<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;

class SocBlockLegal extends Template
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        UrlInterface $url,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlBuilder = $url;
    }

    /**
     * @description Getter for SOC legal block title
     * @return string
     */
    public function getBlockTitle(): string
    {
        return __('Increase your performance & get insights !');
    }
    /**
     * @description Getter for SOC legal block description
     * @return string
     */
    public function getDescription(): string
    {
        return __('By accepting share of checkout option, enable Alma to analyse the usage of your payment methods, get more informations to perform and share this data with you. You can unsubscribe and erase your data at any moment.');
    }
    /**
     * @description Getter for SOC legal additional information title
     * @return string
     */
    public function getDetailTitle(): string
    {
        return __('Know more about collected data');
    }
    /**
     * @description Getter for SOC legal additional information details <li>
     * @return array
     */
    public function getDetailsArgs(): array
    {
        return [
            __('total quantity of orders, amounts and currencies'),
            __('payment provider for each order')
        ];
    }
    /**
     * @description Getter for SOC legal link to config page title
     * @return string
     */
    public function getLinkTitle(): string
    {
        return __("Go to configuration page");
    }
    /**
     * @description Getter for SOC legal link to config page url for href
     * @return string
     */
    public function getConfigPageUrl(): string
    {
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/payment/', ['_secure' => true]);
    }


}