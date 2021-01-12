<?php
/**
 * 2018-2020 Alma SAS
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
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2020 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */


namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\API\Entities\Merchant;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\Availability;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var ResourceConfig
     */
    private $resourceConfig;
    /**
     * @var Availability
     */
    private $availabilityHelper;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    public function __construct(
        Config $config,
        ResourceConfig $resourceConfig,
        Availability $availabilityHelper,
        TypeListInterface $cacheTypeList
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->availabilityHelper = $availabilityHelper;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
    }

    // Update the fully_configured flag depending on whether we can correctly connect to Alma with provided API keys
    // Get the merchant's ID at the same time, as it might be needed for frontend-initiated API calls
    public function execute(Observer $observer)
    {
        /** @var Merchant $merchant */
        $merchant = null;
        $fully_configured = (int) $this->availabilityHelper->canConnectToAlma(null, null, $merchant);

        if ($fully_configured && $merchant) {
            $configPath = $this->config->getFieldPath(Config::CONFIG_MERCHANT_ID);
            $this->resourceConfig->saveConfig($configPath, $merchant->id, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }

        if ($this->config->isFullyConfigured() !== $fully_configured) {
            $configPath = $this->config->getFieldPath(Config::CONFIG_FULLY_CONFIGURED);
            $this->resourceConfig->saveConfig($configPath, $fully_configured, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

            $this->cacheTypeList->cleanType(CacheTypeConfig::TYPE_IDENTIFIER);
        }
    }
}
