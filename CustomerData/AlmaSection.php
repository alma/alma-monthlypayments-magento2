<?php
namespace Alma\MonthlyPayments\CustomerData;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Alma\MonthlyPayments\Helpers\Logger;

class AlmaSection implements SectionSourceInterface
{
    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }
    public function getSectionData()
    {
        $this->logger->info('------------------Hello WORDLS',[]);
        return [
            'customdata' => "We are getting data from custom section",
        ];
    }
}
