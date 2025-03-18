<?php

namespace Alma\MonthlyPayments\Test\Unit\Controller\Payment;

use Alma\API\Lib\PaymentValidator;
use Alma\MonthlyPayments\Controller\Payment\Ipn;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentValidation;
use Alma\MonthlyPayments\Model\Exceptions\AlmaPaymentValidationException;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use PHPUnit\Framework\TestCase;

class IpnTest extends TestCase
{

    /**
     * @var Context
     */
    private $context;
    /**
     * @var PaymentValidation
     */
    private $paymentValidationHelper;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentValidator
     */
    private $paymentValidator;


    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @var Ipn
     */
    private $ipn;

    /**
     * @var Http
     */
    private $httpRequest;

    /**
     * @var Json
     */
    private $json;

    const HEADER_SIGNATURE_KEY = 'X-Alma-Signature';
    const PAYMENT_ID = 'payment_123456';
    const API_KEY = 'sk_test_123456';

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->json = $this->createMock(Json::class);
        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturn($this->json);
        $this->context->method('getResultFactory')->willReturn($resultFactory);
        $this->httpRequest = $this->createMock(Http::class);
        $this->context->method('getRequest')->willReturn($this->httpRequest);
        $this->paymentValidationHelper = $this->createMock(PaymentValidation::class);
        $this->logger = $this->createMock(Logger::class);
        $this->paymentValidator = $this->createMock(PaymentValidator::class);
        $this->apiConfigHelper = $this->createMock(ApiConfigHelper::class);

        $this->ipn = new Ipn(
            $this->context,
            $this->paymentValidationHelper,
            $this->logger,
            $this->paymentValidator,
            $this->apiConfigHelper
        );
    }

    public function testExecuteWithoutApiKeyReturnError()
    {
        $this->apiConfigHelper
            ->expects($this->once())
            ->method('getActiveAPIKey')
            ->willReturn('');

        $this->json
            ->expects($this->once())
            ->method('setData')
            ->with(["error" => "Missing API key in IPN request"])
            ->willReturn($this->json);
        $this->json
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(500)->willReturn($this->json);

        $this->paymentValidationHelper
            ->expects($this->never())
            ->method('completeOrderIfValid');
        $this->ipn->execute();
    }

    public function testExecuteNoCallCompleteOrderIfValidWithNoSignatureInHeader()
    {
        $this->apiConfigHelper
            ->expects($this->once())
            ->method('getActiveAPIKey')
            ->willReturn(self::API_KEY);

        $this->json
            ->expects($this->once())
            ->method('setData')
            ->with(["error" => "Missing signature"])
            ->willReturn($this->json);
        $this->json
            ->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(401)
            ->willReturn($this->json);

        $this->httpRequest
            ->method('getParam')
            ->with('pid')
            ->willReturn(self::PAYMENT_ID);
        $this->httpRequest
            ->method('getHeader')
            ->with(self::HEADER_SIGNATURE_KEY)
            ->willReturn(false);
        $this->paymentValidationHelper
            ->expects($this->never())
            ->method('completeOrderIfValid');
        $this->ipn->execute();
    }

    public function testExecuteNoCallCompleteOrderIfValidWithBadSignatureInHeader()
    {
        $signature = 'bad_signature';
        $this->apiConfigHelper->expects($this->once())->method('getActiveAPIKey')->willReturn(self::API_KEY);

        $this->json->expects($this->once())->method('setData')->with(["error" => "Wrong signature in IPN request"])->willReturn($this->json);
        $this->json->expects($this->once())->method('setHttpResponseCode')->with(401)->willReturn($this->json);
        $this->httpRequest
            ->method('getParam')
            ->with('pid')
            ->willReturn(self::PAYMENT_ID);
        $this->httpRequest
            ->method('getHeader')
            ->with(self::HEADER_SIGNATURE_KEY)
            ->willReturn($signature);

        $this->paymentValidator
            ->expects($this->once())
            ->method('isHmacValidated')
            ->with(self::PAYMENT_ID, self::API_KEY, $signature)
            ->willReturn(false);

        $this->paymentValidationHelper->expects($this->never())->method('completeOrderIfValid');

        $this->ipn->execute();
    }

    public function testExecuteCallCompleteOrderIfValidWithGoodSignatureInHeaderReturn500ForException()
    {
        $signature = 'good_signature';
        $errorMsg = 'Error in validation';
        $this->apiConfigHelper
            ->expects($this->once())
            ->method('getActiveAPIKey')
            ->willReturn(self::API_KEY);

        $this->json
            ->expects($this->once())
            ->method('setData')
            ->with(["error" => $errorMsg])
            ->willReturn($this->json);
        $this->json->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(500)
            ->willReturn($this->json);

        $this->httpRequest
            ->method('getParam')
            ->with('pid')
            ->willReturn(self::PAYMENT_ID);
        $this->httpRequest
            ->method('getHeader')
            ->with(self::HEADER_SIGNATURE_KEY)
            ->willReturn($signature);

        $this->paymentValidator
            ->expects($this->once())
            ->method('isHmacValidated')
            ->with(self::PAYMENT_ID, self::API_KEY, $signature)
            ->willReturn(true);

        $this->paymentValidationHelper
            ->expects($this->once())
            ->method('completeOrderIfValid')
            ->with(self::PAYMENT_ID)
            ->willThrowException(new AlmaPaymentValidationException($errorMsg, '', 500));

        $this->ipn->execute();
    }

    public function testExecuteCallCompleteOrderIfValidWithGoodSignatureInHeaderReturn200()
    {
        $signature = 'good_signature';
        $this->apiConfigHelper
            ->expects($this->once())
            ->method('getActiveAPIKey')
            ->willReturn(self::API_KEY);

        $this->json
            ->expects($this->once())
            ->method('setData')
            ->with(["success" => true])
            ->willReturn($this->json);
        $this->json->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(200)
            ->willReturn($this->json);

        $this->httpRequest
            ->method('getParam')
            ->with('pid')
            ->willReturn(self::PAYMENT_ID);
        $this->httpRequest
            ->method('getHeader')
            ->with(self::HEADER_SIGNATURE_KEY)
            ->willReturn($signature);

        $this->paymentValidator
            ->expects($this->once())
            ->method('isHmacValidated')
            ->with(self::PAYMENT_ID, self::API_KEY, $signature)
            ->willReturn(true);

        $this->paymentValidationHelper
            ->expects($this->once())
            ->method('completeOrderIfValid')
            ->with(self::PAYMENT_ID);

        $this->ipn->execute();
    }
}
