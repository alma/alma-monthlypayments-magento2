<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Data\InsuranceConfig;
use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;

class InsuranceHelper extends AbstractHelper
{
    const ALMA_INSURANCE_SKU = 'alma_insurance';
    const ALMA_INSURANCE_CONFIG_CODE = 'insurance_config';
    const CONFIG_IFRAME_URL ='https://protect.staging.almapay.com/almaBackOfficeConfiguration.html';

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ProductRepository $productRepository
     * @param Logger $logger
     * @param Json $json
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        ProductRepository $productRepository,
        Logger $logger,
        Json $json,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
    }

    /**
     * @return InsuranceConfig
     */
    public function getConfig():InsuranceConfig
    {
        $configData = (string)$this->configHelper->getConfigByCode(self::ALMA_INSURANCE_CONFIG_CODE);
        return new InsuranceConfig($configData);
    }

    /**
     * Get alma_insurance data from model
     *
     * @param Item $quoteItem
     * @return string
     */
    public function getQuoteItemAlmaInsurance(Item $quoteItem): ?string
    {
        return $quoteItem->getAlmaInsurance();
    }

    /**
     * Set alma_insurance in DB
     *
     * @param Item $quoteItem
     * @param array $data
     * @return Item
     */
    public function setAlmaInsuranceToQuoteItem(Item $quoteItem, array $data): Item
    {
        return $quoteItem->setAlmaInsurance($this->json->serialize($data));
    }

    /**
     * @return InsuranceProduct|null
     */
    public function getInsuranceParamsInRequest(): ?InsuranceProduct
    {
        $insuranceId = $this->request->getParam('alma_insurance_id');
        $insuranceName = $this->request->getParam('alma_insurance_name');
        $insurancePrice = $this->request->getParam('alma_insurance_price');
        if ($insuranceId && $insuranceName && $insurancePrice) {
            return new InsuranceProduct((int)$insuranceId, $insuranceName, $this->formatPrice($insurancePrice));
        }
        return null;
    }

    /**
     * @param string $price
     * @return float
     */
    public function formatPrice(string $price):float
    {
        return (float)substr($price, 0, -1);
    }

    /**
     * @return Product
     * @throws AlmaInsuranceProductException
     */
    public function getAlmaInsuranceProduct(): Product
    {
        try {
            return $this->productRepository->get(self::ALMA_INSURANCE_SKU);
        } catch (NoSuchEntityException $e) {
            $message = 'No alma Insurance product in Catalog - Use a product with sku : ' . self::ALMA_INSURANCE_SKU;
            $this->logger->error($message, [$e->getMessage()]);
            throw new AlmaInsuranceProductException($message, 0, $e);
        }
    }

    /**
     * @param int $productId
     * @param int $insuranceId
     * @return string
     */
    public function createLinkToken(int $productId, int $insuranceId): string
    {
        return (hash('sha256', $productId . time() . $insuranceId));
    }

    public function getIframeUrlWithParams():string
    {
        $configArray = $this->getConfig()->getArrayConfig();
        unset($configArray['is_insurance_activated']);
        $paramNumber=0;
        $uri ='';
        foreach ($configArray as $key=>$value) {
            $uri .= ($paramNumber===0 ? '?' : '&').$key.'='.($value ? 'true' : 'false');
            $paramNumber++;
        }
        return self::CONFIG_IFRAME_URL.$uri;
    }
}
