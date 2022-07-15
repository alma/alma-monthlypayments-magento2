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

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\ConfigHelper;
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
     * @var Logger
     */
    private $logger;

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
     * @param Logger $logger
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
        Logger $logger,
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
        $this->logger = $logger;
    }

    /**
     * @return Phrase
     */
    public function getApiKeyName(): Phrase
    {
        return __('API key');
    }

    /**
     * @return void
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();
        if (
            !$this->hasDataChanges() ||
            preg_match('/^\*+$/', $value)
        ) {
            $this->disallowDataSave();
            return;
        }
        // Clean api key value by saving empty value
        $merchant = $this->availabilityHelper->getMerchant($this->apiKeyType, $value);
        if (empty($value) || $merchant) {
            $this->saveAndEncryptValue();
            $this->configHelper->saveMerchantId($this->merchantIdPath, $merchant, $this->getScope(), $this->getScopeId());
            $this->changeApiModeToTest($value);
        } else {
            $this->disallowDataSave();
            $this->messageManager->addErrorMessage(
                sprintf(
                    __("Error checking %s - other configuration has been saved"),
                    __($this->getApiKeyName())
                )
            );
        }
    }

    /**
     * @param string $value
     *
     */
    public function changeApiModeToTest(string $value): void
    {
        if ($this->isValueChanged() && empty($value) && $this->apiKeyType == 'live') {
            $this->configHelper->apiTestMode($this->getScope(), $this->getScopeId());
        }
    }

    /**
     * @return void
     */
    protected function saveAndEncryptValue(): void
    {
        parent::beforeSave();
    }

    protected function disallowDataSave(): void
    {
        $this->_dataSaveAllowed = false;
    }
}
