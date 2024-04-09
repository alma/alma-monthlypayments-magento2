<?php

/**
 * 2018-2021 Alma / Nabla SAS
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
 * @copyright 2018-2021 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

class Insurance extends Fieldset
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param InsuranceHelper $insuranceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context      $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js   $jsHelper,
        InsuranceHelper                     $insuranceHelper,
        array                               $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->insuranceHelper = $insuranceHelper;
    }

    /**
     * Add custom css class
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getFrontendClass($element): string
    {
        return parent::_getFrontendClass($element);
    }

    /**
     * Return header title part of html for payment solution
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element): string
    {
        if ($this->insuranceHelper->getConfig()->isAllowed()) {
            return parent::_getHeaderTitleHtml($element);
        } else {
            $notAllowedComment = __('Insurance is not activated - if you are interested by Alma insurance please contact');
            return '<div>
                        <h1>Alma insurance Beta</h1>
                        <p>' . $notAllowedComment . '</p>
                    </div>';
        }
    }
}
