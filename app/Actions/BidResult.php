<?php

namespace App\Actions;

class BidResult
{
    /**
     * @param  array{bid: array{id: int, bidder_code: string, amount_aed: int, currency: string}, wallet: array{deposit_aed: int, reserved_aed: int, available_aed: int, currency: string}}|array{error: array{code: string, message: string}}  $body
     */
    public function __construct(public int $status, public array $body) {}
}
