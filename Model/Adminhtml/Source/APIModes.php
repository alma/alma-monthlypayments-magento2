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

namespace Alma\MonthlyPayments\Model\Adminhtml\Source;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class APIModes
 */
class APIModes implements ArrayInterface
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Logger $logger
     * @param ApiConfigHelper $apiConfigHelper
     */
    public function __construct(
        Logger $logger,
        RequestInterface $request,
        ApiConfigHelper $apiConfigHelper
    ) {
        $this->logger = $logger;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $arrayResult = [];
        $arrayResult[] = ['value' => 'test', 'label' => __('Test')];

        $type = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $id = 0;
        $store = $this->request->getParam('store');
        $website = $this->request->getParam('website');

        if ($store) {
            $type = ScopeInterface::SCOPE_STORES;
            $id = $store;
        } elseif ($website) {
            $type = ScopeInterface::SCOPE_WEBSITES;
            $id = $website;
        }

        if ($this->apiConfigHelper->getLiveKey($type, $id)) {
            $arrayResult[] = ['value' => 'live', 'label' => __('Live')];
        }
        return $arrayResult;
    }
}
