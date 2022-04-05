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

namespace Alma\MonthlyPayments\Block\Adminhtml\Form;

use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

/**
 * Class SecondaryFieldset
 * @package Alma\MonthlyPayments\Block\System\Config\Form
 *
 * Will only render if the API has been correctly configured.
 *
 */
class SecondaryFieldset extends Fieldset
{
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * SecondaryFieldset constructor.
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ApiConfigHelper $apiConfigHelper,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        if (!$this->apiConfigHelper->isFullyConfigured()) {
            return "";
        }

        return parent::render($element);
    }
}
