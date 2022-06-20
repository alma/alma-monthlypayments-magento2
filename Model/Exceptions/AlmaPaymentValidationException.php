<?php

namespace Alma\MonthlyPayments\Model\Exceptions;

use Exception;

class AlmaPaymentValidationException extends Exception
{
    const RETURN_PATH = 'checkout/onepage/failure';
    private $returnPath;

    /**
     * AlmaPaymentValidationError constructor.
     * @param string $message
     * @param string $returnPath
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $returnPath = self::RETURN_PATH, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->returnPath = $returnPath;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getReturnPath(): string
    {
        return $this->returnPath;
    }
}
