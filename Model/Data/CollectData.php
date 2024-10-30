<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Lib\PayloadFormatter;
use Alma\MonthlyPayments\Api\Data\CollectDataInterface;
use Alma\MonthlyPayments\Helpers\CmsFeaturesDataHelper;
use Alma\MonthlyPayments\Helpers\CmsInfoDataHelper;

class CollectData implements CollectDataInterface
{

    private $payloadFormatter;
    private $cmsInfoDataHelper;
    private $cmsFeaturesDataHelper;

    public function __construct(
        PayloadFormatter  $payloadFormatter,
        CmsInfoDataHelper $cmsInfoDataHelper,
        CmsFeaturesDataHelper $cmsFeaturesDataHelper
    )
    {
        $this->payloadFormatter = $payloadFormatter;
        $this->cmsInfoDataHelper = $cmsInfoDataHelper;
        $this->cmsFeaturesDataHelper = $cmsFeaturesDataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $cmsInfo = new CmsInfo($this->cmsInfoDataHelper->getCmsInfoData());
        $cmsFeatures = new CmsFeatures($this->cmsFeaturesDataHelper->getCmsFeaturesData());
        return $this->payloadFormatter->formatConfigurationPayload($cmsInfo, $cmsFeatures);
    }
}
