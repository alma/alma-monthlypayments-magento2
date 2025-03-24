<?php

namespace Alma\MonthlyPayments\Model\Adminhtml\Messages;

use Alma\MonthlyPayments\Block\Adminhtml\System\SOCBlockLegal;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\View\LayoutFactory;

/**
 * Add a message in admin back office for SOC validation.
 */
class SOCMessage implements MessageInterface
{
    public const POSITION = 'message';
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @var SOCHelper
     */
    private $socHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @param LayoutFactory $layoutFactory
     * @param ApiConfigHelper $apiConfigHelper
     * @param SOCHelper $socHelper
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        ApiConfigHelper $apiConfigHelper,
        SOCHelper $socHelper
    ) {
        $this->layoutFactory = $layoutFactory;
        $this->socHelper = $socHelper;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * Return message ID
     *
     * @return string
     */
    public function getIdentity(): string
    {
        return 'soc_notification';
    }

    /**
     * Define the display conditions
     *
     * @return bool
     */
    public function isDisplayed(): bool
    {
        return (
            $this->socHelper->getSelectorValue() === SOCHelper::SELECTOR_NOT_SET &&
            $this->apiConfigHelper->getActiveMode() === ApiConfigHelper::LIVE_MODE_KEY
        );
    }

    /**
     * Défine Text message with a block for data and a template
     *
     * @return string
     */
    public function getText(): string
    {
        $layout = $this->layoutFactory->create();
        $blockOption = $layout->createBlock(
            SOCBlockLegal::class,
            '',
            ['data'=>[
                'link' => true,
                'position' => self::POSITION
            ]]
        )->setTemplate("Alma_MonthlyPayments::system/soc-legal.phtml");
        return $blockOption->toHtml();
    }

    /**
     * Return message severity
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_CRITICAL;
    }
}
