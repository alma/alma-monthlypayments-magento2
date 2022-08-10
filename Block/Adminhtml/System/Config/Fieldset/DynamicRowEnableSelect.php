<?php

namespace Alma\MonthlyPayments\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\View\Element\Html\Select;

class DynamicRowEnableSelect extends Select
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param $value
     *
     * @return DynamicRowEnableSelect
     */
    public function setInputId($value): DynamicRowEnableSelect
    {
        return $this->setId($value);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }
    private function getSourceOptions()
    {
        return [
            ['label' => 'Yes', 'value' => '1'],
            ['label' => 'No', 'value' => '0'],
        ];
    }
}
