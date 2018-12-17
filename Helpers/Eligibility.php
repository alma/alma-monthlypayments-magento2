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

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Helpers;
use Alma\MonthlyPayments\Model\Data\Quote as AlmaQuote;

class Eligibility
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;
    /**
     * @var Client
     */
    private $alma;
    /**
     * @var Logger
     */
    private $logger;

    /** @var bool */
    private $eligible;

    /** @var string */
    private $message;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        Helpers\AlmaClient $almaClient,
        Helpers\Logger $logger
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->pricingHelper = $pricingHelper;
        $this->logger = $logger;

        $this->alma = $almaClient->getDefaultClient();
    }

    /**
     * @param $eligibilityMessage string Message to display when Quote is eligible for monthly payments
     * @param $nonEligibilityMessage string Message to display when Quote is not eligible for monthly payments
     * @return bool
     */
    public function checkEligibility($eligibilityMessage, $nonEligibilityMessage) {
        if (!$this->alma) {
            return false;
        }

        $this->message = $eligibilityMessage;
        $cartTotal = Helpers\Functions::priceToCents((float)$this->checkoutSession->getQuote()->getGrandTotal());

        try {
            $eligibility = $this->alma->payments->eligibility(AlmaQuote::dataFromQuote($this->checkoutSession->getQuote()));
        } catch (RequestError $e) {
            $this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
            $this->message = $nonEligibilityMessage;
            return false;
        }

        if (!$eligibility->isEligible) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            try {
                $merchant = $this->alma->merchants->me();
            } catch (RequestError $e) {
                $this->logger->error("Error fetching merchant information: {$e->getMessage()}");
            }

            if (isset($merchant) && $merchant) {
                $minAmount = $merchant->minimum_purchase_amount;
                $maxAmount = $merchant->maximum_purchase_amount;

                if ($cartTotal < $minAmount || $cartTotal > $maxAmount) {
                    if ($cartTotal > $maxAmount) {
                        $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($maxAmount));
                        $this->message .= '<br>' . sprintf(__('(Maximum amount: %s)'), $price);
                    } else {
                        $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($minAmount));
                        $this->message .= '<br>' . sprintf(__('(Minimum amount: %s)'), $price);
                    }
                }
            }
        } else {
            $this->eligible = true;
        }

        return true;
    }

    public function isEligible()
    {
        return $this->eligible;
    }

    public function getMessage()
    {
        return $this->message;
    }

    private function getFormattedPrice($price)
    {
        return $this->pricingHelper->currency($price, true, false);
    }
}
