<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\API\Endpoints\Insurance;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\InsuranceSendCustomerCartHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;
use PHPUnit\Framework\TestCase;

class InsuranceSendCustomerCartHelperTest extends TestCase
{
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Context
     */
    private $context;
    private $orderId;
    /**
     * @var Logger
     */
    private $logger;
    protected function setUp(): void
    {
        $this->almaClient = $this->createMock(AlmaClient::class);
        $this->context = $this->createMock(Context::class);
        $this->logger = $this->createMock(Logger::class);
        $this->orderId = 42;
    }
    private function getConstructorDependencies(): array
    {
        return [
            $this->context,
            $this->almaClient,
            $this->logger
        ];
    }

    private function newInsuranceSendCustomerCartHelper(): InsuranceSendCustomerCartHelper
    {
        return new InsuranceSendCustomerCartHelper(...$this->getConstructorDependencies());
    }

    public function testSendCustomerCartMustReturnNullNeverThrowException()
    {
        $itemsCollection = $this->collectionFactory();
        $newInsuranceSendCustomerCartHelper = $this->newInsuranceSendCustomerCartHelper();
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->method('sendCustomerCart')->willThrowException(new AlmaClientException('Error sending customer cart to Alma'));
        $client = $this->createMock(Client::class);
        $client->insurance = $insuranceEndpoint;
        $this->almaClient->expects($this->once())->method('getDefaultClient')->willReturn($client);
        $this->logger->expects($this->once())->method('error');
        $this->assertNull($newInsuranceSendCustomerCartHelper->sendCustomerCart($itemsCollection, $this->orderId));
    }

    public function testGivenSimpleItemsCollectionSendCustomerCartThenCallAlmaClient()
    {
        $itemsCollection = $this->collectionFactory();
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('sendCustomerCart')->with(['mb-024', 'mb-025']);
        $client = $this->createMock(Client::class);
        $client->insurance = $insuranceEndpoint;
        $this->almaClient->expects($this->once())->method('getDefaultClient')->willReturn($client);
        $newInsuranceSendCustomerCartHelper = $this->newInsuranceSendCustomerCartHelper();
        $newInsuranceSendCustomerCartHelper->sendCustomerCart($itemsCollection, $this->orderId);
    }

    public function testGivenConfigurableItemCOllectionSendCustomerCartThenCallAlmaClient()
    {
        $itemsCollection = $this->collectionFactory(true);
        $insuranceEndpoint = $this->createMock(Insurance::class);
        $insuranceEndpoint->expects($this->once())->method('sendCustomerCart')->with(['mb-024', 'mb-025']);
        $client = $this->createMock(Client::class);
        $client->insurance = $insuranceEndpoint;
        $this->almaClient->expects($this->once())->method('getDefaultClient')->willReturn($client);
        $newInsuranceSendCustomerCartHelper = $this->newInsuranceSendCustomerCartHelper();
        $newInsuranceSendCustomerCartHelper->sendCustomerCart($itemsCollection, $this->orderId);
    }

    private function collectionFactory($withConfigurable = false): Collection
    {
        $items = [];
        $item1 = $this->createMock(\Magento\Sales\Model\Order\Invoice\Item::class);
        $item1->expects($this->once())->method('getSku')->willReturn('mb-024');
        $item1->expects($this->once())->method('getParentId')->willReturn(null);
        $items[] = $item1;

        $item2 = $this->createMock(\Magento\Sales\Model\Order\Invoice\Item::class);
        $item2->expects($this->once())->method('getSku')->willReturn(InsuranceHelper::ALMA_INSURANCE_SKU);
        $item2->expects($this->once())->method('getParentId')->willReturn(null);
        $items[] = $item2;

        if ($withConfigurable) {
            $item3 = $this->createMock(\Magento\Sales\Model\Order\Invoice\Item::class);
            $item3->method('getSku')->willReturn('mb-024');
            $item3->expects($this->once())->method('getParentId')->willReturn(23);
            $items[] = $item3;
        }

        $item4 = $this->createMock(\Magento\Sales\Model\Order\Invoice\Item::class);
        $item4->expects($this->once())->method('getSku')->willReturn('mb-025');
        $item4->expects($this->once())->method('getParentId')->willReturn(null);
        $items[] = $item4;

        $iterator = new \ArrayIterator($items);

        $itemsCollection = $this->createMock(Collection::class);
        $itemsCollection->expects($this->once())->method('getIterator')->willReturn($iterator);
        return $itemsCollection;
    }
}
