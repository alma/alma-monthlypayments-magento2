<?php

namespace Alma\MonthlyPayments\Ui\Component\Insurance\Listing\Column;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class OrderContainSubscriptionTest extends TestCase
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ContextInterface
     */
    private $contextInterface;
    /**
     * @var UiComponentFactory
     */
    private $uiComponentFactory;


    protected function setUp(): void
    {
        $this->contextInterface = $this->createMock(ContextInterface::class);
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
    }

    protected function tearDown(): void
    {
        $this->contextInterface = null;
        $this->uiComponentFactory = null;
    }

    /**
     * @return OrderContainSubscription
     */
    protected function createOrderContainSubscription() : OrderContainSubscription
    {
        return new OrderContainSubscription(
            $this->logger,
            $this->contextInterface,
            $this->uiComponentFactory
        );
    }


}
