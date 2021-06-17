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

namespace Alma\MonthlyPayments\Block\Cart;

use Magento\Framework\View\Element\Template;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers;

class Eligibility extends Template
{
    /** @var Config */
    private $config;

    /**
     * @var Helpers\Eligibility
     */
    private $eligibilityHelper;
    /**
     * @var Helpers\Availability
     */
    private $availabilityHelper;

    /**
     * Eligibility constructor.
     * @param Template\Context $context
     * @param Config $config
     * @param Helpers\Eligibility $eligibilityHelper
     * @param Helpers\Availability $availabilityHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        Helpers\Eligibility $eligibilityHelper,
        Helpers\Availability $availabilityHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->config = $config;
        $this->eligibilityHelper = $eligibilityHelper;

        $this->checkEligibility();
        $this->availabilityHelper = $availabilityHelper;
    }

    public function checkEligibility()
    {
        $this->eligibilityHelper->checkEligibility();
    }

    public function isEligible()
    {
        return $this->eligibilityHelper->isEligible();
    }

    public function showEligibilityMessage()
    {
        return $this->shouldDisplay() && $this->config->showEligibilityMessage();
    }

    public function getEligibilityMessage()
    {
        return $this->eligibilityHelper->getMessage();
    }

    public function shouldDisplay()
    {
        return $this->availabilityHelper->isAvailable();
    }
}
