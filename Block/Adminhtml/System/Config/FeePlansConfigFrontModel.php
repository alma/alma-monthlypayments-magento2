<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config;

use Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset\DynamicRowEnableSelect;
use Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset\DynamicRowText;
use Alma\MonthlyPayments\Helpers\PaymentPlansHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

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
     * @var DynamicRowText|BlockInterface
     */
    private $renderString;
    /**
     * @var PaymentPlansHelper
     */
    private $paymentPlansHelper;

    /**
     * @param PaymentPlansHelper $paymentPlansHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        PaymentPlansHelper $paymentPlansHelper,
        Context $context,
        array $data = []
    ) {
        $this->setTemplate('system/config/form/field/array.phtml');
        parent::__construct(
            $context,
            $data
        );
        $this->paymentPlansHelper = $paymentPlansHelper;
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
            PaymentPlansHelper::CUSTOM_MIN_PURCHASE_KEY,
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
    /**
     * Get the grid and scripts contents
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setComment($this->getHtmlComment($element->getValue()));
        $this->setElement($element);
        $html = $this->_toHtml();
        $this->_arrayRowsCache = null;
        // doh, the object is used as singleton!
        return $html;
    }

    /**
     * @param array $feePlans
     *
     * @return string
     */
    private function getHtmlComment(array $feePlans): string
    {
        $html = '<div style="font-size: x-small" data-toggle="collapse" ><p>' . __('Fees applied to each transaction :') . '</p>';
        foreach ($feePlans as $key => $plan) {
            $customerFees = $plan['fee']['customer'];
            $merchantFees = $plan['fee']['merchant'];

            $html .= '<p class="content">';
            $html .= '<b>' . $this->paymentPlansHelper->planLabelByKey($key) . '</b> : ';
            if ($merchantFees['merchant_fee_variable'] != 0) {
                $html .= ' ' . __('Merchant fee variable:') . ' ' . intval($merchantFees['merchant_fee_variable']) / 100 . '%';
            }
            if ($merchantFees['merchant_fee_fixed'] != 0) {
                $html .= ' ' . __('Merchant fee fixed:') . ' ' . intval($merchantFees['merchant_fee_variable']) / 100 . '%';
            }
            $html .= ' <b>-</b> ';
            if ($customerFees['customer_fee_fixed'] == 0 && $customerFees['customer_fee_variable'] == 0) {
                $html .= ' ' . __('Customer fee:') . ' ' . __('no fees');
            }
            if ($customerFees['customer_fee_fixed'] != 0) {
                $html .= ' ' . __('Customer fixed fee:') . ' ' . intval($customerFees['customer_fee_fixed']) / 100 . '%';
            }
            if ($customerFees['customer_fee_variable'] != 0) {
                $html .= ' ' . __('Customer variable fee:') . ' ' . intval($customerFees['customer_fee_variable']) / 100 . '%';
            }
            $html .= '</p>';
        }
        $html .= '</div>';
        return $html;
    }
}
