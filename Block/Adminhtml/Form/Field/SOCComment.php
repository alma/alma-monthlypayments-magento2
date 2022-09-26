<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\View\LayoutFactory;

class SOCComment extends Field
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
     * @param Context $context
     * @param ResourceInterface $resource
     * @param LayoutFactory $layoutFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResourceInterface $resource,
        LayoutFactory $layoutFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->layoutFactory = $layoutFactory;
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
            "Alma\MonthlyPayments\Block\Adminhtml\System\SOCBlockLegal",
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
