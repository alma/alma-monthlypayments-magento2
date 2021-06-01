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

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Availability;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class APIKeyValue extends Encrypted
{
    protected $apiKeyType = null;

    /**
     * @var AlmaClient
     */
    private $almaClient;

    /**
     * @var Availability
     */
    private $availabilityHelper;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var false
     */
    protected $hasError;

    /**
     * @var Config
     */
    private $almaConfig;

    /**
     * APIKeyValue constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param AlmaClient $almaClient
     * @param Availability $availabilityHelper
     * @param ResourceConfig $resourceConfig
     * @param MessageManager $messageManager
     * @param Config $almaConfig
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
        AlmaClient $almaClient,
        Availability $availabilityHelper,
        ResourceConfig $resourceConfig,
        MessageManager $messageManager,
        Config $almaConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
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

        $this->almaClient = $almaClient;
        $this->availabilityHelper = $availabilityHelper;
        $this->resourceConfig = $resourceConfig;
        $this->almaConfig = $almaConfig;
        $this->messageManager = $messageManager;

        $this->hasError = false;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getApiKeyName()
    {
        return __('API key');
    }

    public function beforeSave()
    {
        if (!$this->hasDataChanges()) {
            return;
        }

        // Force fully_configured to 0 – it will be switched to 1 by the ConfigObserver if both API keys are correct
        $configPath = $this->almaConfig->getFieldPath(Config::CONFIG_FULLY_CONFIGURED);
        $this->resourceConfig->saveConfig($configPath, 0, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        $value = (string)$this->getValue();

        if (empty($value)) {
            // If we throw a ValidatorException (or any Exception), the whole DB transaction will be rolled back and
            // our change to fully_configured above won't be saved, which is a problem; so we use the Message Manager
            // and prevent save using `_dataSaveAllowed`.
            $this->_dataSaveAllowed = false;
            $this->messageManager->addErrorMessage(__('API key is required'));
            return;
        }

        // don't try value, if an obscured value was received. This indicates that data was not changed.
        if (!preg_match('/^\*+$/', $value) && !$this->availabilityHelper->canConnectToAlma($this->apiKeyType, $value)) {
            $this->_dataSaveAllowed = false;
            $this->messageManager->addErrorMessage(
                sprintf(
                    __("Error checking %s - other configuration has been saved"),
                    __($this->getApiKeyName())
                )
            );
            return;
        }

        parent::beforeSave();
    }
}
