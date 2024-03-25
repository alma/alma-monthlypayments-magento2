<?php

namespace Alma\MonthlyPayments\Test\Unit\Block\Grid\Renderer;

use Magento\AdminNotification\Model\Inbox;
use Magento\Catalog\Block\Product\View\Description;
use PHPUnit\Framework\TestCase;

class NoticeTest extends TestCase
{
    private $context;
    private $logger;
    private $data;
    private $notice;
    private $escaper;

    public function setUp(): void
    {
        $this->escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $this->context = $this->createMock(\Magento\Backend\Block\Context::class);
        $this->context->method('getEscaper')->willReturn($this->escaper);

        $this->logger = $this->createMock(\Alma\MonthlyPayments\Helpers\Logger::class);
        $this->data = [];
        $this->notice = new \Alma\MonthlyPayments\Block\Grid\Renderer\Notice(
            $this->context,
            $this->logger,
            $this->data
        );
    }

    public function testRenderForAlmaMethod()
    {
        $methods = \array_merge(
            \get_class_methods(Inbox::class),
            ['getTitle', 'getDescription']
        );
        $row = $this->getMockBuilder(Inbox::class)->setMethods($methods)->disableOriginalConstructor()->getMock();
        $row->method('getTitle')->willReturn('Alma insurance : <b>New Order #1234567890<b/>');
        $this->escaper->method('escapeHtml')->with($row->getTitle())->willReturn('Alma insurance : New Order #1234567890');

        $row->method('getDescription')->willReturn('<p>Description</p>');
        $this->assertEquals(
            '<span class="grid-row-title">Alma insurance : New Order #1234567890</span><br /><p>Description</p>',
            $this->notice->render($row)
        );
    }

    public function testRenderForNonAlmaMethod()
    {
        $methods = \array_merge(
            \get_class_methods(Inbox::class),
            ['getTitle', 'getDescription']
        );
        $row = $this->getMockBuilder(Inbox::class)->setMethods($methods)->disableOriginalConstructor()->getMock();

        $row->method('getTitle')->willReturn('Magento insurance : <b>New Order #1234567890<b/>');
        $row->method('getDescription')->willReturn('<p>Description</p>');

        $this->escaper
            ->method('escapeHtml')
            ->withConsecutive([$row->getTitle()],[$row->getDescription()])
            ->willReturnOnConsecutiveCalls('Magento insurance : New Order #1234567890','Description',);
        $this->assertEquals(
            '<span class="grid-row-title">Magento insurance : New Order #1234567890</span><br />Description',
            $this->notice->render($row)
        );
    }
}
