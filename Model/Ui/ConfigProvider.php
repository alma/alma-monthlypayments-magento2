<?php
/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Model\Ui;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Eligibility;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Alma\MonthlyPayments\Helpers\Logger;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Eligibility
     */
    private $eligibilityHelper;
    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * ConfigProvider constructor.
     * @param CheckoutSession $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param Eligibility $eligibilityHelper
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        Config $config,
        Eligibility $eligibilityHelper,
        ResolverInterface $localeResolver,
        Logger $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->eligibilityHelper = $eligibilityHelper;
        $this->localeResolver = $localeResolver;
        $this->logger = $logger;
    }

    /**
     * @return \array[][]
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                Config::CODE => [
                    'redirectTo' => $this->urlBuilder->getUrl('alma/payment/pay'),
                    'title' => $this->config->getPaymentButtonTitle(),
                    'description' => $this->config->getPaymentButtonDescription(),
                    'sortOrder' => $this->config->getSortOrder(),
                    'locale' => str_replace('_', '-', $this->localeResolver->getLocale()),
                    'paymentPlans' => array_map(function ($pe) {
                        $planConfig = $pe->getPlanConfig();

                        $plan = $planConfig->toArray();
                        $plan['key'] = $planConfig->planKey();
                        $plan['logo'] = $planConfig->logoFileName();
                        $plan['paymentPlan'] = $pe->getEligibility()->getPaymentPlan();

                        // TODO : we need to take only customerTotalCostAmount and annualInterestRate
                        $plan['eligibility'] = $pe->getEligibility();

                        return $plan;
                    }, $this->eligibilityHelper->getEligiblePlans())
                ]
            ]
        ];
        $this->logger->info('Paymenbt Provider Config',[$config]);
        return $config;
    }
}
