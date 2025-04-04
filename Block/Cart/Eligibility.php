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

use Alma\MonthlyPayments\Helpers\Availability;
use Magento\Framework\View\Element\Template;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\View\Element\Template\Context;

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
     * @var Logger
     */
    private $logger;

    /**
     * Eligibility constructor.
     * @param Context $context
     * @param Config $config
     * @param Helpers\Eligibility $eligibilityHelper
     * @param Availability $availabilityHelper
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Template\Context     $context,
        Config               $config,
        Helpers\Eligibility  $eligibilityHelper,
        Helpers\Availability $availabilityHelper,
        Logger               $logger,
        array                $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
        $this->config = $config;
        $this->eligibilityHelper = $eligibilityHelper;
        $this->checkEligibility();
        $this->availabilityHelper = $availabilityHelper;
    }

    /**
     * @return void
     */
    public function checkEligibility(): void
    {
        try {
            $this->eligibilityHelper->checkEligibility();
        } catch (\Exception $e) {
            $this->logger->error('Check Eligibility Exception : ', [$e->getMessage()]);
        }
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

    public function hasEnabledPaymentPlansInBo()
    {
        return $this->eligibilityHelper->hasEnabledPaymentPlansInBo();
    }
}
