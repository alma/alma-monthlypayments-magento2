<?php

namespace Alma\MonthlyPayments\Services;


use Alma\API\Entities\DTO\MerchantBusinessEvent\CartInitiatedBusinessEvent;
use Alma\API\Entities\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEvent;
use Alma\API\Exceptions\AlmaException;
use Alma\API\Exceptions\ParametersException;
use Alma\MonthlyPayments\Helpers\AlmaClient;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Alma\MonthlyPayments\Model\Exceptions\MerchantBusinessServiceException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;

class MerchantBusinessService
{
    const QUOTE_BNPL_ELIGIBILITY_KEY = 'alma_bnpl_eligibility';
    const CART_INITIATED_STATUS_KEY = 'alma_cart_initiated_status';
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
     * Get cart initiated notification status in quote DB
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isSendCartInitiatedNotification(CartInterface $quote)
    {
        return (bool)$quote->getData(self::CART_INITIATED_STATUS_KEY);
    }

    /**
     * Create and send cart initiated business event
     * Update db Status
     * Never throw Exception
     *
     * @param CartInterface $quote
     * @return void
     */
    public function createAndSendCartInitiatedBusinessEvent(CartInterface $quote)
    {
        try {
            $businessEvent = $this->createCartInitiatedBusinessEventByQuote($quote);
            $this->almaClient->getDefaultClient()->merchants->sendCartInitiatedBusinessEvent($businessEvent);
            $this->saveCartInitiatedIsSendStatus($quote);
        } catch (AlmaException $e) {
            $this->logger->error('Send CartInitiatedBusinessEvent error : ', [$e->getMessage()]);
        }
    }

    /**
     * Create a CartInitiatedBusinessEventDTO
     *
     * @param CartInterface $quote //ID can be null, it's not in return type
     * @return CartInitiatedBusinessEvent
     * @throws ParametersException
     */
    private function createCartInitiatedBusinessEventByQuote(CartInterface $quote): CartInitiatedBusinessEvent
    {
        $quoteIdBoolOrNull = $quote->getId() ? (string)($quote->getId()) : null;
        return new CartInitiatedBusinessEvent($quoteIdBoolOrNull);

    }

    /**
     * Set alma_cart_initiated to true in quote DB
     *
     * @param CartInterface $quote
     * @return void
     */
    private function saveCartInitiatedIsSendStatus(CartInterface $quote): void
    {
        $quote->setData(self::CART_INITIATED_STATUS_KEY, '1');
        $this->quoteRepository->save($quote);
    }


    /**
     * Set alma_bnpl_eligibility to true in quote DB
     *
     * @param CartInterface $quote
     * @return void
     */
    public function quoteIsEligibleForBNPL(CartInterface $quote): void
    {
        $quote->setData(self::QUOTE_BNPL_ELIGIBILITY_KEY, '1');
        $this->quoteRepository->save($quote);
    }

    /**
     * Set alma_bnpl_eligibility to false in quote DB
     *
     * @param CartInterface $quote
     * @return void
     */
    public function quoteNotEligibleForBNPL(CartInterface $quote): void
    {
        $quote->setData(self::QUOTE_BNPL_ELIGIBILITY_KEY, '0');
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
            $quoteIsEligible = $quote->getData(self::QUOTE_BNPL_ELIGIBILITY_KEY);
            return new OrderConfirmedBusinessEvent(
                $isPayNow,
                $isBNPL,
                $quoteIsEligible !== null ? (bool)$quoteIsEligible : null,
                !empty($order->getIncrementId()) ? (string)$order->getIncrementId() : null,
                !empty($order->getQuoteId()) ? (string)$order->getQuoteId() : null,
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
            $this->logger->error('Send OrderConfirmedBusinessEvent error : ', [$e->getMessage()]);
        }
    }
}

