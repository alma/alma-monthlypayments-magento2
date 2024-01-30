<?php

namespace Alma\MonthlyPayments\Model\Api\Insurance;

use Alma\MonthlyPayments\Api\Insurance\InsuranceUpdateInterface;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Framework\Webapi\Rest\Request;

class InsuranceUpdate implements InsuranceUpdateInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Request
     */
    private $request;

    public function __construct(
        Request $request,
        Logger $logger
    ) {
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function update(): string
    {
        $postData = $this->request->getBodyParams();
        return $postData['subscription_id'];
    }
}
