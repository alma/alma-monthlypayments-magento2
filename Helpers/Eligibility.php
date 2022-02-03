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
use Alma\MonthlyPayments\Gateway\Config\Config;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfigInterface;
use Alma\MonthlyPayments\Helpers;
use Alma\MonthlyPayments\Model\Data\PaymentPlanEligibility;
use Alma\MonthlyPayments\Model\Data\Quote as AlmaQuote;
use Magento\Quote\Model\QuoteFactory;
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
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * Eligibility constructor.
     * @param Session $checkoutSession
     * @param Data $pricingHelper
     * @param AlmaClient $almaClient
     * @param Logger $logger
     * @param Config $config
     * @param AlmaQuote $quoteData
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Session $checkoutSession,
        Data $pricingHelper,
        Helpers\AlmaClient $almaClient,
        Helpers\Logger $logger,
        Config $config,
        AlmaQuote $quoteData,
        QuoteFactory $quoteFactory
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->pricingHelper = $pricingHelper;
        $this->logger = $logger;
        $this->alma = $almaClient->getDefaultClient();
        $this->config = $config;
        $this->quoteData = $quoteData;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param PaymentPlanConfigInterface[] $plansConfig
     * @param string                       $planKey
     *
     * @return null|PaymentPlanConfigInterface
     */
    private function getPlanConfigFromKey(array $plansConfig, string $planKey): ?PaymentPlanConfigInterface
    {
        foreach ($plansConfig as $planConfig) {
            if ($planConfig->planKey() === $planKey) {
                return $planConfig;
            }
        }

        return null;
    }

    /**
     * @return PaymentPlanEligibility[]
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RequestError
     */
    private function getPlansEligibility(): array
    {
        $this->logger->info('getPlansEligibility',[]);
        if (!$this->alma || !$this->checkItemsTypes()) {
            $this->logger->info('Alma client is empty or not good item types');
            return [];
        }

        $cartTotal = Functions::priceToCents((float)$this->checkoutSession->getQuote()->getGrandTotal());

        // Get enabled plans and build a list of installments counts that should be tested for eligibility
        $enabledPlansInConfig      = $this->config->getPaymentPlansConfig()->getEnabledPlans();
        $installmentsQuery         = [];
        $availablePlans            = [];

        foreach ($enabledPlansInConfig as $planKey => $planConfig) {
            if (
                $cartTotal >= $planConfig->minimumAmount() &&
                $cartTotal <= $planConfig->maximumAmount()
            ) {
                // Query eligibility for the plan's installments count & keep track of which plans are queried
                $installmentsQuery[] = [
                    'purchase_amount' => $cartTotal,
                    'installments_count' => $planConfig->installmentsCount(),
                    'deferred_days' => $planConfig->deferredDays(),
                    'deferred_month' => $planConfig->deferredMonths(),
                    'cart_total' => $cartTotal,
                ];
                $availablePlans[] = $planKey;
            }
        }

        if (empty($installmentsQuery)) {
            $this->logger->info('No eligible installment in config');
            return [];
        }

        $quote = $this->quoteFactory->create()->load($this->checkoutSession->getQuote()->getId());
        $eligibilities = $this->alma->payments->eligibility(
            $this->quoteData->eligibilityDataFromQuote($quote,$installmentsQuery),
            true
        );
        if (!is_array($eligibilities) && $eligibilities instanceof \Alma\API\Endpoints\Results\Eligibility) {
            $eligibilities = [$eligibilities->getPlanKey() => $eligibilities];
        }
        $plansEligibility = [];
        foreach ($availablePlans as $planKey) {
            $planConfig  = $this->getPlanConfigFromKey($enabledPlansInConfig, $planKey);
            if (!$planConfig) {
                $this->logger->info('No Plan Config' ,['planKey' => $planKey]);
                continue;
            }
            if (!array_key_exists($planConfig->almaPlanKey(), $eligibilities)) {
                $this->logger->info('Plan is not Eligible for this country' ,['planKey' => $planKey, 'country' => $quote->getBillingAddress()->getCountryId()]);
                continue;
            }
            $eligibility = $eligibilities[$planConfig->almaPlanKey()];
            $plansEligibility[$planConfig->planKey()] = new PaymentPlanEligibility($planConfig, $eligibility);
        }
        $this->logger->info('array_values($plansEligibility)',[array_values($plansEligibility)]);
        return array_values($plansEligibility);
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
        $this->logger->info('checkEligibility',[]);

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
        $this->logger->info('getEligiblePlans',[]);
        try {
            return array_filter($this->getPlansEligibility(), function ($planEligibility) {
                return $planEligibility->getEligibility()->isEligible();
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
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
     * Get translated eligibility message.
     * @return string
     */
    public function getMessage()
    {
        return __($this->message);
    }
}
