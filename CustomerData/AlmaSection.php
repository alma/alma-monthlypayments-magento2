<?php
namespace Alma\MonthlyPayments\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteRepository;
use Alma\MonthlyPayments\Helpers\Eligibility;
use Alma\MonthlyPayments\Helpers\ConfigHelper;

class AlmaSection implements SectionSourceInterface
{
    private array $paymentMethods;

    public function __construct(
        Logger $logger,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        Eligibility $eligibility,
        ConfigHelper $configHelper
    )
    {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteReposityory = $quoteRepository;
        $this->eligibility = $eligibility;
        $this->configHelper = $configHelper;
        $this->paymentMethods = [
            Eligibility::INSTALLMENTS_TYPE => [
                'title' => __($this->configHelper->getInstallmentsPaymentTitle()),
                'description'  => __($this->configHelper->getInstallmentsPaymentDesc()),
            ],
            Eligibility::SPREAD_TYPE => [
                'title' => __($this->configHelper->getSpreadPaymentTitle()),
                'description'  => __($this->configHelper->getSpreadPaymentDesc()),
            ],
            Eligibility::DEFFERED_TYPE => [
                'title' => __($this->configHelper->getDeferredPaymentTitle()),
                'description'  => __($this->configHelper->getDeferredPaymentDesc()),
            ],
            Eligibility::MERGED_TYPE => [
                'title' => __($this->configHelper->getMergePaymentTitle()),
                'description'  => __($this->configHelper->getMergePaymentDesc()),
            ],
        ];
    }


    public function getSectionData()
    {
        $this->logger->info('----- In GetSection DATA -----',[]);
        $areMergePaymentMethods = $this->configHelper->getAreMergedPayementMethods();
        $eligibilities[Eligibility::MERGED_TYPE] = $this->eligibility->getEligiblePlans();
        if(!$areMergePaymentMethods){
            $eligibilities = $this->eligibility->sortEligibilities($eligibilities[Eligibility::MERGED_TYPE]);
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

    private function getPaymentMethodTexts($typeName):array
    {
        foreach ($this->paymentMethods as $key => $paymentMethod) {
            if ($key == $typeName) {
                return $paymentMethod;
            }
        }
        return [
            'title' => __($this->configHelper->getMergePaymentTitle()),
            'description' => __($this->configHelper->getMergePaymentDesc()),
        ];
    }
}
