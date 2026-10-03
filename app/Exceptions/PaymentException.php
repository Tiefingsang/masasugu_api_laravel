<?php

namespace App\Exceptions;

use Exception;

/**
 * PaymentException — Exception custom pour tous les problèmes de paiement
 */
class PaymentException extends Exception
{
    protected array $context = [];

    public function __construct(string $message, int $code = 0, array $context = [])
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
