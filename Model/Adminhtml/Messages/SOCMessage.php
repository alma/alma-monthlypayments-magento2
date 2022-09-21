<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Messages;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\View\LayoutFactory;

/**
 * Add a message in admin back office for SOC validation.
 */
class SOCMessage implements MessageInterface
{
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @var SOCHelper
     */
    private $socHelper;

    /**
     * @param LayoutFactory $layoutFactory
     * @param SOCHelper $socHelper
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        SOCHelper $socHelper
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->socHelper = $socHelper;
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
        return $this->socHelper->getSelectorValue() === SOCHelper::SELECTOR_NOT_SET;
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
