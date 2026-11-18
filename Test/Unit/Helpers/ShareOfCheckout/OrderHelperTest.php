<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers\ShareOfCheckout;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\DateHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\OrderHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderHelperTest extends TestCase
{
    const EURO_CURRENCY = 'EUR';
    /**
     * @var Context
     */
    private $context;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var null
     */
    private $orderHelper;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->dateHelper = $this->createMock(DateHelper::class);
    }

    public function tearDown(): void
    {
        $this->orderHelper = null;
    }

    public function testInstancePayloadBuilder(): void
    {
        $this->assertInstanceOf(OrderHelper::class, $this->createNewOrderHelper());
    }

    public function testImplementAbstractHelperInterface(): void
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->createNewOrderHelper());
    }

    public function testInitTotalOrderResultFormat(): void
    {
        $expectedResult = [
            'total_order_count' => 0,
            'total_amount'      => 0,
            'currency'          => self::EURO_CURRENCY,
        ];
        $this->assertEquals($expectedResult, $this->createNewOrderHelper()->initTotalOrderResult(self::EURO_CURRENCY));
    }

    public function testInitOrderResultFormat(): void
    {
        $expectedResult = [
            'order_count' => 0,
            'amount'      => 0,
            'currency'    => self::EURO_CURRENCY,
        ];
        $this->assertEquals($expectedResult, $this->createNewOrderHelper()->initOrderResult(self::EURO_CURRENCY));
    }

    public function testCreateOrderCollectionParams(): void
    {
        $mockCollection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection)
        ;
        $mockCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->with('*')
            ->willReturnSelf()
        ;
        $filters = [];
        $mockCollection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(function ($field, $condition) use (&$filters, $mockCollection) {
                $filters[] = [$field, $condition];
                return $mockCollection;
            });
        $this->createNewOrderHelper()->createOrderCollection();
        $this->assertSame([
            ['created_at', ['from' => [''], 'to' => ['']]],
            ['state', ['in' => ['processing', 'complete']]]
        ], $filters);
    }

    public function testCreatOrderCollectionNotCallIfOrderCollectionExist(): void
    {
        $orderHelperMock = $this->getMockBuilder(OrderHelper::class)
            ->onlyMethods(['createOrderCollection'])
            ->setConstructorArgs($this->getConstructorDependency())
            ->getMock();
        $orderHelperMock->setOrderCollection($this->createMock(OrderSearchResultInterface::class));
        $orderHelperMock->expects($this->never())
            ->method('createOrderCollection');
        $orderHelperMock->getOrderCollection();
    }

    public function testCreatOrderCollectionIsCallIfOrderCollectionNotExist(): void
    {
        $orderHelperMock = $this->getMockBuilder(OrderHelper::class)
            ->onlyMethods(['createOrderCollection'])
            ->setConstructorArgs($this->getConstructorDependency())
            ->getMock();
        $orderHelperMock->expects($this->once())
            ->method('createOrderCollection');
        $orderHelperMock->getOrderCollection();
    }

    public function testSetOrderCollection(): void
    {
        $orderHelper = $this->createNewOrderHelper();
        $orderSearchResultInterface = $this->createMock(OrderSearchResultInterface::class);
        $orderHelper->setOrderCollection($orderSearchResultInterface);
        $this->assertEquals($orderSearchResultInterface, $orderHelper->getOrderCollection());
    }

    /**
     * @dataProvider totalOrderDataProvider
     */
    #[DataProvider('totalOrderDataProvider')]
    public function testGetTotalsOrdersResult($orders, $result): void
    {
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->setOrderCollection($this->getMockOrderCollection($orders));
        $this->assertEquals(
            $result,
            $orderHelper->getTotalsOrders()
        );
    }

    /**
     * @dataProvider paymentMethodOrderDataProvider
     */
    #[DataProvider('paymentMethodOrderDataProvider')]
    public function testGetPaymentMethodOrdersResult($orders, $result): void
    {
        $orderHelper = $this->createNewOrderHelper();
        $orderHelper->setOrderCollection($this->getMockOrderCollection($orders));
        $this->assertEquals(
            $result,
            $orderHelper->getSOCByPaymentMethods()
        );
    }

    private function createNewOrderHelper(): OrderHelper
    {
        return new OrderHelper(...$this->getConstructorDependency());
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->context,
            $this->collectionFactory,
            $this->dateHelper
        ];
    }
    public static function totalOrderDataProvider(): array
    {
        return [
            'Multi devise orders ' => [
                'orders' => [
                    ['Alma', 'EUR', '100', '0'],
                    ['Alma', 'EUR', '120', '0'],
                    ['Paypal', 'EUR', '110', '0'],
                    ['Paypal', 'USD', '100', '50'],
                ],
                'result' => [
                    [
                        'total_amount' => 33000,
                        'total_order_count' => 3,
                        'currency' => 'EUR',
                    ],
                    [
                        'total_amount' => 5000,
                        'total_order_count' => 1,
                        'currency' => 'USD',
                    ]
                ]
            ]
        ];
    }
    public static function paymentMethodOrderDataProvider(): array
    {
        return [
            'Multi devise orders ' => [
                'orders' => [
                    ['Alma', 'EUR', '100', '0'],
                    ['Alma', 'EUR', '120', '0'],
                    ['Paypal', 'EUR', '110', '0'],
                    ['Paypal', 'USD', '100', '50'],
                ],
                'result' => [
                    [
                        'payment_method_name' => 'Alma',
                        'orders' => [
                            [
                            'amount' => 22000,
                            'order_count' => 2,
                            'currency' => 'EUR'
                            ]
                        ]
                    ],
                    [
                        'payment_method_name' => 'Paypal',
                        'orders' => [
                            [
                                'amount' => 11000,
                                'order_count' => 1,
                                'currency' => 'EUR'
                            ],
                            [
                                'amount' => 5000,
                                'order_count' => 1,
                                'currency' => 'USD'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }


    private function getMockOrderCollection(array $orders)
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        return $objectManagerHelper->getCollectionMock(
            Collection::class,
            array_map(function (array $order) {
                return $this->mockOrderFactory(...$order);
            }, $orders)
        );
    }

    private function mockOrderFactory($paymentMethodeCode, $currencyCode, $amountPaid, $amountRefund): Order
    {
        $payment = $this->createMock(OrderPaymentInterface::class);
        $payment->method('getMethod')
            ->willReturn($paymentMethodeCode);
        $payment->expects($this->once())
            ->method('getAmountPaid')
            ->willReturn($amountPaid);
        $payment->expects($this->once())
            ->method('getAmountRefunded')
            ->willReturn($amountRefund);
        $order = $this->createMock(Order::class);
        $order->method('getPayment')
            ->willReturn($payment);
        $order->expects($this->once())
            ->method('getOrderCurrencyCode')
            ->willReturn($currencyCode);
        return $order;
    }

}
