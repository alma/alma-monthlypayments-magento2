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

use Alma\MonthlyPayments\Gateway\Config\Config;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\View\Helper\Js;

/**
 * Class ApiFieldset
 * @package Alma\MonthlyPayments\Block\System\Config\Form
 *
 * Changes default collapsed state depending on whether the API has already been configured.
 *
 */
class ApiFieldset extends Fieldset
{
    /**
     * @var Config
     */
    private $config;

    /**
     * ApiFieldset constructor.
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->config = $config;
    }

    /**
     * Must return `true` for the group to be expanded by default – go figure.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isCollapseState($element): bool
    {
        return !$this->config->isFullyConfigured();
    }

    /**
     * When the API hasn't been configured yet, add information about other configuration settings being available only
     * after proper API configuration.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element): string
    {
        $comment = $element->getComment();
        if (!$this->config->isFullyConfigured()) {
            $comment .= '<br>' . __("Other configuration settings will be made available after you provide valid API keys.");
        }

        return $comment ? '<div class="comment">' . $comment . '</div>' : '';
    }
}
