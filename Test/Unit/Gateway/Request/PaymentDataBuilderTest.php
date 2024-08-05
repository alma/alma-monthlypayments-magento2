<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Gateway\Request\CartDataBuilder;
use Alma\MonthlyPayments\Gateway\Request\PaymentDataBuilder;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Model\InfoInterface;
use PHPUnit\Framework\TestCase;

class PaymentDataBuilderTest extends TestCase
{
    const RETURN_URL =  'https://mywebsite/alma/payment/return/';
    const IPN_URL =  'https://mywebsite/alma/payment/ipn/';
    const CANCEL_URL =  'https://mywebsite/alma/payment/cancel/';
    const FAILURE_URL =  'https://mywebsite/alma/payment/failure/';
    const EXPIRATION_TIME =  '2';
    const ORIGIN_ONLINE =  'online';
    const ORIGIN_INPAGE =  'online_in_page';
    const INCREMENT_ID =  '100001';
    const QUOTE_ID =  '454';
    const PLAN_KEY_CREDIT =  'general:10:0:0';
    const INSTALLMENT_CREDIT_COUNT =  '10';
    const PLAN_KEY_DEFERRED =  'general:1:15:0';
    const DEFERRED_COUNT =  '15';
    const PLAN_KEY =  'general:3:0:0';
    const INSTALLMENT_COUNT =  '3';
    const LOCALE =  'en_US';

    private $checkoutSession;
    private $config;
    private $locale;
    private $configHelper;
    private $cartDataBuilder;
    private $paymentPlansHelper;

    public function setUp(): void
    {
        $this->checkoutSession = $this->createMock(Session::class);
        $this->config = $this->createMock(Config::class);
        $this->locale = $this->createMock(Resolver::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->cartDataBuilder = $this->createMock(CartDataBuilder::class);
        $this->paymentPlansHelper = $this->createMock(PaymentPlansHelper::class);
        $this->cartDataBuilder->method('build')->willReturn(
            [
                'cart' => [
                    'items' => [
                        'item1'
                    ]
                ]
            ]
        );
    }

    /**
     * @dataProvider paymentPayloadDataProvider
     *
     * @return void
     */
    public function testPaymentPayload($order, $response): void
    {
        $infoInterfaceMock = $this->createMock(InfoInterface::class);
        $infoInterfaceMock->expects($this->once())->method('getAdditionalInformation')->willReturn($order['plan_key']);

        $orderInterfaceMock = $this->createMock(OrderAdapterInterface::class);
        $orderInterfaceMock->expects($this->once())->method('getOrderIncrementId')->willReturn(self::INCREMENT_ID);
        $orderInterfaceMock->expects($this->once())->method('getGrandTotalAmount')->willReturn(100.00);

        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())->method('getPayment')->willReturn($infoInterfaceMock);
        $paymentDataObjectMock->expects($this->once())->method('getOrder')->willReturn($orderInterfaceMock);

        $this->checkoutSession->expects($this->once())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $paymentPlanConfigMock = $this->createMock(PaymentPlanConfigInterface::class);
        $plansMock = [$order['plan_key'] => $paymentPlanConfigMock];
        $paymentPlanConfigMock->expects($this->once())->method('getPaymentData')->willReturn(['installments_count' => $order['installment_count']]);
        $paymentPlanConfigMock->expects($this->any())->method('hasDeferredTrigger')->willReturn($order['plan_has_trigger']);

        $paymentPlansConfigMock = $this->createMock(PaymentPlansConfigInterface::class);
        $paymentPlansConfigMock->expects($this->once())->method('getPlans')->willReturn($plansMock);

        $this->config->expects($this->once())->method('getPaymentPlansConfig')->willReturn($paymentPlansConfigMock);
        $this->config->expects($this->once())->method('getReturnUrl')->willReturn(self::RETURN_URL);
        $this->config->expects($this->once())->method('getIpnCallbackUrl')->willReturn(self::IPN_URL);
        $this->config->expects($this->once())->method('getCustomerCancelUrl')->willReturn(self::CANCEL_URL);
        $this->config->expects($this->once())->method('getFailureReturnUrl')->willReturn(self::FAILURE_URL);

        $this->locale->expects($this->once())->method('getLocale')->willReturn(self::LOCALE);

        $this->configHelper->expects($this->once())->method('triggerIsEnabled')->willReturn($order['has_trigger']);

        $buildSubjectMock = ['payment' => $paymentDataObjectMock];

        $this->paymentPlansHelper->method('isInPageAllowed')->willReturn($order['inpage']);
        $paymentDataBuilder = $this->createPaymentDataBuilderTest()->build($buildSubjectMock);
        $this->assertEquals($response, $paymentDataBuilder);
    }

