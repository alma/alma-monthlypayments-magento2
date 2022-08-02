<?php
namespace Alma\MonthlyPayments\CustomerData;

use Alma\MonthlyPayments\Helpers\CheckoutConfigHelper;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Alma\MonthlyPayments\Helpers\Eligibility;

class AlmaSection implements SectionSourceInterface
{
    /**
     * @var array[]
     */
    private $paymentOptions;
    /**
     * @var Eligibility
     */
    private $eligibility;
    /**
     * @var CheckoutConfigHelper
     */
    private $checkoutConfigHelper;

    /**
     * @param Eligibility $eligibility
     * @param CheckoutConfigHelper $checkoutConfigHelper
     */
    public function __construct(
        Eligibility $eligibility,
        CheckoutConfigHelper $checkoutConfigHelper
    )
    {
        $this->eligibility = $eligibility;
        $this->checkoutConfigHelper = $checkoutConfigHelper;
        $this->paymentOptions = [
            Eligibility::INSTALLMENTS_TYPE => [
                'title' => __($this->checkoutConfigHelper->getInstallmentsPaymentTitle()),
                'description'  => __($this->checkoutConfigHelper->getInstallmentsPaymentDesc()),
            ],
            Eligibility::SPREAD_TYPE => [
                'title' => __($this->checkoutConfigHelper->getSpreadPaymentTitle()),
                'description'  => __($this->checkoutConfigHelper->getSpreadPaymentDesc()),
            ],
            Eligibility::DEFERRED_TYPE => [
                'title' => __($this->checkoutConfigHelper->getDeferredPaymentTitle()),
                'description'  => __($this->checkoutConfigHelper->getDeferredPaymentDesc()),
            ],
            Eligibility::MERGED_TYPE => [
                'title' => __($this->checkoutConfigHelper->getMergePaymentTitle()),
                'description'  => __($this->checkoutConfigHelper->getMergePaymentDesc()),
            ],
        ];
    }


    /**
     * @return array
     */
    public function getSectionData(): array
    {
        $areMergePaymentMethods = $this->checkoutConfigHelper->getAreMergedPaymentMethods();
        $eligibilities[Eligibility::MERGED_TYPE] = $this->eligibility->getEligiblePlans();
        if(!$areMergePaymentMethods){
            $enabledPlanInBO = $this->eligibility->getEnabledConfigPaymentPlans();
            foreach ($enabledPlanInBO as $planConfig){
                $type = $this->eligibility->getPaymentType($planConfig->planKey());
                $eligibilities[$type] = [];
            }
            $eligibilities = $this->eligibility->sortEligibilities($eligibilities);
        }

        $allPaymentPlans = [];
        $paymentMethods = [];
        foreach ($eligibilities as $typeName => $eligibility){
            $paymentMethodText = $this->getPaymentMethodTexts($typeName);
            $paymentMethods[$typeName] = [
              'title' => $paymentMethodText['title'],
              'description' => $paymentMethodText['description'],
              'paymentPlans' => []
            ];
            foreach ( $eligibility as $plan) {
                $paymentPlan = $plan->getPlanConfig()->toArray();
                $paymentPlan['eligibility'] = $plan->getEligibility();
                $allPaymentPlans[]=$paymentPlan;
                $paymentMethods[$typeName]['paymentPlans'][]=$paymentPlan;
            }
        }
        return [
            'paymentMethods' => $paymentMethods,
            'allPaymentPlans' => $allPaymentPlans,
        ];
    }

    /**
     * @param $typeName
     * @return array
     */
    private function getPaymentMethodTexts($typeName):array
    {
        foreach ($this->paymentOptions as $key => $paymentMethod) {
            if ($key == $typeName) {
                return $paymentMethod;
            }
        }
        return [
            'title' => __($this->checkoutConfigHelper->getMergePaymentTitle()),
            'description' => __($this->checkoutConfigHelper->getMergePaymentDesc()),
        ];
    }
}
