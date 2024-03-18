<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer\Admin;

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceFlagException;
use Alma\MonthlyPayments\Observer\Admin\LoadConfigObserver;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class LoadConfigObserverTest extends TestCase
{
    const INSURANCE_URL = 'https://mytestSite.test/backadm/admin/system_config/edit/section/alma_insurance_section/key/ee6/';
    const PAYMENT_URL = 'https://mytestSite.test/backadm/admin/system_config/edit/section/payment/key/ee6/';
    const NON_PAYMENT_URL = 'https://mytestSite.test/backadm/admin/system_config/edit/section/catalog/key/ee6/';
    private $urlInterface;
    private $paymentPlansHelper;
    private $logger;
    private $observer;
    private $availability;
    private $configHelper;
    private $storeHelper;
    private $httpInterface;

    public function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->urlInterface = $this->createMock(UrlInterface::class);
        $this->paymentPlansHelper = $this->createMock(PaymentPlansHelper::class);
        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction'])
            ->getMock();
        $actionInterface = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'getResponse'])
            ->getMock();
        $this->httpInterface = $this->createMock(HttpInterface::class);
        $actionInterface->method('getResponse')->willReturn($this->httpInterface);
        $eventMock->method('getControllerAction')->willReturn($actionInterface);
        $this->observer = $this->createMock(Observer::class);
        $this->observer->method('getEvent')->willReturn($eventMock);
        $this->availability = $this->createMock(Availability::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->storeHelper = $this->createMock(StoreHelper::class);
        $this->storeHelper->method('getScope')->willReturn('default');
        $this->storeHelper->method('getStoreId')->willReturn('1');
    }

    public function tearDown(): void
    {
        $this->logger = null;
        $this->urlInterface = null;
        $this->paymentPlansHelper = null;
        $this->availability = null;
        $this->configHelper = null;
        $this->storeHelper = null;
    }

    private function createLoadConfigObserver(): LoadConfigObserver
    {
        return new LoadConfigObserver(
            $this->logger,
            $this->urlInterface,
            $this->paymentPlansHelper,
            $this->availability,
            $this->configHelper,
            $this->storeHelper,
        );
    }


    public function testNeverCallSaveBaseApiPlansConfigForNonPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::NON_PAYMENT_URL);

        $this->paymentPlansHelper->expects($this->never())
            ->method('saveBaseApiPlansConfig');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testCallSaveBaseApiPlansConfigForPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);

        $this->paymentPlansHelper->expects($this->once())
            ->method('saveBaseApiPlansConfig');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testNeverCallGetMerchantInsuranceAvailableForNonPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::NON_PAYMENT_URL);

        $this->configHelper->expects($this->never())
            ->method('saveIsAllowedInsuranceValue');

        $this->configHelper->expects($this->never())
            ->method('clearInsuranceConfig');
        $this->availability->expects($this->never())
            ->method('getMerchantInsuranceAvailability');
        $this->createLoadConfigObserver()->execute($this->observer);
    }


    public function testGivenApiMeThrowExceptionMustReturnThisWithoutCallSaveButCallingFeePlans(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);
        $this->paymentPlansHelper->expects($this->once())
            ->method('saveBaseApiPlansConfig');
        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability')
            ->willThrowException(new AlmaInsuranceFlagException('No Api Key', $this->logger));
        $this->configHelper->expects($this->never())
            ->method('saveIsAllowedInsuranceValue');
        $this->configHelper->expects($this->never())
            ->method('clearInsuranceConfig');
        $instance = $this->createLoadConfigObserver();
        $this->assertEquals($instance, $instance->execute($this->observer));
    }

    public function testCallGetMerchantInsuranceAvailabilityHasFalseAndGetConfigCodeTrueForPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);

        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability')
            ->willReturn(false);
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode')
            ->willReturn('1');
        $this->configHelper->expects($this->once())
            ->method('saveIsAllowedInsuranceValue')
            ->with(0);
        $this->configHelper->expects($this->once())
            ->method('clearInsuranceConfig');
        $this->httpInterface->expects($this->once())
            ->method('setRedirect')
            ->with(self::PAYMENT_URL);
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testCallGetMerchantInsuranceAvailabilityHasFalseAndGetConfigCodeFalseForPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);

        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability')
            ->willReturn(false);
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode')
            ->willReturn('0');
        $this->configHelper->expects($this->never())
            ->method('saveIsAllowedInsuranceValue');
        $this->configHelper->expects($this->never())
            ->method('clearInsuranceConfig');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testCallGetMerchantInsuranceAvailabilityHasTrueForPaymentUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);

        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability')
            ->willReturn(true);
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode');
        $this->configHelper->expects($this->never())
            ->method('saveIsAllowedInsuranceValue');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testGivenInsuranceFlagTrueAndConfigFalseMustSaveIsAllowedInsuranceAndRedirect(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::INSURANCE_URL);
        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability')
            ->willReturn(true);
        $this->configHelper->expects($this->once())
            ->method('getConfigByCode')
            ->willReturn('0');
        $this->configHelper->expects($this->once())
            ->method('saveIsAllowedInsuranceValue')
            ->with(1);
        $this->httpInterface->expects($this->once())
            ->method('setRedirect')
            ->with(self::INSURANCE_URL);
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testCallGetMerchantInsuranceAvailabilityAndNeverSaveBaseApiPlansConfigForInsuranceUrl(): void
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::INSURANCE_URL);

        $this->availability->expects($this->once())
            ->method('getMerchantInsuranceAvailability');

        $this->paymentPlansHelper->expects($this->never())
            ->method('saveBaseApiPlansConfig');
        $this->createLoadConfigObserver()->execute($this->observer);
    }
}
