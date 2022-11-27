<?php

declare(strict_types=1);

namespace FireflyIII\Services\Transactions;

use FireflyIII\Models\Transaction;

final class FindTransaction
{
    public function find($transactionDescription, $amount, $dateString): ?Transaction
    {
        return Transaction::with('transactionJournal')
            ->where('description', $transactionDescription)
            ->where('amount', number_format($amount, 2) . '0000000000000000000000')
            ->whereHas('transactionJournal', function($query) use ($dateString) {
                $query->where('date', $dateString);
            })
            ->first();
    }
}