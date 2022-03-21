<?php

namespace Alma\MonthlyPayments\Controller\Adminhtml\System\Config;

use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class Collect extends Action
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param FileFactory $fileFactory
     * @param Context $context
     * @param Logger $logger
     */
    public function __construct(
        FileFactory $fileFactory,
        Context $context,
        Logger $logger
    )
    {
    parent::__construct($context);
        $this->logger = $logger;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $filepath = 'alma.log';
        $downloadedFileName = 'alma.log';
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::LOG);
    }

}
