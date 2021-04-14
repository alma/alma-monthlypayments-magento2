<?php


namespace Alma\MonthlyPayments\Model\Api\Data;


use Alma\API\Entities\Instalment;
use Alma\MonthlyPayments\Api\Data\InstallmentInterface;

class Installment implements InstallmentInterface
{
    /**
     * @var Instalment
     */
    private $installmentData;

    public function __construct(array $data = [])
    {
        $this->installmentData = $data['installment'];
    }

    /**
     * @inheritDoc
     */
    public function getDueDate()
    {
        return $this->installmentData['due_date'];
    }

    /**
     * @inheritDoc
     */
    public function getCustomerFee()
    {
        return $this->installmentData['customer_fee'];
    }

    /**
     * @inheritDoc
     */
    public function getPurchaseAmount()
    {
        return $this->installmentData['purchase_amount'];
    }
}
