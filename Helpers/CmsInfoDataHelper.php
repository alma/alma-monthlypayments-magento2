<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\API\Client;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;


class CmsInfoDataHelper
{

    private $productMetadata;
    private $moduleList;
    private $themeCollectionFactory;

    /**
     * @param ProductMetadata $productMetadata
     * @param ModuleListInterface $moduleList
     * @param CollectionFactory $themeCollectionFactory
     */
    public function __construct(
        ProductMetadata     $productMetadata,
        ModuleListInterface $moduleList,
        CollectionFactory   $themeCollectionFactory
    )
    {
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->themeCollectionFactory = $themeCollectionFactory;
    }

    /**
     * Return data for CmsInfo Object
     *
     * @return array
     */
    public function getCmsInfoData(): array
    {
        $themeDataArray = $this->getCurrentThemeNameAndVersion();

        return [
            'cms_name' => 'Adobe Commerce',
            'cms_version' => $this->productMetadata->getVersion(),
            'third_parties_plugins' => $this->getThirdPartyModules(),
            'theme_name' => $themeDataArray['name'],
            'theme_version' => $themeDataArray['version'],
            'language_name' => 'PHP',
            'language_version' => phpversion(),
            'alma_plugin_version' => $this->getModuleVersion(),
            'alma_sdk_version' => Client::VERSION,
            'alma_sdk_name' => 'alma/alma-php-client',
        ];
    }


    /**
     * Get third party modules list without Magento modules
     *
     * @return array
     */
    private function getThirdPartyModules(): array
    {
        $thirdPartyModules = [];

        foreach ($this->moduleList->getAll() as $moduleName => $moduleInfo) {
            if (!str_starts_with($moduleName, 'Magento_')) {
                $thirdPartyModules[] = ['name' => $moduleName, 'version' => $moduleInfo['setup_version']];
            }
        }

        return $thirdPartyModules;
    }

    /**
     * Get alma module version (no check installed, it's current module )
     *
     * @return string
     */
    private function getModuleVersion(): string
    {
        $moduleInfo = $this->moduleList->getOne('Alma_MonthlyPayments');
        return $moduleInfo['setup_version'];
    }

    /**
     * Get All frontend theme name and version with parent and child theme
     *
     * @return array
     */
    public function getCurrentThemeNameAndVersion(): array
    {
        $themes = $this->themeCollectionFactory->create();
        $themes->addFieldToFilter('area', 'frontend');
        $themeDataArray = [];
        $themeName = '';
        $themeVersion = '';
        foreach ($themes as $theme) {
            if (!$theme->getParentId()) {
                $themeName .= $theme->getThemeTitle();
                $themeVersion .= $theme->getVersion();
            }
            if ($theme->getParentId()) {
                $themeName .= ' / ' . $theme->getThemeTitle();
                $themeVersion .= ' / ' . $theme->getVersion();
            }
        }
        $themeDataArray['name'] = $themeName;
        $themeDataArray['version'] = $themeVersion;
        return $themeDataArray;
    }

}
