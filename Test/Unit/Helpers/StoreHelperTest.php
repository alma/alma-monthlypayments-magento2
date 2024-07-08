<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;
use PHPUnit\Framework\TestCase;

class StoreHelperTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var State
     */
    private $state;
    /**
     * @var StoreResolver
     */
    private $storeManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Logger
     */
    private $logger;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->state = $this->createMock(State::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
    }

    /**
     * @dataProvider  dataProviderForTestStoreIdAndScope
     *
     * @param $data
     *
     * @return void
     */
    public function testStoreId($data): void
    {
        $this->state->method('getAreaCode')->willReturn($data['area']);
        $mockStoreInterface = $this->createMock(StoreInterface::class);
        $mockStoreInterface->method('getId')->willReturn($data['currentStoreId']);
        $this->storeManager->method('getStore')->willReturn($mockStoreInterface);
        $this->request->method('getParam')->willReturnOnConsecutiveCalls($data['store'], $data['website']);
        $this->assertEquals($data['expectedStoreId'], $this->createNewStoreHelper()->getStoreId());
    }

    /**
     * @dataProvider  dataProviderForTestStoreIdAndScope
     *
     * @param $data
     *
     * @return void
     */
    public function testScope($data): void
    {
        $this->state->method('getAreaCode')->willReturn($data['area']);
        $this->request->method('getParam')->willReturnOnConsecutiveCalls($data['store'], $data['website']);
        $this->assertEquals($data['expectedScope'], $this->createNewStoreHelper()->getScope());
    }

    public function dataProviderForTestStoreIdAndScope(): array
    {
        return [
            'In Front in default view' => [
                [
                    'storeId' => null,
                    'scope' => null,
                    'area' => StoreHelper::AREA_FRONT,
                    'currentStoreId' => '1',
                    'store' => null,
                    'website' => null,
                    'expectedStoreId' => '1',
                    'expectedScope' => ScopeInterface::SCOPE_STORES
                ]
            ],
            'In Back office in default view' => [
                [
                    'storeId' => null,
                    'scope' => null,
                    'area' => StoreHelper::AREA_BACK,
                    'currentStoreId' => null,
                    'store' => null,
                    'website' => null,
                    'expectedStoreId' => '0',
                    'expectedScope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                ]
            ],
            'In Back office in specific store view' => [
                [
                    'storeId' => null,
                    'scope' => null,
                    'area' => StoreHelper::AREA_BACK,
                    'currentStoreId' => null,
                    'store' => '2',
                    'website' => null,
                    'expectedStoreId' => '2',
                    'expectedScope' => ScopeInterface::SCOPE_STORES
                ]
            ],
            'In Back office in specific website' => [
                [
                    'storeId' => null,
                    'scope' => null,
                    'area' => StoreHelper::AREA_BACK,
                    'currentStoreId' => null,
                    'store' => null,
                    'website' => '1',
                    'expectedStoreId' => '1',
                    'expectedScope' => ScopeInterface::SCOPE_WEBSITES
                ]
            ],
            'In rest API' => [
                [
                    'storeId' => null,
                    'scope' => ScopeInterface::SCOPE_STORES,
                    'area' => StoreHelper::AREA_API,
                    'currentStoreId' => '3',
                    'store' => null,
                    'website' => null,
                    'expectedStoreId' => '3',
                    'expectedScope' => ScopeInterface::SCOPE_STORES
                ]
            ],
        ];
    }

    /**
     * @return StoreHelper
     */
    private function createNewStoreHelper(): StoreHelper
    {
        return new StoreHelper(...$this->getConstructorDependency());
    }

    /**
     * @return array
     */
    private function getConstructorDependency(): array
    {
        return [
            $this->context,
            $this->state,
            $this->storeManager,
            $this->request,
            $this->logger,
        ];
    }
}
