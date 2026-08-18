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

namespace Alma\MonthlyPayments\Model\Adminhtml\Config\ApiUrl;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Data\ProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;

class ApiUrlValue extends Value implements ProcessorInterface
{
    protected $urlPath = null;
    protected $oldUrlPath = null;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * ApiUrlValue constructor.
     * @param Url $url
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param DeploymentConfig $deploymentConfig
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Url                  $url,
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        DeploymentConfig     $deploymentConfig,
        ?AbstractResource    $resource = null,
        ?AbstractDb          $resourceCollection = null,
        array                $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->url = $url;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function isMagentoInstalled(): bool
    {
        return $this->deploymentConfig->isAvailable();
    }

    /**
     * @inheritDoc
     */
    public function processValue($value): string
    {
        if (!$this->isMagentoInstalled()) {
            return (string) $value;
        }

        if (empty($value)) {
            $value = $this->url->getUrl(
                $this->urlPath,
                ['_nosid' => true, '_type' => UrlInterface::URL_TYPE_WEB]
            );
        }
        return $value;
    }

    /**
     * @return string
     */
    public function getOldDefaultUrl(): string
    {
        if (!$this->isMagentoInstalled()) {
            return '';
        }

        return $this->url->getUrl(
            $this->oldUrlPath,
            ['_nosid' => true, '_type' => UrlInterface::URL_TYPE_WEB]
        );
    }
}
