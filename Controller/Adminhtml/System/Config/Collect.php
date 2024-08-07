<?php

namespace Alma\MonthlyPayments\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Collect extends Action
{

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @param FileFactory $fileFactory
     * @param Context $context
     */
    public function __construct(
        FileFactory $fileFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
    }

    /**
     * Collect alma logs
     *
     * @return ResponseInterface|ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $filepath = 'alma.log';
        $downloadedFileName = 'alma.log';
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::LOG);
    }
}
