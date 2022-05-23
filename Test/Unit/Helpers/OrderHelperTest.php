<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\OrderHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Helper\Context;

class OrderHelperTest extends TestCase
{
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);
        $orderFactory = $this->createMock(OrderFactory::class);
        $this->orderHelper = new OrderHelper($contextMock,$orderFactory);
    }

    public function testInstanceConfigHelper()
    {
        $this->assertInstanceOf(OrderHelper::class,$this->orderHelper);
    }

    public function testImplementAbstractHelper()
    {
        $this->assertInstanceOf(AbstractHelper::class,$this->orderHelper);
    }


}