    public function paymentPayloadDataProvider(): array
    {
        return [
            'Payload with installment key' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY,
                    'installment_count' => self::INSTALLMENT_COUNT,
                    'has_trigger' => false,
                    'plan_has_trigger' => false,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::INSTALLMENT_COUNT)
                ]
            ],
            'Payload with plan deferred key' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY_DEFERRED,
                    'installment_count' => self::DEFERRED_COUNT,
                    'has_trigger' => false,
                    'plan_has_trigger' => false,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::DEFERRED_COUNT)
                ]
            ],
            'Payload with plan credit key' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY_CREDIT,
                    'installment_count' => self::INSTALLMENT_CREDIT_COUNT,
                    'has_trigger' => false,
                    'plan_has_trigger' => false,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::INSTALLMENT_CREDIT_COUNT)
                ]
            ],
            'Payload with In page Installment key' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY_CREDIT,
                    'installment_count' => self::INSTALLMENT_CREDIT_COUNT,
                    'has_trigger' => false,
                    'plan_has_trigger' => false,
                    'inpage' => true,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::INSTALLMENT_CREDIT_COUNT, self::ORIGIN_INPAGE)
                ]
            ],
            'Payload without trigger' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY,
                    'installment_count' => self::INSTALLMENT_COUNT,
                    'has_trigger' => false,
                    'plan_has_trigger' => false,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::INSTALLMENT_COUNT)
                ]
            ],
            'Trigger option is enable but plan does not have trigger option' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY,
                    'installment_count' => self::INSTALLMENT_COUNT,
                    'has_trigger' => true,
                    'plan_has_trigger' => false,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => $this->paymentFactory(self::INSTALLMENT_COUNT)
                ]
            ],
            'Trigger option is enable but plan has trigger option' => [
                'order' => [
                    'plan_key' => self::PLAN_KEY,
                    'installment_count' => self::INSTALLMENT_COUNT,
                    'has_trigger' => true,
                    'plan_has_trigger' => true,
                    'inpage' => false,
                ],
                'final_payload' => [
                    'payment' => array_merge(
                        $this->paymentFactory(self::INSTALLMENT_COUNT),
                        [
                            'deferred' => 'trigger',
                            'deferred_description' => '',
                        ]
                    )
                ]
            ]
        ];
    }

    /**
     * @return PaymentDataBuilder
     */
    private function createPaymentDataBuilderTest(): PaymentDataBuilder
    {
        return new PaymentDataBuilder(...$this->getConstructorDependency());
    }

    /**
     * @param string $installmentCount
     * @param string $inpage
     * @return array
     */
    private function paymentFactory(string $installmentCount, string $inpage = self::ORIGIN_ONLINE): array
    {
        $cartData = [
            'installments_count' => $installmentCount,
            'return_url' => self::RETURN_URL,
            'origin' => $inpage,
            'ipn_callback_url' => self::IPN_URL,
            'customer_cancel_url' => self::CANCEL_URL,
            'failure_return_url' => self::FAILURE_URL,
            'purchase_amount' => 10000,
            'shipping_address' => [],
            'billing_address' => [],
            'locale' => self::LOCALE,
            'expires_after' => 0,
            'custom_data' => [
                'order_id' => self::INCREMENT_ID,
                'quote_id' => self::QUOTE_ID,
            ]
        ];

        $cartData['cart'] = [
            'items' => [
                'item1'
            ]
        ];

        return $cartData;
    }

    /**
     * @return array
     */
    private function getConstructorDependency(): array
    {
        return [
            $this->checkoutSession,
            $this->config,
            $this->locale,
            $this->configHelper,
            $this->cartDataBuilder,
            $this->paymentPlansHelper
        ];
    }
}
