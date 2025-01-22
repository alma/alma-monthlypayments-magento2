<?php

namespace Alma\MonthlyPayments\Services;


use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Exceptions\AlmaException;
use Alma\API\Exceptions\ParametersException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;

class MerchantBusinessService
{
    const QUOTE_BNPL_ELIGIBILITY_KEY = 'alma_bnpl_eligibility';
    /**
     * @var AlmaClient
     */
    private $almaClient;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;


    /**
     * @param AlmaClient $almaClient
     * @param Logger $logger
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        AlmaClient      $almaClient,
        Logger          $logger,
        QuoteRepository $quoteRepository
    )
    {
        $this->almaClient = $almaClient;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }


    /**
     * Set alma_bnpl_eligibility to true in quote DB
     *
     * @param Quote $quote
     * @return void
     */
    public function quoteIsEligibleForBNPL(Quote $quote): void
    {
        $quote->setData(self::QUOTE_BNPL_ELIGIBILITY_KEY, true);
        $this->quoteRepository->save($quote);
    }

    /**
     * Set alma_bnpl_eligibility to false in quote DB
     *
     * @param Quote $quote
     * @return void
     */
    public function quoteNotEligibleForBNPL(Quote $quote): void
    {
        $quote->setData(self::QUOTE_BNPL_ELIGIBILITY_KEY, false);
        $this->quoteRepository->save($quote);
    }

    /**
     * Generate OrderConfirmed DTO
     *
     * @param Order $order
     * @return OrderConfirmedBusinessEvent
     * @throws MerchantBusinessServiceException
     */
    public function createOrderConfirmedBusinessEventByOrder(Order $order): OrderConfirmedBusinessEvent
    {
        $planKey = $order->getPayment()->getAdditionalInformation()['selectedPlan'] ?? '';
        $isPayNow = PaymentPlansHelper::PAY_NOW_KEY === $planKey;
        $isBNPL = !$isPayNow && preg_match('!general:[\d]+:[\d]+:[\d]+!', $planKey);

        try {
            /** @var Quote $quote */
            $quote = $this->quoteRepository->get($order->getQuoteId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Create Order confirmed business event get quote error :', [$e->getMessage()]);
            throw new MerchantBusinessServiceException('Create Order confirmed business event : quote not found');
        }
        try {
            return new OrderConfirmedBusinessEvent(
                $isPayNow,
                $isBNPL,
                (bool)$quote->getData(self::QUOTE_BNPL_ELIGIBILITY_KEY),
                $order->getId(),
                $order->getQuoteId(),
                $order->getPayment()->getAdditionalInformation()['PAYMENT_ID'] ?? null
            );
        } catch (ParametersException $e) {
            $this->logger->error('New Order confirmed business event error :', [$e->getMessage()]);
            throw new MerchantBusinessServiceException('Create Order confirmed business event : Impossible to construct DTO');
        }
    }

    /**
     * Send Order Confirmed event with PHP Client
     *
     * @param OrderConfirmedBusinessEvent $orderConfirmedMock
     * @return void
     */
    public function sendOrderConfirmedBusinessEvent(OrderConfirmedBusinessEvent $orderConfirmedMock)
    {
        try {
            $this->almaClient->getDefaultClient()->merchants->sendOrderConfirmedBusinessEvent($orderConfirmedMock);
        } catch (AlmaException $e) {
            $this->logger->error('sendOrderConfirmedBusinessEvent : ', [$e->getMessage()]);
        }
    }
}

