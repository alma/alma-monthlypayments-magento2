<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;

class SOCBlockLegal extends Template
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var array
     */
    private $data;

    /**
     * @param UrlInterface $url
     * @param Template\Context $context
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        UrlInterface $url,
        Template\Context $context,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlBuilder = $url;
        $this->logger = $logger;
        $this->data = $data;
    }

    /**
     * Getter for SOC legal block title
     *
     * @return string
     */
    public function getBlockTitle(): string
    {
        return __('Increase your performance & get insights with Alma');
    }
    /**
     * Getter for SOC legal block description
     *
     * @return string
     */
    public function getDescription(): string
    {
        $html = 'By accepting share of checkout option, enable Alma to analyse the usage of your payment methods, ';
        $html .= 'get more information to perform and share this data with you. ';
        $html .= 'You can <a href="mailto:support@getalma.eu" target="_blank">unsubscribe</a> ';
        $html .= 'and erase your data at any moment.';
        return __($html);
    }
    /**
     * Getter for SOC legal additional information title
     *
     * @return string
     */
    public function getDetailTitle(): string
    {
        return __('Know more about collected data');
    }
    /**
     * Getter for SOC legal additional information details <li>
     *
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
     * Getter for SOC legal link to config page title
     *
     * @return string
     */
    public function getLinkTitle(): string
    {
        return __("Go to the configuration page");
    }

    /**
     * Define if display go to config page link
     *
     * @return bool
     */
    public function hasLink(): bool
    {
        return $this->data['link'];
    }

    /**
     * Get message position
     *
     * @return string
     */
    public function getPosition(): string
    {
        return $this->data['position'];
    }
    /**
     * Getter for SOC legal link to config page url for href
     *
     * @return string
     */
    public function getConfigPageUrl(): string
    {
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/payment/', ['_secure' => true]);
    }
}
