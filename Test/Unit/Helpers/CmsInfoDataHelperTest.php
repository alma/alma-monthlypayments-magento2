<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Alma\API\Client;
use Alma\MonthlyPayments\Helpers\CmsInfoDataHelper;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Magento\Theme\Model\Theme;
use PHPUnit\Framework\TestCase;

class CmsInfoDataHelperTest extends TestCase
{
    private $productMetadata;
    private $moduleList;
    private $themeCollectionFactory;
    private $cmsInfoDataHelper;
    protected function setUp(): void
    {
        $this->productMetadata = $this->createMock(ProductMetadata::class);
        $this->productMetadata->method('getVersion')->willReturn('2.4.0');
        $this->moduleList = $this->createMock(ModuleListInterface::class);
        $this->moduleList->method('getOne')->willReturn(['setup_version' => '1.0.0']);
        $this->themeCollectionFactory = $this->createMock(CollectionFactory::class);
        $parentTheme = $this->getMockBuilder(Theme::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getVersion',
                'getThemeTitle',
            ])
            ->getMock();
        $childTheme = $this->getMockBuilder(Theme::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getParentId',
                'getVersion',
                'getThemeTitle',
            ])
            ->getMock();

        $parentTheme->method('getThemeTitle')->willReturn('Parent Theme');
        $parentTheme->method('getVersion')->willReturn('1.0.0');

        $childTheme->method('getParentId')->willReturn(1);
        $childTheme->method('getThemeTitle')->willReturn('Child Theme');
        $childTheme->method('getVersion')->willReturn('2.0.0');

        $iterator = new \ArrayIterator([$parentTheme, $childTheme]);
        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn($iterator);
        $this->themeCollectionFactory->method('create')->willReturn($collection);
        $this->cmsInfoDataHelper = new CmsInfoDataHelper(
            $this->productMetadata,
            $this->moduleList,
            $this->themeCollectionFactory
        );

    }

    public function testOutput()
    {
        $this->moduleList->method('getAll')->willReturn([]);
        $this->assertEquals([
            'cms_name' => 'Adobe Commerce',
            'cms_version' => '2.4.0',
            'third_parties_plugins' => [],
            'theme_name' => 'Parent Theme / Child Theme',
            'theme_version' => '1.0.0 / 2.0.0',
            'language_name' => 'PHP',
            'language_version' => phpversion(),
            'alma_plugin_version' => '1.0.0',
            'alma_sdk_version' => Client::VERSION,
            'alma_sdk_name' => 'alma/alma-php-client',
        ], $this->cmsInfoDataHelper->getCmsInfoData());
    }

}
