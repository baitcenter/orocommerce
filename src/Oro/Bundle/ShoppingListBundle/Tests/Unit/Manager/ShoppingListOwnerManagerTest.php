<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListOwnerManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListOwnerManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $aclHelper;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ShoppingListOwnerManager
     */
    protected $manager;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder(AclHelper::class)->disableOriginalConstructor()->getMock();
        $configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $entityConfig = $this->createMock(ConfigInterface::class);
        $configProvider->method('getConfig')->with(ShoppingList::class)->willReturn($entityConfig);
        $entityConfig->method('get')->willReturnMap([
            ['frontend_owner_field_name', false, null, 'customerUser'],
            ['organization_field_name', false, null, 'organisation'],
        ]);

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->manager = new ShoppingListOwnerManager(
            $this->aclHelper,
            $this->registry,
            $configProvider
        );
    }

    public function testSetOwner()
    {
        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);

        $qb = $this->getQueryBuilder();
        $repo->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $queryWithCriteria = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with(
                $qb,
                'ASSIGN',
                [
                    'aclDisable' => true,
                    'availableOwnerEnable' => true,
                    'availableOwnerTargetEntityClass' => ShoppingList::class
                ]
            )
            ->willReturn($queryWithCriteria);
        $queryWithCriteria->method('getOneOrNullResult')->willReturn($user);

        $shoppingList = new ShoppingList();
        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);
        $em->expects($this->once())->method('flush');

        $this->manager->setOwner(1, $shoppingList);
        $this->assertSame($user, $shoppingList->getCustomerUser());
    }

    public function testSetSameOwner()
    {
        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        // if new owner is same as current owner don't run flush
        $em->expects($this->never())->method('flush');

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($user);
        $this->manager->setOwner(1, $shoppingList);
        $this->assertSame($user, $shoppingList->getCustomerUser());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage User with id=1 not exists
     */
    public function testSetOwnerUserNotExists()
    {
        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);
        // user with requested id not exists
        $repo->method('find')->with(1)->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        // flush should not be called
        $em->expects($this->never())->method('flush');
        $this->manager->setOwner(1, new ShoppingList());
    }

    public function testSetOwnerPermissionDenied()
    {
        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);

        $qb = $this->getQueryBuilder();
        $repo->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $queryWithCriteria = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->willReturn($queryWithCriteria);
        $queryWithCriteria->method('getOneOrNullResult')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        // flush should not be called
        $em->expects($this->never())->method('flush');
        $this->expectException(AccessDeniedException::class);
        $this->manager->setOwner(1, new ShoppingList());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getQueryBuilder()
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);

        return $qb;
    }
}
