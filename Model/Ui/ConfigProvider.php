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
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Alma\MonthlyPayments\Helpers\Logger;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ResolverInterface
     */
    private $localeResolver;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * ConfigProvider constructor.
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Config $config,
        ResolverInterface $localeResolver,
        ConfigHelper $configHelper,
        Logger $logger
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
    }

    /**
     * @return \array[][]
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Config::CODE => [
                    'redirectTo' => $this->urlBuilder->getUrl('alma/payment/pay'),
                    'title' => __($this->config->getPaymentButtonTitle()),
                    'description' => __($this->config->getPaymentButtonDescription()),
                    'triggerEnable' => __($this->configHelper->triggerIsEnabled()),
                    'triggerLabel' => __($this->configHelper->getTrigger()),
                    'sortOrder' => $this->config->getSortOrder(),
                    'locale' => str_replace('_', '-', $this->localeResolver->getLocale())
                ]
            ]
        ];
    }
}
