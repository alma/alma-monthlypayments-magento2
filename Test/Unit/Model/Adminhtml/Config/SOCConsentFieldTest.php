<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Config;

use Alma\API\Client;
use Alma\API\Endpoints\ShareOfCheckout;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Alma\MonthlyPayments\Model\Adminhtml\Config\SOCConsentField;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;

/**
 * Class SOCConsentFieldTest
 *
 * This class tests SOCConsentField class
 */
class SOCConsentFieldTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->socHelper = $this->createMock(SOCHelper::class);
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $this->typeListInterface = $this->createMock(TypeListInterface::class);
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->logger = $this->createMock(Logger::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
    }

    /**
     * @return void
     */
    public function testBeforeSaveExitWithoutChanges()
    {
        $this->createShareOfCheckoutMock();
        $socFieldSaveObject = $this->createSOCConsentField();
        $this->assertEquals($socFieldSaveObject, $socFieldSaveObject->beforeSave());
    }

    /**
     * @return SOCConsentField
     */
    private function createSOCConsentField(): SOCConsentField
    {
        return new SOCConsentField(...$this->getConstructorDependency());
    }

    /**
     * @return void
     */
    private function createShareOfCheckoutMock(): void
    {
        $socEndpoint = $this->createMock(ShareOfCheckout::class);
        $socEndpoint->method('addConsent');
        $socEndpoint->method('removeConsent');

        $almaClientMock = $this->createMock(Client::class);
        $almaClientMock->shareOfCheckout = $socEndpoint;
        $this->almaClient->method('getDefaultClient')->willReturn($almaClientMock);
    }

    /**
     * @return array
     */
    private function getConstructorDependency(): array
    {
        return [
            $this->socHelper,
            $this->context,
            $this->registry,
            $this->scopeConfigInterface,
            $this->typeListInterface,
            $this->almaClient,
            $this->logger,
            $this->messageManager
        ];
    }
}
