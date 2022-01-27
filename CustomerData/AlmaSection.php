<?php
namespace Alma\MonthlyPayments\CustomerData;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteRepository;
use Alma\MonthlyPayments\Helpers\Eligibility;

class AlmaSection implements SectionSourceInterface
{
    public function __construct(
        Logger $logger,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        Eligibility $eligibility
    )
    {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteReposityory = $quoteRepository;
        $this->eligibility = $eligibility;
    }
    public function getSectionData()
    {
        $this->logger->info('----- In GetSection DATA -----',[]);
        $paymentPlans = [];
        $plans = $this->eligibility->getEligiblePlans();
        foreach ($plans as $key=> $plan){
            $paymentPlans[$key] = $plan->getPlanConfig()->toArray();
            $paymentPlans[$key]['eligibility'] = $plan->getEligibility();

        }
        return [
            'paymentPlans' => $paymentPlans,
        ];
    }
}
