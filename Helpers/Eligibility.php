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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;
use Alma\MonthlyPayments\Helpers\QuoteHelper;
use Magento\Quote\Api\Data\CartInterface;
use \InvalidArgumentException;

class Eligibility
{
    const INSTALLMENTS_TYPE = 'installments';
    const SPREAD_TYPE = 'spread';
    const DEFFERED_TYPE = 'deferred';
    const MERGED_TYPE = 'merged';
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
     * @var bool
     */
    private $alreadyLoaded;
    /**
     * @var PaymentPlanEligibility[]
     */
    private $currentFeePlans;
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;


    /**
     * Eligibility constructor.
     * @param Data $pricingHelper
     * @param AlmaClient $almaClient
     * @param Logger $logger
     * @param Config $config
     * @param AlmaQuote $quoteData
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(
        Data $pricingHelper,
        Helpers\AlmaClient $almaClient,
        Helpers\Logger $logger,
        Config $config,
        AlmaQuote $quoteData,
        QuoteHelper $quoteHelper
    )
    {
        $this->pricingHelper = $pricingHelper;
        $this->alma = $almaClient->getDefaultClient();
        $this->logger = $logger;
        $this->config = $config;
        $this->quoteData = $quoteData;
        $this->quoteHelper = $quoteHelper;
        $this->alreadyLoaded = false;
        $this->currentFeePlans = [];
    }

    /**
     * Get eligibility plans
     * @return PaymentPlanEligibility[]
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RequestError
     * @throws InvalidArgumentException
     */
    private function getPlansEligibility(): array
    {
        try {
            $quote = $this->getEligibilityQuote();
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$this->alma){
            throw new InvalidArgumentException('Alma client is not define');
        }
        if (!$this->checkItemsTypes()){
            throw new InvalidArgumentException($this->config->getExcludedProductsMessage());
        }

        if ($this->isAlreadyLoaded()){
            $this->logger->info('Fee plans are already loaded',[]);
            return $this->getCurrentsFeePlans();
        }

        $cartTotal = Functions::priceToCents((float)$quote->getGrandTotal());
        // Get enabled plans in BO and build a list of installments counts that should be tested for eligibility
        $enabledPlansInConfig      = $this->getEnabledConfigPaymentPlans();
        $installmentsQuery         = [];
        $availablePlansKeyInBo     = [];

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
                $availablePlansKeyInBo[] = $planKey;
            }
        }

        if (empty($installmentsQuery)) {
            $this->logger->info('No eligible installment in config for this amount');
            return [];
        }

        $eligibilities = $this->alma->payments->eligibility(
            $this->quoteData->eligibilityDataFromQuote($quote,$installmentsQuery),
            true
        );

        $eligibilities = $this->formatResultEligibility($eligibilities);

        $plansEligibility = [];
        foreach ($availablePlansKeyInBo as $planKey) {
            $planConfig  = $this->getPlanConfigFromKey($enabledPlansInConfig, $planKey);

            if (!$planConfig) {
                $this->logger->info('No Plan Config' ,['planKey' => $planKey]);
                continue;
            }
            // Check if bo plan is in eligibility list
            if (!array_key_exists($planConfig->almaPlanKey(), $eligibilities)) {
                $this->logger->info('Configured plan is not eligible : ' ,['planKey' => $planKey, 'country' => $quote->getBillingAddress()->getCountryId()]);
                continue;
            }
            $eligibility = $eligibilities[$planConfig->almaPlanKey()];
            $plansEligibility[$planConfig->planKey()] = new PaymentPlanEligibility($planConfig, $eligibility);
        }
        $feePlans = array_values($plansEligibility);
        $this->setCurrentsFeePlans($feePlans);
        return $feePlans;
    }

    /**
     * @return bool
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    public function checkEligibility(): bool
    {
        $this->logger->info('checkEligibility',[]);
        try {
            $quote = $this->getEligibilityQuote();
        } catch (InvalidArgumentException $e) {
            return false;
        }
        $eligibilityMessage = $this->config->getEligibilityMessage();
        $nonEligibilityMessage = $this->config->getNonEligibilityMessage();
        $excludedProductsMessage = $this->config->getExcludedProductsMessage();

        $this->eligible = false;
        $this->message = $nonEligibilityMessage;

        if (!$this->checkItemsTypes()) {
            $this->message .='<br>' . $excludedProductsMessage;
            return false;
        }

        try {
            $plansEligibility = $this->getPlansEligibility();
        } catch (\Exception $e) {
            $this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
            return false;
        }

        $anyEligible = false;
        $minAmount = $this->getMinPurchaseAmountInBo();
        $maxAmount = $this->getMaxPurchaseAmountInBo();
        foreach ($plansEligibility as $planEligibility) {
            $eligibility = $planEligibility->getEligibility();
            if ($eligibility->isEligible()) {
                $anyEligible = true;
                break;
            }
        }

        if (!$anyEligible) {
            $cartTotal = Functions::priceToCents((float)$quote->getGrandTotal());

            if ($cartTotal > $maxAmount) {
                $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($maxAmount));
                $this->message .= '<br>' . sprintf(__('(Maximum amount: %s)'), $price);
            } elseif ($cartTotal < $minAmount) {
                $price = $this->getFormattedPrice(Helpers\Functions::priceFromCents($minAmount));
                $this->message .= '<br>' . sprintf(__('(Minimum amount: %s)'), $price);
            }
        } else {
            $this->message = $eligibilityMessage;
            $this->eligible = true;
        }
        return $this->eligible;
    }

    /**
     * Get eligible plans
     * @return PaymentPlanEligibility[]
     */
    public function getEligiblePlans(): array
    {
        try {
            return array_filter($this->getPlansEligibility(), function ($planEligibility) {
                return $planEligibility->getEligibility()->isEligible();
            });
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage(), [$e->getTrace()]);
        return [];
        }
    }

    /**
     * Check if all items are eligible for alma payment
     * excluding list in BO
     * @return bool
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    private function checkItemsTypes(): bool
    {
        try {
            $quote = $this->getEligibilityQuote();
        } catch (InvalidArgumentException $e) {
            return false;
        }
        $excludedProductTypes = $this->config->getExcludedProductTypes();
        foreach ($quote->getAllItems() as $item) {
            if (in_array($item->getProductType(), $excludedProductTypes)) {
                return false;
            }
        }
        return true;
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
     * Get formatted Price
     * @param int $price
     * @return string
     */
    private function getFormattedPrice(int $price): string
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * Get eligibility Status
     * @return bool
     */
    public function isEligible(): bool
    {
        return $this->eligible;
    }

    /**
     * Get translated eligibility message.
     * @return string
     */
    public function getMessage(): string
    {
        return __($this->message);
    }

    /**
     * Get currents fee Plans
     *
     * @return PaymentPlanEligibility[]
     *
     */
    public function getCurrentsFeePlans(): array
    {
        if (!$this->isAlreadyLoaded()){
            $this->getEligiblePlans();
        }
        return $this->currentFeePlans;
    }

    /**
     * Set Currents FeePlans
     *
     * @param PaymentPlanEligibility[]
     * @return bool
     */
    private function setCurrentsFeePlans($feePlans): bool
    {
        $hasFeePlans = false;
        if (count($feePlans)>0){
            $this->currentFeePlans = $feePlans;
            $hasFeePlans  = true;
            $this->setIsAlreadyLoaded($hasFeePlans);

        }
        return $hasFeePlans;
    }

    /**
     * Get loaded flag
     * @return bool
     */
    public function isAlreadyLoaded(): bool
    {
        return $this->alreadyLoaded;
    }

    /**
     * Set loaded flag
     *
     * @param bool $loaded
     *
     */
    private function setIsAlreadyLoaded(bool $loaded)
    {
        $this->alreadyLoaded = $loaded;
    }

    /**
     * Get back office enabled payment plans
     *
     * @return array
     */
    public function getEnabledConfigPaymentPlans():array
    {
        return $this->config->getPaymentPlansConfig()->getEnabledPlans();
    }

    /**
     * Get minimum purchase amount for payment plans in back office
     *
     * @return int
     */
    public function getMinPurchaseAmountInBo():int
    {
        $minPurchaseAmount = null;
        $inConfigPaymentPlans = $this->getEnabledConfigPaymentPlans();
        foreach ($inConfigPaymentPlans as $paymentPlan){
            if(
                $paymentPlan->isEnabled() &&
                ($minPurchaseAmount === null || $paymentPlan->minimumAmount() < $minPurchaseAmount)
            ){
                $minPurchaseAmount = $paymentPlan->minimumAmount();
            }
        }
        if ($minPurchaseAmount === null){
            $minPurchaseAmount =  0;
        }
        return $minPurchaseAmount;
    }

    /**
     * Get maximum purchase amount for payment plans in back office
     *
     * @return int
     */
    public function getMaxPurchaseAmountInBo():int
    {
        $maxPurchaseAmount = null;
        $inConfigPaymentPlans = $this->getEnabledConfigPaymentPlans();
        foreach ($inConfigPaymentPlans as $paymentPlan){
            if
            (
                $paymentPlan->isEnabled() &&
                ($maxPurchaseAmount === null || $paymentPlan->maximumAmount() > $maxPurchaseAmount)
            ){
                    $maxPurchaseAmount = $paymentPlan->maximumAmount();
            }
        }
        if ($maxPurchaseAmount === null){
            $maxPurchaseAmount =  0;
        }
        return $maxPurchaseAmount;
    }

    /**
     * Check if at least one payment plan is enabled in Bo
     *
     * @return bool
     */
    public function hasEnabledPaymentPlansInBo():bool
    {
        $hasActivePlans = false;
        $inConfigPaymentPlans = $this->getEnabledConfigPaymentPlans();
        foreach ($inConfigPaymentPlans as $paymentPlan) {
            if($paymentPlan->isEnabled()){
                return true;
            }
        }
        return $hasActivePlans;
    }

    /**
     * @param PaymentPlanEligibility[] $eligibilities
     * @return array
     */
    public function sortEligibilities($eligibilities):array
    {
        $sortedEligibilities=[];
        foreach ($eligibilities as $paymentPlan){

            $planConfig = $paymentPlan->getPlanConfig();
            $planKey = $planConfig->planKey();

            $type = $this->getPaymentType($planKey);
            $sortedEligibilities[$type][]=$paymentPlan;

        }
        return $sortedEligibilities;
    }

    /**
     * Get payment type according to plan key ( like general:10:0:0 ) for split ordering
     * @param string $planKey
     * @return string
     */
    private function getPaymentType($planKey):string
    {
        $matches = [];
        $isKnownType = preg_match('/^general:(\d{1,2}):(\d{1,2}):(\d{1,2})$/',$planKey,$matches);

        if ($isKnownType) {
            $installmentCount = $matches[1];
            $isDeferred = $matches[2] > 0 || $matches[3] > 0;

            return $this->buildType($installmentCount, $isDeferred);
        }
        // We don't know this paymentType
        return 'other';
    }

    /**
     * Build type for according to installment count and is deferred flag
     * @param int $installmentCount
     * @param bool $isDeferred
     * @return string
     */
    private function buildType($installmentCount,$isDeferred):string
    {
        $type = 'other';

        if ($installmentCount > 1 && !$isDeferred) {
            $type = self::INSTALLMENTS_TYPE;
        }
        if ($installmentCount > 4 && !$isDeferred) {
            $type = self::SPREAD_TYPE;
        }
        if ($installmentCount == 1 && $isDeferred) {
            $type = self::DEFFERED_TYPE;
        }
        return $type;
    }


    /**
     * @return CartInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    private function getEligibilityQuote():?CartInterface
    {
        $quote=$this->quoteHelper->getQuote();
        if(!isset($quote)){
            $this->logger->info('No Quote for eligibility',[]);
            throw new InvalidArgumentException('No Quote for eligibility');
        }
        return $quote;
    }

    /**
     * Convert instanceof \Alma\API\Endpoints\Results\Eligibility in array
     * @param $eligibilities
     * @return array
     */
    private function formatResultEligibility($eligibilities):array
    {
        if (!is_array($eligibilities) && $eligibilities instanceof \Alma\API\Endpoints\Results\Eligibility) {
            $eligibilities = [$eligibilities->getPlanKey() => $eligibilities];
            $this->logger->info('is instanceof \Alma\API\Endpoints\Results\Eligibility',[$eligibilities]);
        }
        return $eligibilities;
    }
}
