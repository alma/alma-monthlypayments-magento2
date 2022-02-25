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
            $paymentMethods[$typeName]['title'] = $paymentMethodText['title'];
            $paymentMethods[$typeName]['description'] = $paymentMethodText['description'];
            $paymentMethods[$typeName]['paymentPlans'] = [];
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

        switch ($typeName){
            case Eligibility::INSTALLMENTS_TYPE :
                $paymentMethodTitle =__($this->configHelper->getInstallmentsPaymentTitle());
                $paymentMethodDesc  =__($this->configHelper->getInstallmentsPaymentDesc());
                break;
            case Eligibility::SPREAD_TYPE :
                $paymentMethodTitle =__($this->configHelper->getSpreadPaymentTitle());
                $paymentMethodDesc  =__($this->configHelper->getSpreadPaymentDesc());
                break;
            case Eligibility::DEFFERED_TYPE :
                $paymentMethodTitle =__($this->configHelper->getDeferredPaymentTitle());
                $paymentMethodDesc  =__($this->configHelper->getDeferredPaymentDesc());
                break;
            default:
                $paymentMethodTitle =__($this->configHelper->getMergePaymentTitle());
                $paymentMethodDesc = __($this->configHelper->getMergePaymentDesc());
                break;
        }

        return ["title"=>$paymentMethodTitle,"description"=>$paymentMethodDesc];
    }


}
