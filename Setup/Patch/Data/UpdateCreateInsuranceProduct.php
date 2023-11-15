<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 *  Get merchant_id value and clone it in test_merchant_id and live_merchant_id by store view
 *  Delete old merchant_id config
 */
class UpdateCreateInsuranceProduct implements DataPatchInterface
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var InsuranceHelper
     */
    private $insuranceHelper;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var State
     */
    private $state;

    public function __construct(
        Logger $logger,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        State $state,
        InsuranceHelper $insuranceHelper
    ) {
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->insuranceHelper = $insuranceHelper;
        $this->productRepository = $productRepository;
        $this->state = $state;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    public function apply()
    {
        $this->state->setAreaCode('adminhtml');
        /** @var Product $insuranceProduct */
        $insuranceProduct = $this->productFactory->create();
        $insuranceProduct->setSku(InsuranceHelper::ALMA_INSURANCE_SKU);
        $insuranceProduct->setName('Alma Insurance');
        $insuranceProduct->setPrice(0);
        //default attribute set id 4 in Adobe commerce
        $insuranceProduct->setAttributeSetId(4);
        $insuranceProduct->setStatus(Status::STATUS_ENABLED);
        $insuranceProduct->setTypeId('simple');
        $insuranceProduct->setStockData([
            "use_config_manage_stock" => 0,
            "is_in_stock" => 1,
            "manage_stock" => 0,
            "use_config_notify_stock_qty" => 0
        ]);
        // Not visible individualy ID 1
        $insuranceProduct->setVisibility(1);
        $insuranceProduct->setTaxClassId(0); // ID de la classe de taxe (0 pour non applicable)
        $insuranceProduct->setDescription('Alma Insurance product');
        $insuranceProduct->setUrlKey('alma-insurance');

        try {
            $newInsuranceProduct = $this->productRepository->save($insuranceProduct);
            $this->logger->info('New alma insurance product is suceffuly create with id', [$newInsuranceProduct->getId()]);
            $this->logger->info('New alma insurance product is salable : ', [$newInsuranceProduct->isSalable()]);
        } catch (\Exception $e) {
            $this->logger->error('Save insurance product failed with message : ', [$e->getMessage()]);
            $this->logger->error(
                'Impossible to create the alma insurance product, please create it manually with sku',
                [InsuranceHelper::ALMA_INSURANCE_SKU]
            );
        }
    }
}
