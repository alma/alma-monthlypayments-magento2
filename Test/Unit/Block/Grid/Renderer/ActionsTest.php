<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Grid\Renderer;

use Magento\AdminNotification\Model\Inbox;
use PHPUnit\Framework\TestCase;

class ActionsTest extends TestCase
{
    /**
     * @var \Magento\Backend\Block\Context
     */
    private $context;
    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    private $urlHelper;
    private $data;
    private $actions;

    public function setUp(): void
    {

        $escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $builder = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->context = $this->createMock(\Magento\Backend\Block\Context::class);
        $this->context->method('getEscaper')->willReturn($escaper);
        $this->context->method('getUrlBuilder')->willReturn($builder);
        $this->urlHelper = $this->createMock(\Magento\Framework\Url\Helper\Data::class);
        $this->data = [];
        $this->actions = new \Alma\MonthlyPayments\Block\Grid\Renderer\Actions(
            $this->context,
            $this->urlHelper,
            $this->data
        );
    }

    public function testRenderForAlmaMethod()
    {
        $methods = \array_merge(
            \get_class_methods(Inbox::class),
            ['getTitle', 'getUrl', 'getIsRead', 'getNotificationId']
        );
        $row = $this->getMockBuilder(Inbox::class)->setMethods($methods)->disableOriginalConstructor()->getMock();
        $row->method('getTitle')->willReturn('Alma insurance : New Order #1234567890');
        $row->method('getUrl')->willReturn('Url');
        $row->method('getIsRead')->willReturn(0);
        $row->method('getNotificationId')->willReturn(11);
        $this->urlHelper->method('getEncodedUrl')->willReturn('EncodedUrl');
        $this->assertEquals(
            '<a class="action-details" target="_blank" href="">View Order</a><a class="action-mark" href="">Mark as Read</a><a class="action-delete" href="" onClick="deleteConfirm(\'Are you sure?\', this.href); return false;">Remove</a>',
            $this->actions->render($row)
        );
    }
    public function testRenderForNonAlmaMethod()
    {
        $methods = \array_merge(
            \get_class_methods(Inbox::class),
            ['getTitle', 'getUrl', 'getIsRead', 'getNotificationId']
        );
        $row = $this->getMockBuilder(Inbox::class)->setMethods($methods)->disableOriginalConstructor()->getMock();
        $row->method('getTitle')->willReturn('not Alma');
        $row->method('getUrl')->willReturn('Url');
        $row->method('getIsRead')->willReturn(0);
        $row->method('getNotificationId')->willReturn(11);
        $this->urlHelper->method('getEncodedUrl')->willReturn('EncodedUrl');
        $this->assertEquals(
            '<a class="action-details" target="_blank" href="">Read Details</a><a class="action-mark" href="">Mark as Read</a><a class="action-delete" href="" onClick="deleteConfirm(\'Are you sure?\', this.href); return false;">Remove</a>',
            $this->actions->render($row)
        );
    }
}
