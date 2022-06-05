<?php

namespace Alma\MonthlyPayments\Gateway\Validator;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class RefundResponseValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger,
        ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->logger = $logger;
    }

    /**
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $fails = [];
        $errorCodes = [];
        $response = SubjectReader::readResponse($validationSubject);
        if (!$response['resultCode']) {
            $apiResponse = $validationSubject['response']['fails'];
            $errorCodes[] = $apiResponse->responseCode;
            if ($apiResponse->responseCode == '404') {
                $fails[] = $apiResponse->json['object_type'] . ' ' . $apiResponse->json['value'] . ' ' . $apiResponse->json['error_code'];
            }
            if ($apiResponse->responseCode == '400') {
                $fails[] = $apiResponse->json['error_code'] . ' : ' . $apiResponse->json['errors'][0]['message'];
            }
        }
        return $this->createResult($response['resultCode'], $fails, $errorCodes);
    }
}
