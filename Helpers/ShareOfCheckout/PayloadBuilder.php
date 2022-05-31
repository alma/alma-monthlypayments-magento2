<?php

namespace Alma\MonthlyPayments\Helpers\ShareOfCheckout;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class PayloadBuilder extends AbstractHelper
{
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @param Context $context
     * @param DateHelper $dateHelper
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Context $context,
        DateHelper $dateHelper,
        OrderHelper $orderHelper
    ) {
        parent::__construct($context);
        $this->dateHelper = $dateHelper;
        $this->orderHelper = $orderHelper;
    }

    /**
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            "start_time"      => $this->dateHelper->getStartDate(),
            "end_time"        => $this->dateHelper->getEndDate(),
            "orders"          => $this->orderHelper->getTotalsOrders(),
            "payment_methods" => $this->orderHelper->getShareOfCheckoutByPaymentMethods()
        ];
    }


}
