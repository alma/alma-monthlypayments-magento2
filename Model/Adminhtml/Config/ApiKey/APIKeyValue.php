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

use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Exception\LocalizedException;

class APIKeyValue extends \Magento\Config\Model\Config\Backend\Encrypted
{
    protected $apiKeyType = null;

    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Availability
     */
    private $availabilityHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        AlmaClient $almaClient,
        Logger $logger,
        Availability $availabilityHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $encryptor, $resource, $resourceCollection,
            $data);
        $this->almaClient = $almaClient;
        $this->logger = $logger;
        $this->availabilityHelper = $availabilityHelper;
    }

    public function getApiKeyName()
    {
        return __('API key');
    }

    /**
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();

        if (empty($value)) {
            throw new LocalizedException(__('API key is required'));
        }

        $genericError = new LocalizedException(__("Error checking {$this->getApiKeyName()}"));

        // don't try value, if an obscured value was received. This indicates that data was not changed.
        if (!preg_match('/^\*+$/', $value) && !$this->availabilityHelper->canConnectToAlma($this->apiKeyType, $value)) {
            throw $genericError;
        }

        parent::beforeSave();
    }

    public function afterSave()
    {
        $result = parent::afterSave();
        $this->_eventManager->dispatch('alma_saved_api_key', ['mode' => $this->apiKeyType]);
        return $result;
    }
}
