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
use Alma\MonthlyPayments\Helpers\StoreHelper;
use Magento\Framework\Option\ArrayInterface;

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
     * @var StoreHelper
     */
    private $storeHelper;

    /**
     * @param Logger $logger
     * @param ApiConfigHelper $apiConfigHelper
     * @param StoreHelper $storeHelper
     */
    public function __construct(
        Logger $logger,
        ApiConfigHelper $apiConfigHelper,
        StoreHelper $storeHelper
    ) {
        $this->logger = $logger;
        $this->apiConfigHelper = $apiConfigHelper;
        $this->storeHelper = $storeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $arrayResult = [];
        $arrayResult[] = ['value' => 'test', 'label' => __('Test')];

        $type = $this->storeHelper->getScope();
        $id = $this->storeHelper->getStoreId();
        $this->logger->info('toOptionArray ', [$type]);
        $this->logger->info('toOptionArray ', [$id]);

        if ($this->apiConfigHelper->getLiveKey()) {
            $arrayResult[] = ['value' => 'live', 'label' => __('Live')];
        }
        return $arrayResult;
    }
}
