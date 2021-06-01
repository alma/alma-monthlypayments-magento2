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

/**
 * Class APIModes
 */
class ProductWidgetPositions implements \Magento\Framework\Option\ArrayInterface
{
    const POS_AFTER_TITLE = 'catalog.product.view.after.title.alma.widget';
    const POS_BEFORE_PRICE = 'catalog.product.view.before.price.alma.widget';
    const POS_BEFORE_STOCK = 'catalog.product.view.before.stock.alma.widget';
    const POS_AFTER_PRICE = 'catalog.product.view.after.stock.alma.widget';
    const POS_AFTER_INFO = 'catalog.product.view.after.info.alma.widget';
    const POS_BEFORE_ADDTOCART = 'catalog.product.view.before.addtocart.alma.widget';
    const POS_AFTER_ADDTOCART = 'catalog.product.view.after.addtocart.alma.widget';
    const POS_AFTER_ADDLINKS = 'catalog.product.view.after.addlinks.alma.widget';
    const POS_CUSTOM = 'catalog.product.view.custom.alma.widget';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => SELF::POS_AFTER_TITLE, 'label' => __('After Product Title')],
            ['value' => SELF::POS_BEFORE_PRICE, 'label' => __('Before Product Price')],
            ['value' => SELF::POS_BEFORE_STOCK, 'label' => __('Before Product Stock')],
            ['value' => SELF::POS_AFTER_PRICE, 'label' => __('After Product Stock')],
            ['value' => SELF::POS_AFTER_INFO, 'label' => __('After Product Informations')],
            ['value' => SELF::POS_BEFORE_ADDTOCART, 'label' => __('Before Product AddToCart Button')],
            ['value' => SELF::POS_AFTER_ADDTOCART, 'label' => __('After Product AddToCart Button')],
            ['value' => SELF::POS_AFTER_ADDLINKS, 'label' => __('After AddTo links')],
            ['value' => SELF::POS_CUSTOM, 'label' => __('Inside a custom HTML container')]
        ];
    }
}
