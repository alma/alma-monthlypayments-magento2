<?php

namespace Alma\MonthlyPayments\Observer\Admin;

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

    public function __construct(
        UrlInterface $url,
        PaymentPlansHelper $paymentPlansHelper
    ) {
        $this->url = $url;
        $this->paymentPlansHelper = $paymentPlansHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (preg_match('!section\/payment!', $this->url->getCurrentUrl())) {
            $this->paymentPlansHelper->saveBaseApiPlanConfig();
        }
    }
}
