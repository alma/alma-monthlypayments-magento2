<?php
namespace Alma\MonthlyPayments\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Quote\Model\QuoteRepository;
use Alma\MonthlyPayments\Helpers\Eligibility;
use Alma\MonthlyPayments\Helpers\ConfigHelper;

class AlmaSection implements SectionSourceInterface
{
    /**
     * @var array[]
     */
    private $paymentOptions;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var QuoteRepository
     */
    private $quoteReposityory;
    /**
     * @var Eligibility
     */
    private $eligibility;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(
        Logger $logger,
        QuoteRepository $quoteRepository,
        Eligibility $eligibility,
        ConfigHelper $configHelper
    )
    {
        $this->logger = $logger;
        $this->quoteReposityory = $quoteRepository;
        $this->eligibility = $eligibility;
        $this->configHelper = $configHelper;
        $this->paymentOptions = [
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
        foreach ($this->paymentOptions as $key => $paymentMethod) {
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
