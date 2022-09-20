<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Messages;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\View\LayoutFactory;

class SOCMessage implements MessageInterface
{
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @var SOCHelper
     */
    private $SOCHelper;

    /**
     * @param LayoutFactory $layoutFactory
     * @param SOCHelper $SOCHelper
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        SOCHelper $SOCHelper
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->SOCHelper = $SOCHelper;
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
        return $this->SOCHelper->getSelectorValue() === SOCHelper::SELECTOR_NOT_SET;
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