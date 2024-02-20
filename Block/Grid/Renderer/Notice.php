<?php

namespace Alma\MonthlyPayments\Block\Grid\Renderer;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Context;

class Notice extends \Magento\AdminNotification\Block\Grid\Renderer\Notice
{
    private Logger $logger;

    public function __construct(
        Context $context,
        Logger  $logger,
        array   $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $this->logger->info('row', [$row]);
        if (preg_match('/^Alma/', $row->getTitle())) {
            return '<span class="grid-row-title">' .
                $this->escapeHtml($row->getTitle()) .
                '</span>' .
                ($row->getDescription() ? '<br />' . $row->getDescription() : '');
        }

        return parent::render($row);
    }
}
