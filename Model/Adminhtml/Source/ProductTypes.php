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

use Magento\Catalog\Model\ProductTypeList;

/**
 * Config product types source
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProductTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var ProductTypeList
     */
    private $productTypeList;

    /**
     * Construct
     *
     * @param ProductTypeList $productTypeList
     */
    public function __construct(
        ProductTypeList $productTypeList
    ) {
        $this->productTypeList = $productTypeList;
    }

    /**
     * Return option array
     *
     * @param bool $addEmpty
     * @return array
     */
    public function toOptionArray($addEmpty = true)
    {
        $types = $this->productTypeList->getProductTypes();

        $options = [];

        if ($addEmpty) {
            $options[] = ['label' => __('-- Please Select a Type --'), 'value' => ''];
        }

        foreach ($types as $type) {
            $options[] = ['label' => $type->getLabel(), 'value' => $type->getName()];
        }

        return $options;
    }
}
