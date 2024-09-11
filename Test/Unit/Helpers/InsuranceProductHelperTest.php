<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\MonthlyPayments\Helpers\InsuranceProductHelper;
use Alma\MonthlyPayments\Helpers\Logger;
use Alma\MonthlyPayments\Helpers\ProductHelper;
use Alma\MonthlyPayments\Model\Exceptions\AlmaInsuranceProductException;
use Alma\MonthlyPayments\Model\Exceptions\AlmaProductException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InsuranceProductHelperTest extends TestCase
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var Dir
     */
    private Dir $dir;
    /**
     * @var File
     */
    private File $file;
    /**
     * @var Filesystem
     */
    private Filesystem $fileSystem;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var InsuranceProductHelper
     */
    private $insuranceProductHelper;
    /**
     * @var ProductHelper
     */
    private $productHelper;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->logger = $this->createMock(Logger::class);
        $this->productFactory = $this->createMock(ProductFactory::class);
        $this->dir = $this->createMock(Dir::class);
        $this->file = $this->createMock(File::class);
        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->productHelper = $this->createMock(ProductHelper::class);
        $this->insuranceProductHelper = new InsuranceProductHelper(
            $this->context,
            $this->logger,
            $this->productFactory,
            $this->dir,
            $this->file,
            $this->fileSystem,
            $this->productRepository,
            $this->productHelper
        );
    }

    public function testGetInsuranceProductThrowExceptionIfNoProductFound(): void
    {
        $this->productRepository->method('get')->willThrowException(new NoSuchEntityException());
        $this->expectException(NoSuchEntityException::class);
        $this->insuranceProductHelper->getInsuranceProduct();
    }

    public function testGetInsuranceProductReturnProduct(): void
    {
        $this->productRepository->method('get')->willReturn($this->createMock(ProductInterface::class));
        $this->assertInstanceOf(ProductInterface::class, $this->insuranceProductHelper->getInsuranceProduct());
    }

    public function testDisableInsuranceProductNotCallDisableProductIfNoProductFound(): void
    {
        $this->productRepository->method('get')->willThrowException(new NoSuchEntityException());
        $this->productHelper->expects($this->never())->method('disableProduct');
        $this->logger->expects($this->once())->method('info');
        $this->insuranceProductHelper->disableInsuranceProductIfExist();
    }

    public function testDisableInsuranceProductNotCallDisableProductIfProductFoundWithDisabledStatus(): void
    {
        $product = $this->getProductMock();
        $product->method('getStatus')->willReturn(Status::STATUS_DISABLED);
        $this->productHelper->expects($this->never())->method('disableProduct');
        $this->insuranceProductHelper->disableInsuranceProductIfExist();
    }

    public function testDisableInsuranceProductCallDisableProductIfProductFoundWithEnabledStatus(): void
    {
        $product = $this->getProductMock();
        $product->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $this->productHelper->expects($this->once())->method('disableProduct')->with($product);
        $this->insuranceProductHelper->disableInsuranceProductIfExist();
    }

    public function testDisableInsuranceProductThrowExceptionIfErrorOnSave(): void
    {
        $product = $this->getProductMock();
        $this->productHelper
            ->expects($this->once())
            ->method('disableProduct')
            ->with($product)
            ->willThrowException(new AlmaProductException('Error on save'));
        $this->expectException(AlmaInsuranceProductException::class);
        $this->insuranceProductHelper->disableInsuranceProductIfExist();
    }

    public function testEnableInsuranceProductThrowExceptionIfProductNotExist(): void
    {
        $this->productRepository->method('get')->willThrowException(new NoSuchEntityException());
        $this->expectException(AlmaInsuranceProductException::class);
        $this->insuranceProductHelper->enableInsuranceProductIfExist();
    }

    public function testEnableInsuranceProductNotCallEnableProductHelperForExistingProductWithEnableStatus(): void
    {
        $product = $this->getProductMock();
        $product->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $this->productHelper->expects($this->never())->method('enableProduct');
        $this->insuranceProductHelper->enableInsuranceProductIfExist();
    }

    private function getProductMock(): ProductInterface|MockObject
    {
        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->method('get')->willReturn($product);
        return $product;
    }


}
