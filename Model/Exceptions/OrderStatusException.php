<?php

namespace Alma\MonthlyPayments\Model\Exceptions;

use Exception;

class OrderStatusException extends Exception
{
    public function __construct($message, $logger, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $logger->warning($message);
        if (isset($previous)) {
            $logger->warning(sprintf('Previous exception message : %s', $previous->getMessage()));
        }
    }
}
