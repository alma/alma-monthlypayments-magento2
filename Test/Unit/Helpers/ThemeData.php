<?php

namespace Alma\MonthlyPayments\Test\Unit\Helpers;

use Magento\Theme\Model\Theme;

class ThemeData extends Theme
{
    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->_data = $data;
    }
}
