<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset\DynamicRowEnableSelect;
use Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset\DynamicRowText;
use Alma\MonthlyPayments\Helpers\Logger;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class FeePlansConfigFrontModel extends AbstractFieldArray
{
    const SMALL_COLUMN_STYLE = 'width:50px';
    const SMALL_STRING_COLUMN = 'width:75px;text-align:center;font-style:italic;padding:8px 0 15px 0';
    const SMALL_INPUT_COLUMN = 'width:75px;';
    const BIG_STRING_COLUMN = 'width:150px;font-weight:bold;padding:8px 0 15px 0';
    /**
     * @var BlockInterface
     */
    private $selectOptions;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var DynamicRowText|BlockInterface
     */
    private $renderString;


    /**
     * @param Context $context
     * @param Logger $logger
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        Logger $logger,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->setTemplate('system/config/form/field/array.phtml');
        parent::__construct(
            $context,
            $data,
            $secureRenderer
        );
        $this->logger = $logger;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'pnx_label',
            [
                'label' => __('Payment plan'),
                'style' => self::BIG_STRING_COLUMN,
                'renderer' => $this->getTextStyle()
            ]
        );
        $this->addColumn(
            'enabled',
            [
                'label' => __('Enabled'),
                'style' => 'width:130px;font-weight:bold;padding:0 0 0px 0',
                'renderer' => $this->getSelectFieldOptions(),
            ]
        );
        $this->addColumn(
            'min_purchase_amount',
            [
                'label' => __('Min amount'),
                'style' => self::SMALL_STRING_COLUMN,
                'renderer' => $this->getTextStyle(),
            ]
        );
        $this->addColumn(
            'custom_min_purchase_amount',
            [
                'label' => __('Min display amount'),
                'style' => self::SMALL_INPUT_COLUMN,
                'class' => 'validate-number'
            ]
        );
        $this->addColumn(
            'custom_max_purchase_amount',
            [
                'label' => __('Max display amount'),
                'style' => self::SMALL_INPUT_COLUMN,
                'class' => 'validate-number'
            ]
        );
        $this->addColumn(
            'max_purchase_amount',
            [
                'label' => __('Max amount'),
                'style' => self::SMALL_STRING_COLUMN,
                'renderer' => $this->getTextStyle(),
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add More');
    }

    /**
     * @param DataObject $row
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];
        $selectFieldData = $row->getSelectField();
        if ($selectFieldData !== null) {
            $this->logger->info('$selectFieldData', [$selectFieldData]);
            $options['option_' . $this->getSelectFieldOptions()->calcOptionHash($selectFieldData)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }


    /**
     * @return BlockInterface
     * @throws LocalizedException
     */
    private function getSelectFieldOptions(): BlockInterface
    {
        if (!$this->selectOptions) {
            $this->selectOptions = $this->getLayout()->createBlock(
                DynamicRowEnableSelect::class,
                '',
                ['data' => []]
            );
        }
        return $this->selectOptions;
    }

    /**
     * @return BlockInterface
     * @throws LocalizedException
     */
    private function getTextStyle(): BlockInterface
    {
        if (!$this->renderString) {
            $this->renderString = $this->getLayout()->createBlock(
                DynamicRowText::class,
                '',
                ['data' => []]
            );
        }
        return $this->renderString;
    }
}
