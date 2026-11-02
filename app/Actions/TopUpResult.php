<?php

namespace App\Actions;

class TopUpResult
{
    /**
     * @param  array{wallet: array{deposit_aed: int, reserved_aed: int, available_aed: int, currency: string}, activity: array{id: int, type: string, amount_aed: int, currency: string, created_at: string}}|array{error: array{code: string, message: string}}  $body
     */
    public function __construct(public int $status, public array $body) {}
}
