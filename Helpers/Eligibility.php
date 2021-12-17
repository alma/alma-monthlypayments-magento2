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
use Alma\API\Endpoints\Results\Eligibility as AlmaEligibility;
use Alma\API\RequestError;
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Helpers;
use Alma\MonthlyPayments\Model\Data\PaymentPlanEligibility;
use Alma\MonthlyPayments\Model\Data\Quote as AlmaQuote;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;

class Eligibility
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Data
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
    /**
     * @var Config
     */
    private $config;
    /**
     * @var AlmaQuote
     */
    private $quoteData;

    /**
     * Eligibility constructor.
     * @param Session $checkoutSession
     * @param Data $pricingHelper
     * @param AlmaClient $almaClient
     * @param Logger $logger
     * @param Config $config
     * @param AlmaQuote $quoteData
     */
    public function __construct(
        Session $checkoutSession,
        Data $pricingHelper,
        Helpers\AlmaClient $almaClient,
        Helpers\Logger $logger,
        Config $config,
        AlmaQuote $quoteData
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->pricingHelper = $pricingHelper;
        $this->logger = $logger;
        $this->alma = $almaClient->getDefaultClient();
        $this->config = $config;
        $this->quoteData = $quoteData;
    }

    /**
     * @return PaymentPlanEligibility[]
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RequestError
     */
    public function getPlansEligibility(): array
    {
        if (!$this->alma || !$this->checkItemsTypes()) {
            return [];
        }

        $cartTotal = Functions::priceToCents((float)$this->checkoutSession->getQuote()->getGrandTotal());

        // Get enabled plans and build a list of installments counts that should be tested for eligibility
        $enabledPlans = $this->config->getPaymentPlansConfig()->getEnabledPlans();
        $installmentsQuery = [];
        $queriedPlans = [];
        $plansEligibility = [];
        foreach ($enabledPlans as $planKey => $planConfig) {
            if (
                $cartTotal < $planConfig->minimumAmount() ||
                $cartTotal > $planConfig->maximumAmount()
            ) {
                // If cart total is out of this plan's configured bounds, we know it's not eligible right away
                $eligibility = new AlmaEligibility(
                    [
                        'installments_count' => $planConfig->installmentsCount(),
                        'eligible' => false,
                        'constraints' => [
                            'purchase_amount' => [
                                'minimum' => $planConfig->minimumAmount(),
                                'maximum' => $planConfig->maximumAmount(),
                            ],
                        ],
                        'reasons' => [
                            'purchase_amount' => 'invalid_value'
                        ]
                    ]
                );

                $plansEligibility[] = new PaymentPlanEligibility($planConfig, $eligibility);
            } else {
                // Query eligibility for the plan's installments count & keep track of which plans are queried
                $installmentsQuery[] = [
                    'purchase_amount' => $cartTotal,
                    'installments_count' => $planConfig->installmentsCount(),
                    'deferred_days' => $planConfig->deferredDays(),
                    'deferred_month' => $planConfig->deferredMonths(),
                    'cart_total' => $cartTotal
                ];
                $queriedPlans[] = $planKey;

                // Insert plan key into the "final" result array so that we can replace it with its actual eligibility
                // result after the API call is made
                $plansEligibility[] = $planKey;
            }
        }

        $eligibilities = [];

        if (!empty($installmentsQuery)) {
            $data = $this->quoteData->eligibilityDataFromQuote(
                $this->checkoutSession->getQuote(),
                $installmentsQuery
            );
            $eligibilities = $this->alma->payments->eligibility(
                $data,
                true
            );
        }

        $queriedEligibilities = [];
        foreach (array_values($eligibilities) as $idx => $eligibility) {
            $key = $queriedPlans[$idx];
            $planConfig = $enabledPlans[$key];
            $queriedEligibilities[$key] = new PaymentPlanEligibility($planConfig, $eligibility);
        }

        return array_map(function ($planOrKey) use ($queriedEligibilities) {
            return is_string($planOrKey) ? $queriedEligibilities[$planOrKey] : $planOrKey;
        }, $plansEligibility);
    }

    /**
     * @return bool
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * TODO : Do not check Eligibility when cart is empty
     */
    public function checkEligibility()
    {
        $eligibilityMessage = $this->config->getEligibilityMessage();
        $nonEligibilityMessage = $this->config->getNonEligibilityMessage();
        $excludedProductsMessage = $this->config->getExcludedProductsMessage();

        if (!$this->checkItemsTypes()) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage . '<br>' . $excludedProductsMessage;

            return false;
        }

        try {
            $plansEligibility = $this->getPlansEligibility();
        } catch (\Exception $e) {
            $this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            return false;
        }

        $this->message = $eligibilityMessage;
        $anyEligible = false;
        $minAmount = PHP_INT_MAX;
        $maxAmount = PHP_INT_MIN;
        foreach ($plansEligibility as $planEligibility) {
            $eligibility = $planEligibility->getEligibility();

            if ($eligibility->isEligible()) {
                $anyEligible = true;

                break;
            }

            $reasons = $eligibility->getReasons();
            if (key_exists('purchase_amount', $reasons) && $reasons['purchase_amount'] == 'invalid_value') {
                $minAmount = min($minAmount, $eligibility->getConstraints()['purchase_amount']['minimum']);
                $maxAmount = max($maxAmount, $eligibility->getConstraints()['purchase_amount']['maximum']);
            } else {
                $minAmount = min($minAmount, $planEligibility->getPlanConfig()->minimumAmount());
                $maxAmount = max($maxAmount, $planEligibility->getPlanConfig()->maximumAmount());
            }
        }

        if (!$anyEligible) {
            $cartTotal = Functions::priceToCents((float)$this->checkoutSession->getQuote()->getGrandTotal());
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            if ($cartTotal > $maxAmount) {
                $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($maxAmount));
                $this->message .= '<br>' . sprintf(__('(Maximum amount: %s)'), $price);
            } elseif ($cartTotal < $minAmount) {
                $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($minAmount));
                $this->message .= '<br>' . sprintf(__('(Minimum amount: %s)'), $price);
            }
        } else {
            $this->eligible = true;
        }

        return $this->eligible;
    }

    /**
     * @return PaymentPlanEligibility[]
     */
    public function getEligiblePlans(): array
    {
        try {
            return array_filter($this->getPlansEligibility(), function ($planEligibility) {
                return $planEligibility->getEligibility()->isEligible();
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return bool
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function checkItemsTypes()
    {
        $quote = $this->checkoutSession->getQuote();
        $excludedProductTypes = $this->config->getExcludedProductTypes();

        foreach ($quote->getAllItems() as $item) {
            if (in_array($item->getProductType(), $excludedProductTypes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $price
     * @return float|string
     */
    private function getFormattedPrice($price)
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * @return bool
     */
    public function isEligible()
    {
        return $this->eligible;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
