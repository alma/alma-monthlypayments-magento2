<?php

namespace Alma\MonthlyPayments\Helpers\Exceptions;

use Alma\API\Exceptions\AlmaException;

class AlmaClientException extends AlmaException
{
    /**
     * Get exception message same as Request error
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }
}
