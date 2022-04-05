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
        AlmaClient $almaClient,
        ApiConfigHelper $apiConfigHelper,
        Logger $logger
    )
    {
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

        return $this->isFullyConfigured() &&
            $this->isAvailableForCurrency($currencyCode);
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
     * @return bool
     */
    public function isFullyConfigured()
    {
        return $this->apiConfigHelper->isFullyConfigured();
    }

    /**
     * @param string | null $mode
     * @param string | null $apiKey
     * @param Merchant $merchant
     * @return bool
     */
    public function canConnectToAlma($mode = null, $apiKey = null, &$merchant = false): bool
    {
        if ($mode) {
            $modes = [$mode];
        } else {
            $modes = ['live', 'test'];
        }

        $keys = [
            'live' => $this->apiConfigHelper->getLiveKey(),
            'test' => $this->apiConfigHelper->getTestKey(),
        ];

        if ($apiKey) {
            if ($mode) {
                $keys[$mode] = $apiKey;
            } else {
                $keys = [
                    'live' => $apiKey,
                    'test' => $apiKey,
                ];
            }
        }

        foreach ($modes as $mode) {
            $key = $keys[$mode];

            $alma = $this->almaClient->createInstance($key, $mode);
            if (!$alma) {
                $this->logger->error("Could not create API client to check {$mode} API key");
                return false;
            }

            try {
                $merchant = $alma->merchants->me();
            } catch (RequestError $e) {
                if ($e->response && $e->response->responseCode === 401) {
                    return false;
                } else {
                    $this->logger->error("Error while connecting to Alma API: {$e->getMessage()}");
                    return false;
                }
            }
        }

        return true;
    }
}
