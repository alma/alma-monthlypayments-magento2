<?php

namespace Alma\MonthlyPayments\Controller\Adminhtml\Insurance;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class SubscriptionDetails extends Action implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context     $context,
        PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;

        parent::__construct($context);
    }

    /**
     * Add the main Admin page
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->pageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('Alma Subscription details for order'));

        return $resultPage;
    }
}
