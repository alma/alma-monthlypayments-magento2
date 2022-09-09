<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Messages;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\View\LayoutFactory;

class SocMessage implements MessageInterface
{
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    public function __construct(
        LayoutFactory $layoutFactory
    ) {
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return 'soc_notification';
    }

    /**
     * @return bool
     */
    public function isDisplayed(): bool
    {
        // write code to decide if this message should be shown or not
        // return true to show it, false otherwise
        return true;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        $layout = $this->layoutFactory->create();
        $blockOption = $layout->createBlock("Alma\MonthlyPayments\Block\Adminhtml\System\SocLegal")->setTemplate("Alma_MonthlyPayments::system/soc-legal.phtml");
        return $blockOption->toHtml();
    }

    /**
     * @return int
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_CRITICAL;
    }

}