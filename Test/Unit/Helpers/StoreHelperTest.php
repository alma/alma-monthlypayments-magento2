<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;
use PHPUnit\Framework\TestCase;

class StoreHelperTest extends TestCase
{
    /**
     * @var Context|(Context&object&\PHPUnit\Framework\MockObject\MockObject)|(Context&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;
    /**
     * @var State|(State&object&\PHPUnit\Framework\MockObject\MockObject)|(State&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $state;
    /**
     * @var StoreResolver|(StoreResolver&object&\PHPUnit\Framework\MockObject\MockObject)|(StoreResolver&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManager;
    /**
     * @var RequestInterface|(RequestInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(RequestInterface&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;
    /**
     * @var Logger|(Logger&object&\PHPUnit\Framework\MockObject\MockObject)|(Logger&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
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
        $this->storeManager->method('getCurrentStoreId')->willReturn($data['currentStoreId']);
        $this->request->method('getParam')->willReturnOnConsecutiveCalls($data['store'], $data['website']);
        $this->assertEquals($data['expectedStoreId'], $this->createNewStoreHelper()->getStoreId($data['storeId']));
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
        $this->assertEquals($data['expectedScope'], $this->createNewStoreHelper()->getScope($data['scope']));
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
