<?php

namespace Alma\MonthlyPayments\Test\Unit\Observer;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Alma\MonthlyPayments\Observer\AddToCartInsuranceObserver;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Configuration\Item\ItemProductResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class AddToCartInsuranceObserverTest extends TestCase
{
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ItemProductResolver
     */
    private $configurableItemProductResolver;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var AddToCartInsuranceObserver
     */
    private $AddToCartInsuranceObserver;
    /**
     * @var Product
     */
    private $insuranceProduct;
    /**
     * @var Observer
     */
    private $observer;
    /**
     * @var Item
     */
    private $observerItem;
    /**
     * @var Product
     */
    private $addedProduct;
    /**
     * @var Item
     */
    private $insuranceItemAdded;

    protected function setUp(): void
    {
        $this->insuranceHelper = $this->createMock(InsuranceHelper::class);
        $this->logger = $this->createMock(Logger::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->configurableItemProductResolver = $this->createMock(ItemProductResolver::class);
        $this->session = $this->createMock(Session::class);
        $this->AddToCartInsuranceObserver = new AddToCartInsuranceObserver(
            $this->insuranceHelper,
            $this->logger,
            $this->request,
            $this->configurableItemProductResolver,
            $this->session
        );

        $this->addedProduct = $this->createMock(Product::class);
        $this->addedProduct->method('getId')->willReturn(42);

        $this->insuranceProduct = $this->createMock(Product::class);
        $this->insuranceProduct->method('getId')->willReturn(74);

        $this->insuranceItemAdded = $this->createMock(Item::class);
        $this->insuranceItemAdded->method('getProduct')->willReturn($this->insuranceProduct);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('addProduct')->with($this->insuranceProduct)->willReturn($this->insuranceItemAdded);

        $this->observerItem = $this->createMock(Item::class);
        $this->observerItem->method('getPrice')->willReturn(123.45);
        $this->observerItem->method('getQuote')->willReturn($quote);

        $this->observer = $this->createMock(Observer::class);
        $this->observer->method('getData')->willReturn($this->observerItem);
    }

    protected function tearDown(): void
    {
        $this->insuranceHelper = null;
        $this->logger = null;
        $this->request = null;
        $this->configurableItemProductResolver = null;
        $this->session = null;
        $this->AddToCartInsuranceObserver = null;
        $this->insuranceProduct = null;
        $this->observer = null;
        $this->observerItem = null;
        $this->addedProduct = null;
    }

    public function testGivenNoInsuranceProductInCatalogueShouldReturnDirectly(): void
    {
        $this->insuranceHelper->method('getAlmaInsuranceProduct')->willThrowException(new AlmaInsuranceProductException('No insurance product found'));
        $this->AddToCartInsuranceObserver->execute($this->createMock(Observer::class));
    }

    public function testGivenInsuranceProductAddedToCartShouldReturnDirectly(): void
    {
        $this->insuranceHelper->method('getAlmaInsuranceProduct')->willReturn($this->insuranceProduct);
        $this->observerItem->method('getProduct')->willReturn($this->insuranceProduct);
        $this->logger->expects($this->once())->method('info')->with('Warning insurance product added to quote');
        $this->AddToCartInsuranceObserver->execute($this->observer);
    }

    public function testGivenFormDataAreNotSetShouldReturnDirectly(): void
    {
        $this->insuranceHelper->method('getAlmaInsuranceProduct')->willReturn($this->insuranceProduct);
        $this->observerItem->method('getProduct')->willReturn($this->addedProduct);
        $this->logger->expects($this->once())->method('info')->with('Warning no insurance contract id in request params');
        $this->AddToCartInsuranceObserver->execute($this->observer);
    }

    public function testGivenFormDataAreSetInsuranceObjectNotExistShouldReturnDirectly(): void
    {
        $this->insuranceHelper->method('getAlmaInsuranceProduct')->willReturn($this->insuranceProduct);
        $this->observerItem->method('getProduct')->willReturn($this->addedProduct);

        $this->request->method('getParam')->willReturnOnConsecutiveCalls('contract_123', 3);
        $this->logger->expects($this->once())->method('info')->with('Warning no insurance found for this contract id');
        $this->AddToCartInsuranceObserver->execute($this->observer);
    }

    public function testGivenAnInsuranceObjectShouldSetDataToItemsAndCallAddInsuranceProductToQuote(): void
    {
        $this->insuranceHelper->method('getAlmaInsuranceProduct')->willReturn($this->insuranceProduct);
        $this->observerItem->method('getProduct')->willReturn($this->addedProduct);

        $this->request->method('getParam')->willReturnOnConsecutiveCalls('contract_123', 3);

        $insuranceObject = $this->createMock(InsuranceProduct::class);
        $insuranceObject->method('toArray')->willReturn(['id' => 'contract_123']);
        $this->insuranceHelper->method('getInsuranceProduct')->willReturn($insuranceObject);
        $this->insuranceHelper->expects($this->exactly(2))->method('setAlmaInsuranceToQuoteItem')->withConsecutive([$this->observerItem], [$this->insuranceItemAdded]);

        $this->AddToCartInsuranceObserver->execute($this->observer);
    }
}
