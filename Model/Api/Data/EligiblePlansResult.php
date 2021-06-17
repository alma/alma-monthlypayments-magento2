<?php


namespace Alma\MonthlyPayments\Model\Api\Data;


use Alma\API\Endpoints\Results\Eligibility;
use Alma\MonthlyPayments\Api\Data\EligiblePlansResultInterface;
use Alma\MonthlyPayments\Api\Data\InstallmentInterfaceFactory;
use Alma\MonthlyPayments\Gateway\Config\PaymentPlans\PaymentPlanConfig;

class EligiblePlansResult implements EligiblePlansResultInterface
{

    /** @var InstallmentInterfaceFactory */
    private $installmentFactory;

    /** @var Eligibility */
    private $eligibility;

    /** @var PaymentPlanConfig */
    private $planConfig;

    /**
     * EligiblePlansResult constructor.
     * @param InstallmentInterfaceFactory $installmentFactory
     * @param array $data
     */
    public function __construct(InstallmentInterfaceFactory $installmentFactory, array $data = [])
    {
        $this->eligibility = $data['planEligibility']->getEligibility();
        $this->planConfig = $data['planEligibility']->getPlanConfig();
        $this->installmentFactory = $installmentFactory;
    }

    /**
     * @inheritDoc
     */
    public function getKey()
    {
        return $this->planConfig->planKey();
    }

    /**
     * @inheritDoc
     */
    public function getInstallmentsCount()
    {
        return $this->planConfig->installmentsCount();
    }

    /**
     * @inheritDoc
     */
    public function isDeferred()
    {
        return $this->planConfig->isDeferred();
    }

    /**
     * @inheritDoc
     */
    public function getDeferredDays()
    {
        return $this->planConfig->deferredDays();
    }

    /**
     * @inheritDoc
     */
    public function getDeferredType()
    {
        return $this->planConfig->deferredType();
    }

    /**
     * @inheritDoc
     */
    public function getDeferredMonths()
    {
        return $this->planConfig->deferredMonths();
    }

    /**
     * @inheritDoc
     */
    public function getMinimumAmount()
    {
        return $this->planConfig->minimumAmount();
    }

    /**
     * @inheritDoc
     */
    public function getMaximumAmount()
    {
        return $this->planConfig->maximumAmount();
    }

    /**
     * @inheritDoc
     */
    public function getInstallments()
    {
        return array_map(function ($installment) {
            return $this->installmentFactory->create(["data" => ["installment" => $installment]]);
        }, $this->eligibility->getPaymentPlan());
    }
}
