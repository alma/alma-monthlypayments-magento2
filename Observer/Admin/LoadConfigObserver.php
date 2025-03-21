<?php

namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\API\Lib\IntegrationsConfigurationsUtils;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LoadConfigObserver implements ObserverInterface
{
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;
    /**
     * @var CollectCmsConfigHelper
     */
    private $collectCmsConfigHelper;

    public function __construct(
        UrlInterface           $url,
        PaymentPlansHelper     $paymentPlansHelper,
        CollectCmsConfigHelper $collectCmsConfigHelper
    ) {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
        $this->collectCmsConfigHelper = $collectCmsConfigHelper;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): self
    {
        $currentUrl = $this->url->getCurrentUrl();
        if (preg_match('!section\/(payment)!', $currentUrl)) {
            $this->paymentPlansHelper->saveBaseApiPlansConfig();
            # Send the data collect URL to Alma if necessary
            if (IntegrationsConfigurationsUtils::isUrlRefreshRequired((int)$this->collectCmsConfigHelper->getSendCollectUrlStatus())) {
                $this->collectCmsConfigHelper->sendIntegrationsConfigurationsUrl();
            }
        }
        return $this;
    }
}
