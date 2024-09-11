<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Alma\MonthlyPayments\Model\Exceptions\AlmaProductException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class InsuranceProductHelper extends AbstractHelper
{
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var File
     */
    private $fileProcessor;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Dir
     */
    private $directory;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param ProductFactory $productFactory
     * @param Dir $directory
     * @param File $fileProcessor
     * @param Filesystem $filesystem
     * @param ProductRepository $productRepository
     * @param ProductHelper $productHelper
     */
    public function __construct(
        Context           $context,
        Logger            $logger,
        ProductFactory    $productFactory,
        Dir               $directory,
        File              $fileProcessor,
        Filesystem        $filesystem,
        ProductRepository $productRepository,
        ProductHelper     $productHelper
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->fileProcessor = $fileProcessor;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->directory = $directory;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
    }

    /**
     * Get insurance product
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getInsuranceProduct(): ProductInterface
    {
        $this->logger->info('getCurrentStoreId', [$this->getCurrentStoreId()]);

        return $this->productRepository->get(InsuranceHelper::ALMA_INSURANCE_SKU, true, Store::DEFAULT_STORE_ID);
    }

    /**
     * Disable insurance product if exist
     *
     * @return void
     * @throws AlmaInsuranceProductException
     */
    public function disableInsuranceProductIfExist(): void
    {
        try {
            $insuranceProduct = $this->getInsuranceProduct();
        } catch (NoSuchEntityException $e) {
            $this->logger->info('Alma insurance product not found no disable needed', [$e->getMessage()]);
            return;
        }

        if ((int)$insuranceProduct->getStatus() === Status::STATUS_DISABLED) {
            return;
        }

        try {
            $this->productHelper->disableProduct($insuranceProduct);
        } catch (AlmaProductException $e) {
            $this->logger->error('Disable insurance product failed with message : ', [$e->getMessage()]);
            throw new AlmaInsuranceProductException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Create Insurance product in merchant catalogue
     *
     * @return void
     */
    public function createInsuranceProduct(): void
    {
        // Create a new product with the insurance SKU
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
        // Not visible individually ID 1
        $insuranceProduct->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        // Tax class ID 0 for not applicable
        $insuranceProduct->setTaxClassId(0);
        $insuranceProduct->setDescription('Alma Insura      nce product');
        $insuranceProduct->setUrlKey('alma-insurance');

        $fileName = 'alma_insurance_logo.jpg';
        $insuranceLogo = $this->directory->getDir('Alma_MonthlyPayments') . '/view/adminhtml/web/images/' . $fileName;

        $destinationPath = 'catalog/product/a/l/';
        $folders = explode('/', $destinationPath);
        try {
            $path = '';
            foreach ($folders as $folder) {
                $path = $path . '/' . $folder;
                $this->fileProcessor->checkAndCreateFolder($this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($path));
            }

            $this->fileProcessor->cp($insuranceLogo, $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath($destinationPath . $fileName));
            $insuranceProduct->addImageToMediaGallery($destinationPath . $fileName, ['image', 'small_image', 'thumbnail'], false, false);
        } catch (LocalizedException $e) {
            $this->logger->error('Impossible to add image to alma insurance product : ', [$e->getMessage()]);
        }
        try {
            $newInsuranceProduct = $this->productRepository->save($insuranceProduct);
            $this->logger->info('New alma insurance product is successfully create with id', [$newInsuranceProduct->getId()]);
            $this->logger->info('New alma insurance product is salable : ', [$newInsuranceProduct->isSalable()]);
        } catch (\Exception $e) {
            $this->logger->error('Save insurance product failed with message : ', [$e->getMessage()]);
            $this->logger->error(
                'Impossible to create the alma insurance product, please create it manually with sku',
                [InsuranceHelper::ALMA_INSURANCE_SKU]
            );
        }
    }

    /**
     * Get current store ID
     *
     * @return int
     */
    private function getCurrentStoreId(): int
    {
        return $this->scopeConfig->getValue('store_id', ScopeInterface::SCOPE_STORE);
    }
}
