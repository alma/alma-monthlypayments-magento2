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

namespace Alma\MonthlyPayments\Plugin\Checkout\CustomerData;

use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers;
use Magento\Store\Model\StoreManagerInterface;

class CartEligibility
{
    /**
     * @var Helpers\Eligibility
     */
    private $eligibilityHelper;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Helpers\Logger
     */
    private $logger;
    /**
     * @var Helpers\Availability
     */
    private $availabilityHelper;

    /**
     * @param Helpers\Eligibility $eligibilityHelper
     * @param Config $config
     * @param Helpers\Logger $logger
     * @param Helpers\Availability $availabilityHelper
     */
    public function __construct(
        Helpers\Eligibility $eligibilityHelper,
        Config $config,
        Helpers\Logger $logger,
        Helpers\Availability $availabilityHelper
    ) {
        $this->eligibilityHelper = $eligibilityHelper;
        $this->config = $config;
        $this->logger = $logger;
        $this->availabilityHelper = $availabilityHelper;
    }

    /**
     * Add eligibility information to result
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        try {
            $this->eligibilityHelper->checkEligibility();
        } catch (\Exception $e) {
            $this->logger->warning("Error checking for cart eligibility in minicart: {$e->getMessage()}");
            return $result;
        }

        $result['eligibility'] = [
            'eligible' => $this->eligibilityHelper->isEligible(),
            'message'  => $this->eligibilityHelper->getMessage(),
            'display'  => $this->config->showEligibilityMessage() && $this->shouldDisplay(),
        ];

        return $result;
    }

    private function shouldDisplay() {
        return $this->availabilityHelper->isAvailable();
    }
}
