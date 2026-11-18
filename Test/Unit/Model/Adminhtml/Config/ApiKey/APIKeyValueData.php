<?php

namespace Alma\MonthlyPayments\Test\Unit\Model\Adminhtml\Config\ApiKey;

use Alma\MonthlyPayments\Model\Adminhtml\Config\ApiKey\APIKeyValue;

class APIKeyValueData extends APIKeyValue
{
    /**
     * Get the configured value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->getData('value');
    }

    /**
     * Get the configuration scope.
     *
     * @return mixed
     */
    public function getScope()
    {
        return $this->getData('scope');
    }

    /**
     * Get the configuration scope ID.
     *
     * @return mixed
     */
    public function getScopeId()
    {
        return $this->getData('scope_id');
    }
}
