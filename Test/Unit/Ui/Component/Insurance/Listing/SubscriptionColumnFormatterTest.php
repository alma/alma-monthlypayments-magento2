<?php

namespace Alma\MonthlyPayments\Test\Unit\Ui\Component\Insurance\Listing;

use Alma\MonthlyPayments\Helpers\InsuranceSubscriptionHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column\SubscriptionColumnFormatter;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\TestCase;

class SubscriptionColumnFormatterTest extends TestCase
{
    private $logger;
    private $subscriptionHelper;
    private $context;
    private $uiComponentFactory;
    private $orderRepository;
    private $priceCurrency;


    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->subscriptionHelper = $this->createMock(InsuranceSubscriptionHelper::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
    }

    protected function tearDown(): void
    {
        $this->logger = null;
        $this->subscriptionHelper = null;
        $this->context = null;
        $this->uiComponentFactory = null;
        $this->orderRepository = null;
        $this->priceCurrency = null;
    }

    private function createSubscriptionColumnFormatter()
    {
        return new SubscriptionColumnFormatter(
            $this->logger,
            $this->subscriptionHelper,
            $this->context,
            $this->uiComponentFactory,
            $this->orderRepository,
            $this->priceCurrency
        );
    }

    public function testPrepareDatSourceFormatData()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'order_id' => 1,
                        'subscription_state' => 'started',
                        'subscription_amount' => 10000,
                    ],
                    [
                        'order_id' => 2,
                        'subscription_state' => null,
                        'subscription_amount' => 10000,
                    ],
                ],
            ],
        ];
        $currency = 'EUR';
        $orderInterface = $this->createMock(OrderInterface::class);
        $orderInterface->expects($this->exactly(2))->method('getOrderCurrencyCode')->willReturn($currency);
        $this->orderRepository->method('get')->willReturn($orderInterface);
        $this->priceCurrency->expects($this->exactly(2))
            ->method('convertAndFormat')
            ->with(100, false, 2, null, $currency)
            ->willReturn('100 €');
        $this->subscriptionHelper->expects($this->exactly(2))
            ->method('getNameStatus')
            ->willReturnMap([
                ['started', 'Active'],
                ['', ''],
            ]);
        $subscriptionColumnFormatter = $this->createSubscriptionColumnFormatter();

        $expected = [
            'data' => [
                'items' => [
                    [
                        'order_id' => 1,
                        'subscription_state' => 'Active',
                        'subscription_amount' => '100 €',
                    ],
                    [
                        'order_id' => 2,
                        'subscription_state' => '',
                        'subscription_amount' => '100 €',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $subscriptionColumnFormatter->prepareDataSource($dataSource));
    }

}
