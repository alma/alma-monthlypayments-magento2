<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Alma\MonthlyPayments\Block\Adminhtml\System\SOCBlockLegal;
use Alma\MonthlyPayments\Helpers\ApiConfigHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\View\LayoutFactory;

class SOCFieldFrontModel extends Field
{
    public const POSITION = 'comment';
    /**
     * @var ResourceInterface
     */
    private $resource;
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @var ApiConfigHelper
     */
    private $apiConfigHelper;

    /**
     * @param Context $context
     * @param ResourceInterface $resource
     * @param LayoutFactory $layoutFactory
     * @param ApiConfigHelper $apiConfigHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResourceInterface $resource,
        LayoutFactory $layoutFactory,
        ApiConfigHelper $apiConfigHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->layoutFactory = $layoutFactory;
        $this->apiConfigHelper = $apiConfigHelper;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($this->apiConfigHelper->getActiveMode() === ApiConfigHelper::TEST_MODE_KEY) {
            return '';
        }
        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element):string
    {
        $layout = $this->layoutFactory->create();
        $blockOption = $layout->createBlock(
            SOCBlockLegal::class,
            '',
            ['data'=>[
                'link' => false,
                'position' => self::POSITION
            ]]
        )->setTemplate("Alma_MonthlyPayments::system/soc-legal.phtml");
        $element->setComment($blockOption->toHtml());
        return $element->getElementHtml();
    }
}
