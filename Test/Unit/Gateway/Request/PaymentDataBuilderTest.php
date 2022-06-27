<?php

namespace Alma\MonthlyPayments\Test\Unit\Gateway\Request;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfigInterface;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlansConfigInterface;
use Alma\MonthlyPayments\Gateway\Request\PaymentDataBuilder;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
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
    const INCREMENT_ID =  '100001';
    const QUOTE_ID =  '454';
    const PLAN_KEY =  'general:4:0:0';
    const INSTALLMENT_COUNT =  '4';
    const LOCALE =  'en_US';

    public function setUp(): void
    {
        $this->checkoutSession = $this->createMock(Session::class);
        $this->config = $this->createMock(Config::class);
        $this->locale = $this->createMock(Resolver::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
    }

    /**
     * @dataProvider paymentPayloadDataProvider
     *
     * @return void
     */
    public function testNonTriggerPaymentPayload($order, $response): void
    {
        $infoInterfaceMock = $this->createMock(InfoInterface::class);
        $infoInterfaceMock->expects($this->once())->method('getAdditionalInformation')->willReturn(self::PLAN_KEY);

        $orderInterfaceMock = $this->createMock(OrderAdapterInterface::class);
        $orderInterfaceMock->expects($this->once())->method('getOrderIncrementId')->willReturn(self::INCREMENT_ID);
        $orderInterfaceMock->expects($this->once())->method('getGrandTotalAmount')->willReturn(100.00);

        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())->method('getPayment')->willReturn($infoInterfaceMock);
        $paymentDataObjectMock->expects($this->once())->method('getOrder')->willReturn($orderInterfaceMock);

        $this->checkoutSession->expects($this->once())->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $paymentPlanConfigMock = $this->createMock(PaymentPlanConfigInterface::class);
        $plansMock = [self::PLAN_KEY => $paymentPlanConfigMock];
        $paymentPlanConfigMock->expects($this->once())->method('getPaymentData')->willReturn(['installments_count' => self::INSTALLMENT_COUNT]);
        $paymentPlanConfigMock->expects($this->any())->method('hasDeferredTrigger')->willReturn($order['plan_has_trigger']);

        $paymentPlansConfigMock = $this->createMock(PaymentPlansConfigInterface::class);
        $paymentPlansConfigMock->expects($this->once())->method('getPlans')->willReturn($plansMock);

        $this->config->expects($this->once())->method('getPaymentPlansConfig')->willReturn($paymentPlansConfigMock);
        $this->config->expects($this->once())->method('getReturnUrl')->willReturn(self::RETURN_URL);
        $this->config->expects($this->once())->method('getIpnCallbackUrl')->willReturn(self::IPN_URL);
        $this->config->expects($this->once())->method('getCustomerCancelUrl')->willReturn(self::CANCEL_URL);
        $this->config->expects($this->once())->method('getFailureReturnUrl')->willReturn(self::FAILURE_URL);

        $this->locale->expects($this->once())->method('getLocale')->willReturn(self::LOCALE);

        $this->configHelper->expects($this->once())->method('triggerIsEnabled')->willReturn($order['is_trigger']);

        $buildSubjectMock = ['payment' => $paymentDataObjectMock];
        $paymentDataBuilder = $this->createPaymentDataBuilderTest();
        $this->assertEquals($response, $paymentDataBuilder->build($buildSubjectMock));
    }

    public function paymentPayloadDataProvider(): array
    {
        $paymentBase = [
            'installments_count' => self::INSTALLMENT_COUNT,
            'return_url' => self::RETURN_URL,
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

        return [
            'Non trigger Payload' => [
                'order' => [
                    'is_trigger' => false,
                    'plan_has_trigger' => false,
                ],
                'final_payload' => [
                    'payment' => $paymentBase
                ]
            ],
            'Trigger is enable but plan is not trigger' => [
                'order' => [
                    'is_trigger' => true,
                    'plan_has_trigger' => false,
                ],
                'final_payload' => [
                    'payment' => $paymentBase
                ]
            ],
            'Trigger is enable and plan is trigger' => [
                'order' => [
                    'is_trigger' => true,
                    'plan_has_trigger' => true,
                ],
                'final_payload' => [
                    'payment' => array_merge(
                        $paymentBase,
                        [
                            'deferred' => 'trigger',
                            'deferred_description' => '',
                        ]
                    )
                ]
            ]
        ];
    }

    private function createPaymentDataBuilderTest(): PaymentDataBuilder
    {
        return new PaymentDataBuilder(...$this->getConstructorDependency());
    }

    private function getConstructorDependency(): array
    {
        return [
            $this->checkoutSession,
            $this->config,
            $this->locale,
            $this->configHelper,
        ];
    }
}
