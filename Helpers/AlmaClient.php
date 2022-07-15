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

use Alma\API\Client;
use Alma\MonthlyPayments\Helpers\Exceptions\AlmaClientException;
use Exception;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class AlmaClient
{
    /** @var Client */
    private $alma;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * AlmaClient constructor.
     *
     * @param Logger $logger
     * @param ProductMetadataInterface $productMetadata
     * @param ApiConfigHelper $apiConfigHelper
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Logger $logger,
        ProductMetadataInterface $productMetadata,
        ApiConfigHelper $apiConfigHelper,
        ModuleListInterface $moduleList
    ) {
        $this->alma = null;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     *
     * @return Client
     *
     * @throws AlmaClientException
     */
    public function getDefaultClient(): Client
    {
        if ($this->alma === null) {
            $this->alma = $this->createInstance($this->apiConfigHelper->getActiveAPIKey(), $this->apiConfigHelper->getActiveMode());
        }

        return $this->alma;
    }

    /**
     * @param $apiKey
     * @param $mode
     *
     * @throws AlmaClientException
     *
     * @return Client
     */
    public function createInstance($apiKey, $mode): Client
    {
        $alma = null;
        if (empty($apiKey)) {
            throw new AlmaClientException("No Api Key in {$mode} mode");
        }
        try {
            $alma = new Client($apiKey, ['mode' => $mode, 'logger' => $this->logger]);

            $edition = $this->productMetadata->getEdition();
            $version = $this->productMetadata->getVersion();
            $alma->addUserAgentComponent('Magento', "$version ($edition)");
            $alma->addUserAgentComponent("Alma for Magento 2", $this->getModuleVersion());
        } catch (Exception $e) {
            $this->logger->error("Error creating Alma API client", [$e->getMessage()]);
        }
        if (!$alma) {
            $errorMessage = 'Impossible to create Alma client';
            $this->logger->error($errorMessage, []);
            throw new AlmaClientException($errorMessage);
        }
        return $alma;
    }

    /**
     * @return mixed
     */
    private function getModuleVersion()
    {
        $moduleInfo = $this->moduleList->getOne('Alma_MonthlyPayments');
        return $moduleInfo['setup_version'];
    }
}
