<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\MonthlyPayments\Helpers\InsuranceHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
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
    /**
     * @var Dir
     */
    private $directory;
    /**
     * @var File
     */
    private $fileProcessor;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        Logger $logger,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        State $state,
        InsuranceHelper $insuranceHelper,
        Dir $directory,
        File $fileProcessor,
        Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->insuranceHelper = $insuranceHelper;
        $this->productRepository = $productRepository;
        $this->state = $state;
        $this->directory = $directory;
        $this->fileProcessor = $fileProcessor;
        $this->filesystem = $filesystem;
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
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        } catch (LocalizedException $e) {
            $this->logger->info('Area Code is already set continue', [$e->getMessage()]);
        }

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
