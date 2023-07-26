<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\LiveAPIKeyValue;
use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\TestAPIKeyValue;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class InPageAllowed extends Field
{
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var LiveAPIKeyValue
     */
    private $liveAPIKeyValue;
    /**
     * @var TestAPIKeyValue
     */
    private $testAPIKeyValue;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ApiConfigHelper $apiConfigHelper
     * @param LiveAPIKeyValue $liveAPIKeyValue
     * @param TestAPIKeyValue $testAPIKeyValue
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Context $context,
        ConfigHelper $configHelper,
        ApiConfigHelper $apiConfigHelper,
        LiveAPIKeyValue $liveAPIKeyValue,
        TestAPIKeyValue $testAPIKeyValue,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiConfigHelper = $apiConfigHelper;
        $this->liveAPIKeyValue = $liveAPIKeyValue;
        $this->testAPIKeyValue = $testAPIKeyValue;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }
    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $path = $this->liveAPIKeyValue->getMerchantIsAllowedInPagePath();
        if ('test' === $this->apiConfigHelper->getActiveMode()) {
            $path = $this->testAPIKeyValue->getMerchantIsAllowedInPagePath();
        }
        $this->logger->info('Path', [$path]);
        $inPageIsAllowed = $this->configHelper->getConfigByCode($path);
        $this->logger->info('in Page is Allowed', [$inPageIsAllowed]);
        if (!$inPageIsAllowed) {
            $element->setReadonly(true, true);
            $element->setComment(__("Interested in this feature? Reach out to support@almapay.com to gain access."));
        }
        return $element->getElementHtml();
    }
}
