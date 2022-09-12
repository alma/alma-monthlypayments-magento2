<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Messages;

use Alma\MonthlyPayments\Helpers\ShareOfCheckout\ShareOfCheckoutHelper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\View\LayoutFactory;

class SocMessage implements MessageInterface
{
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @var ShareOfCheckoutHelper
     */
    private $shareOfCheckoutHelper;

    public function __construct(
        LayoutFactory $layoutFactory,
        ShareOfCheckoutHelper $shareOfCheckoutHelper
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->shareOfCheckoutHelper = $shareOfCheckoutHelper;
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
        if ($this->shareOfCheckoutHelper->getShareOfCheckoutSelectorValue() === 2) {
            return true;
        }
        return false;
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