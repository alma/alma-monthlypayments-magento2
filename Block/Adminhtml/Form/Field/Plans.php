<?php
/**
 * 2018-2021 Alma SAS
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
 * @copyright 2018-2021 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Serialize\Serializer\Json;
use Alma\MonthlyPayments\Helpers\Logger;

class Plans extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Alma_MonthlyPayments::form/field/plans.phtml';

    /**
     * @param Context $context
     * @param array $data
         * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Logger $logger ,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_toHtml();
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getElement()->getValue();
    }

    /**
     * @return array
     */
    private function getPlans()
    {
        // Exclude 1-installment plans that are not deferred
        $plans = array_filter($this->getValue()->getPlans(), function ($plan) {
            return $plan->isDeferred() || $plan->installmentsCount() > 1;
        });

        uksort($plans, function ($k1, $k2) use ($plans) {
            /** @var PaymentPlanConfig $p1 */
            $p1 = $plans[$k1];
            /** @var PaymentPlanConfig $p2 */
            $p2 = $plans[$k2];

            // Deferred plans come last, with everything ordered by installments count or deferred duration
            if ($p1->isDeferred() && $p2->isDeferred() && $p1->installmentsCount() == $p2->installmentsCount()) {
                // Most deferred plans will have installmentsCount == 1 and be sorted on duration
                return $p1->deferredDurationInDays() - $p2->deferredDurationInDays();
            } elseif ($p1->isDeferred() || $p2->isDeferred()) {
                // Only one of the two plans is deferred: it should come last
                return $p1->isDeferred() ? 1 : -1;
            }
            // Both plans are "regular" plans: sort them by installments count
            return $p1->installmentsCount() - $p2->installmentsCount();
        });

        return $plans;
    }

    /**
     * @return bool|string
     */
    public function getPlansJson()
    {
        $serializer = new Json();
        return $serializer->serialize(
            array_map(
            // Map each plan config to its data array and add useful information for rendering
                function ($plan) {
                    $data = $plan->toArray();

                    $data['deferredType'] = $plan->deferredType();
                    $data['deferredDuration'] = $plan->deferredDuration();

                    return $data;
                },
                $this->getPlans()
            )
        );
    }
}
