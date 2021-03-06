<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CheckoutPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $factory;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutLineItemsManager;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutSubtotalProvider;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $totalProcessorProvider;

    /** @var OrderPaymentLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentLineItemConverter;

    /** @var PaymentContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextBuilderMock;

    /** @var PaymentContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentContextBuilderFactoryMock;

    /** @var ShippingOriginProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $shippingOriginProvider;

    protected function setUp()
    {
        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);

        $this->totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextBuilderMock = $this->createMock(PaymentContextBuilderInterface::class);

        $this->paymentLineItemConverter = $this->createMock(OrderPaymentLineItemConverterInterface::class);

        $this->paymentContextBuilderFactoryMock = $this->createMock(PaymentContextBuilderFactoryInterface::class);

        $this->shippingOriginProvider = $this->createMock(ShippingOriginProvider::class);

        $this->factory = new CheckoutPaymentContextFactory(
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalProvider,
            $this->totalProcessorProvider,
            $this->paymentLineItemConverter,
            $this->shippingOriginProvider,
            $this->paymentContextBuilderFactoryMock
        );
    }

    protected function tearDown()
    {
        unset(
            $this->factory,
            $this->paymentContextBuilderFactoryMock,
            $this->paymentLineItemConverter,
            $this->contextBuilderMock,
            $this->checkoutSubtotalProvider,
            $this->totalProcessorProvider,
            $this->checkoutLineItemsManager
        );
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param AddressInterface|null $address
     * @param string $currency
     * @param string $shippingMethod
     * @param float $amount
     * @param Customer|null $customer
     * @param CustomerUser|null $customerUser
     * @param array $checkoutLineItems
     * @param Website|null $website
     */
    public function testCreate(
        AddressInterface $address = null,
        $currency = 'USD',
        $shippingMethod = 'SomeShippingMethod',
        $amount = 0.0,
        Customer $customer = null,
        CustomerUser $customerUser = null,
        array $checkoutLineItems = [],
        Website $website = null
    ) {
        $checkout = $this->prepareCheckout(
            $address,
            $currency,
            $shippingMethod,
            $amount,
            $customer,
            $customerUser,
            $checkoutLineItems,
            $website
        );

        $convertedLineItems = new DoctrinePaymentLineItemCollection([
            new PaymentLineItem([])
        ]);

        $shippingOrigin = new ShippingOrigin();
        $this->shippingOriginProvider
            ->expects($this->once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $this->contextBuilderMock->expects($this->once())
            ->method('setShippingOrigin')
            ->with($shippingOrigin);

        $this->paymentLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setLineItems')
            ->with($convertedLineItems);

        $this->factory->create($checkout);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $checkoutLineItems = [new OrderLineItem()];
        $website = $this->createMock(Website::class);

        return [
            'all values' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                $customer,
                $customerUser,
                $checkoutLineItems,
                $website
            ],
            'without customer and customer user (anonymous)' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                null,
                null,
                $checkoutLineItems,
                $website
            ],
            'without customer user (reassigned)' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                $customer,
                null,
                $checkoutLineItems,
                $website
            ]
        ];
    }

    public function testWithNullLineItems()
    {
        $checkout = $this->prepareCheckout();

        $this->paymentLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn(null);

        $this->contextBuilderMock
            ->expects($this->never())
            ->method('setLineItems');

        $this->factory->create($checkout);
    }

    /**
     * @param AddressInterface|null $address
     * @param string $currency
     * @param string $shippingMethod
     * @param float $amount
     * @param Customer|null $customer
     * @param CustomerUser|null $customerUser
     * @param array $checkoutLineItems
     * @param Website|null $website
     * @return Checkout
     */
    protected function prepareCheckout(
        AddressInterface $address = null,
        $currency = 'USD',
        $shippingMethod = 'SomeShippingMethod',
        $amount = 0.0,
        Customer $customer = null,
        CustomerUser $customerUser = null,
        array $checkoutLineItems = [],
        Website $website = null
    ): Checkout {
        $checkoutLineItems = new ArrayCollection($checkoutLineItems);

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setShippingMethod($shippingMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setWebsite($website);

        $this->contextBuilderMock->expects($address ? $this->once() : $this->never())
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilderMock->expects($address ? $this->once() : $this->never())
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilderMock->expects($shippingMethod ? $this->once() : $this->never())
            ->method('setShippingMethod')
            ->with($shippingMethod);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setSubTotal')
            ->with(Price::create($subtotal->getAmount(), $subtotal->getCurrency()))
            ->willReturnSelf();

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCurrency')
            ->with($checkout->getCurrency());

        $this->contextBuilderMock->expects($website ? $this->once() : $this->never())
            ->method('setWebsite')
            ->with($website);

        $this->contextBuilderMock->expects($customer ? $this->once() : $this->never())
            ->method('setCustomer')
            ->with($customer);

        $this->contextBuilderMock->expects($customerUser ? $this->once() : $this->never())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setTotal')
            ->with($subtotal->getAmount())
            ->willReturnSelf();

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('getResult');

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($checkout, (string)$checkout->getId())
            ->willReturn($this->contextBuilderMock);

        $this->checkoutLineItemsManager
            ->expects(static::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->checkoutSubtotalProvider
            ->expects(static::once())
            ->method('getSubtotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->totalProcessorProvider
            ->expects(static::once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        return $checkout;
    }
}
