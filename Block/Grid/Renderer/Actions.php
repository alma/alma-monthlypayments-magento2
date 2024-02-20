<?php

namespace Alma\MonthlyPayments\Block\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\Helper\Data;

class Actions extends \Magento\AdminNotification\Block\Grid\Renderer\Actions
{
    public function __construct(
        Context $context,
        Data    $urlHelper,
        array   $data = []
    )
    {
        parent::__construct($context, $urlHelper, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        if (preg_match('/^Alma/', $row->getTitle())) {
            $readDetailsHtml = $row->getUrl() ? '<a class="action-details" target="_blank" href="' .
                $this->escapeUrl($row->getUrl())
                . '">' .
                __('View Order') . '</a>' : '';

            $markAsReadHtml = !$row->getIsRead() ? '<a class="action-mark" href="' . $this->getUrl(
                    '*/*/markAsRead/',
                    ['_current' => true, 'id' => $row->getNotificationId()]
                ) . '">' . __(
                    'Mark as Read'
                ) . '</a>' : '';

            $encodedUrl = $this->_urlHelper->getEncodedUrl();
            return sprintf(
                '%s%s<a class="action-delete" href="%s" onClick="deleteConfirm(\'%s\', this.href); return false;">%s</a>',
                $readDetailsHtml,
                $markAsReadHtml,
                $this->getUrl(
                    '*/*/remove/',
                    [
                        '_current' => true,
                        'id' => $row->getNotificationId(),
                        ActionInterface::PARAM_NAME_URL_ENCODED => $encodedUrl
                    ]
                ),
                __('Are you sure?'),
                __('Remove')
            );
        }

        return parent::render($row);
    }

}
