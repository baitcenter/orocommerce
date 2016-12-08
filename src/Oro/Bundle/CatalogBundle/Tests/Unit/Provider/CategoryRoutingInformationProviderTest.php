<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryRoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryRoutingInformationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RoutingInformationProviderInterface
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new CategoryRoutingInformationProvider();
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->provider->isSupported(new Category()));
    }

    public function testIsNotSupported()
    {
        $this->assertFalse($this->provider->isSupported(new \DateTime()));
    }

    public function testGetUrlPrefix()
    {
        $this->assertSame('', $this->provider->getUrlPrefix(new Category()));
    }

    public function testGetRouteData()
    {
        $this->assertEquals(
            new RouteData('oro_product_frontend_product_index', ['categoryId' => 42, 'includeSubcategories' => true]),
            $this->provider->getRouteData($this->getEntity(Category::class, ['id' => 42]))
        );
    }
}
