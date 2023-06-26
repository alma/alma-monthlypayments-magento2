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

namespace Alma\MonthlyPayments\Gateway\Validator;

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Helpers\Eligibility;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Alma\MonthlyPayments\Helpers\Logger;

class AvailabilityValidator extends AbstractValidator
{
    /**
     * @var Availability
     */
    private $availabilityHelper;
    /**
     * @var Eligibility
     */
    private $eligibilityHelper;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * AvailabilityValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Logger $logger
     * @param Availability $availabilityHelper
     * @param Eligibility $eligibilityHelper
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Logger $logger,
        Availability $availabilityHelper,
        Eligibility $eligibilityHelper
    ) {
        parent::__construct($resultFactory);
        $this->logger = $logger;
        $this->availabilityHelper = $availabilityHelper;
        $this->eligibilityHelper = $eligibilityHelper;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult($this->availabilityHelper->isAvailable() && $this->eligibilityHelper->checkEligibility());
    }
}
