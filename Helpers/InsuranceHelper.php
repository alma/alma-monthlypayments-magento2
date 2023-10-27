<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Data\InsuranceProduct;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;

class InsuranceHelper extends AbstractHelper
{
    /**
     * @var Json
     */
    private $json;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param Json $json
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        Json $json
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->request = $request;
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
    public function setQuoteItemAlmaInsurance(Item $quoteItem, array $data): Item
    {
        return $quoteItem->setAlmaInsurance($this->json->serialize($data));
    }

    /**
     * @return string|null
     */
    public function getInsuranceParamsInRequest(): ?InsuranceProduct
    {

        $insuranceId = $this->request->getParam('alma_insurance_id');
        $insuranceName = $this->request->getParam('alma_insurance_name');
        $insurancePrice = $this->request->getParam('alma_insurance_price');
        if ($insuranceId && $insuranceName && $insurancePrice) {
            return New InsuranceProduct((int)$insuranceId, $insuranceName, (int)substr($insurancePrice, 0, -1));
        }
        return null;
    }
}
