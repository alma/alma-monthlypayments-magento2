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

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Entities\Merchant;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceFlagException;
use Magento\Store\Model\StoreManagerInterface;

class Availability
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * Availability constructor.
     * @param StoreManagerInterface $storeManager
     * @param AlmaClient $almaClient
     * @param ApiConfigHelper $apiConfigHelper
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AlmaClient            $almaClient,
        ApiConfigHelper       $apiConfigHelper,
        Logger                $logger
    ) {
        $this->storeManager = $storeManager;
        $this->almaClient = $almaClient;
        $this->logger = $logger;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAvailable()
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        // $countryCode = ??

        return $this->isAvailableForCurrency($currencyCode);
    }

    /**
     * @param $currencyCode
     * @return bool
     */
    public function isAvailableForCurrency($currencyCode)
    {
        // We only support Euros at the moment
        return $currencyCode === 'EUR';
    }

    /**
     * @param string $mode
     * @param string $apiKey
     * @return bool | Merchant
     */
    public function getMerchant(string $mode, string $apiKey)
    {
        try {
            return $this->almaClient->createInstance($apiKey, $mode)->merchants->me();
        } catch (AlmaClientException|RequestError $e) {
            $this->logger->error("Could not create API client to check API key", [$mode]);
            $this->logger->error("Exception message", [$e->getMessage()]);
            return false;
        }
    }

    /**
     * @return bool
     * @throws AlmaInsuranceFlagException
     */
    public function isMerchantInsuranceAvailable(): bool
    {
        try {
            $merchant = $this->almaClient->getDefaultClient()->merchants->me();
            return $merchant->cms_insurance ?? true;
        } catch (AlmaClientException|RequestError $e) {
            $this->logger->error("Exception message", [$e->getMessage()]);
            throw new AlmaInsuranceFlagException($e->getMessage(), $this->logger, 0, $e);
        }
    }
}
