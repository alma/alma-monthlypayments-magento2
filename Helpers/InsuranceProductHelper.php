<?php

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;

class InsuranceProductHelper extends AbstractHelper
{
    private $productFactory;
    private $fileProcessor;
    private $filesystem;
    private $logger;
    private $directory;
    private $productRepository;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param ProductFactory $productFactory
     * @param Dir $directory
     * @param File $fileProcessor
     * @param Filesystem $filesystem
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Context           $context,
        Logger            $logger,
        ProductFactory    $productFactory,
        Dir               $directory,
        File              $fileProcessor,
        Filesystem        $filesystem,
        ProductRepository $productRepository
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->fileProcessor = $fileProcessor;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->directory = $directory;
        $this->productRepository = $productRepository;
    }

    /**
     * @return void
     */
    public function createInsuranceProduct(): void
    {
        // Create a new product with the insurance SKU
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
        $insuranceProduct->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        // ID de la classe de taxe (0 pour non applicable)
        $insuranceProduct->setTaxClassId(0);
        $insuranceProduct->setDescription('Alma Insurance product');
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
}
