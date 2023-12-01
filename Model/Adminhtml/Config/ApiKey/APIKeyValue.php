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

namespace Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
use Alma\MonthlyPayments\Helpers\ShareOfCheckout\SOCHelper;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;

class APIKeyValue extends Encrypted
{
    protected $apiKeyType = '';
    protected $merchantIdPath = '';
    protected $merchantIsAllowedInsurance = '';

    /**
     * @var Availability
     */
    private $availabilityHelper;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var false
     */
    protected $hasError;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * APIKeyValue constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param Availability $availabilityHelper
     * @param MessageManager $messageManager
     * @param ConfigHelper $configHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        Availability $availabilityHelper,
        MessageManager $messageManager,
        ConfigHelper $configHelper,
        ApiConfigHelper $apiConfigHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );

        $this->availabilityHelper = $availabilityHelper;
        $this->messageManager = $messageManager;
        $this->hasError = false;
        $this->configHelper = $configHelper;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * Return API key name
     *
     * @return Phrase
     */
    public function getApiKeyName(): Phrase
    {
        return __('API key');
    }

    /**
     * Action before save Api Key value
     *
     * @return void
     */
    public function beforeSave(): void
    {
        $value = $this->getValue();
        $valueIsStars = preg_match('/^\*+$/', $value);
        if (
            !$this->hasDataChanges()
            || $valueIsStars
        ) {
            $this->disallowDataSave();
            $value = $this->getSavedApiKeyByFieldMode();
        }

        // Clean api key value by saving empty value
        $merchant = $this->availabilityHelper->getMerchant($this->getApiKeyType(), $value);
        if (empty($value) || $merchant) {
            $this->saveMerchantData($merchant);

            if ($this->isValueChanged() && $this->getApiKeyType() == 'live') {
                $this->configHelper->deleteConfig(SOCHelper::ENABLE_KEY, $this->getScope(), $this->getScopeId());
                if (empty($value)) {
                    $this->configHelper->changeApiModeToTest($this->getScope(), $this->getScopeId());
                }
            }
            if (empty($value)) {
                $this->configHelper->deleteConfig($this->getMerchantIdPath(), $this->getScope(), $this->getScopeId());
            }

            if (!$valueIsStars) {
                $this->saveAndEncryptValue();
            }
            return;
        }
        $this->disallowDataSave();
        $this->messageManager->addErrorMessage(
            sprintf(
                __("Error checking %s - other configuration has been saved"),
                __($this->getApiKeyName())
            )
        );
    }

    /**
     * Save Merchant id
     *
     * @param $merchant
     * @return void
     */
    private function saveMerchantData($merchant):void
    {
        $this->configHelper->saveMerchantId(
            $this->getMerchantIdPath(),
            $merchant,
            $this->getScope(),
            $this->getScopeId()
        );
        $this->configHelper->saveIsAllowedInsurance(
            $this->getMerchantIsAllowedInsurance(),
            $merchant,
            $this->getScope(),
            $this->getScopeId()
        );
    }

    /**
     * {@inheritdoc}. Delete merchant ID with the API_KEY.
     *
     * @return APIKeyValue
     */
    public function afterDelete(): APIKeyValue
    {
        $this->configHelper->deleteConfig($this->merchantIdPath, $this->getScope(), $this->getScopeId());
        return parent::afterDelete();
    }

    /**
     * Encrypt and save value
     *
     * @return void
     */
    protected function saveAndEncryptValue(): void
    {
        parent::beforeSave();
    }

    /**
     * Change $this->_dataSaveAllowed flag to "false" for disallow save
     *
     * @return void
     */
    protected function disallowDataSave(): void
    {
        $this->_dataSaveAllowed = false;
    }

    /**
     * Return the api key type
     *
     * @return string
     */
    protected function getApiKeyType():string
    {
        return $this->apiKeyType;
    }

    /**
     * Return merchant id path for database save
     *
     * @return string
     */
    public function getMerchantIdPath(): string
    {
        return $this->merchantIdPath;
    }

    /**
     * Get saved Api key saved in DB for this field mode
     * @return string
     */
    private function getSavedApiKeyByFieldMode(): string
    {
        if ('live' === $this->getApiKeyType()) {
            return $this->apiConfigHelper->getLiveKey();
        }

        return $this->apiConfigHelper->getTestKey();
    }

    /**
     * @return string
     */
    public function getMerchantIsAllowedInsurance(): string
    {
        return $this->merchantIsAllowedInsurance;
    }
}
