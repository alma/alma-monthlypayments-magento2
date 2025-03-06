<?php

namespace Alma\MonthlyPayments\Setup\Patch\Data;

use Alma\API\Lib\IntegrationsConfigurationsUtils;
use Alma\MonthlyPayments\Helpers\CollectCmsConfigHelper;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AlmaUpdateSendConfigUrl implements DataPatchInterface
{
    /**
     * @var CollectCmsConfigHelper
     */
    private $collectCmsConfigHelper;

    /**
     * @param CollectCmsConfigHelper $collectCmsConfigHelper
     */
    public function __construct(
        CollectCmsConfigHelper $collectCmsConfigHelper
    ) {

        $this->collectCmsConfigHelper = $collectCmsConfigHelper;
    }

    /**
     * Apply migration send url if necessary
     *
     * @return void
     */
    public function apply(): void
    {
        if (IntegrationsConfigurationsUtils::isUrlRefreshRequired((int)$this->collectCmsConfigHelper->getSendCollectUrlStatus())) {
            $this->collectCmsConfigHelper->sendIntegrationsConfigurationsUrl();
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
