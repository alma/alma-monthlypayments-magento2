<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer\Admin;

use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Observer\Admin\LoadConfigObserver;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class LoadConfigObserverTest extends TestCase
{
    const PAYMENT_URL = 'https://mytestSite.test/backadm/admin/system_config/edit/section/payment/key/ee6/';
    const NON_PAYMENT_URL = 'https://mytestSite.test/backadm/admin/system_config/edit/section/catalog/key/ee6/';
    private $urlInterface;
    private $paymentPlansHelper;
    private $observer;
    /**
     * @var CollectCmsConfigHelper
     */
    private $collectCmsConfigHelper;

    public function setUp(): void
    {
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
        $eventMock->method('getControllerAction')->willReturn($actionInterface);
        $this->observer = $this->createMock(Observer::class);
        $this->observer->method('getEvent')->willReturn($eventMock);
        $this->collectCmsConfigHelper = $this->createMock(CollectCmsConfigHelper::class);

    }

    public function tearDown(): void
    {
        $this->urlInterface = null;
        $this->paymentPlansHelper = null;
        $this->collectCmsConfigHelper = null;
    }

    private function createLoadConfigObserver(): LoadConfigObserver
    {
        return new LoadConfigObserver(
            $this->urlInterface,
            $this->paymentPlansHelper,
            $this->collectCmsConfigHelper
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

    public function testObserverNotSendIfNotNecessary()
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn((string)time());
        $this->collectCmsConfigHelper->expects($this->never())->method('sendIntegrationsConfigurationsUrl');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

    public function testObserverSendIfNecessary()
    {
        $this->urlInterface->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn(self::PAYMENT_URL);
        $this->collectCmsConfigHelper->expects($this->once())->method('getSendCollectUrlStatus')->willReturn(null);
        $this->collectCmsConfigHelper->expects($this->once())->method('sendIntegrationsConfigurationsUrl');
        $this->createLoadConfigObserver()->execute($this->observer);
    }

}
