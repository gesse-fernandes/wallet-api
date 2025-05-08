<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create($data)
    {
        return Transaction::create($data);
    }

    public function findById($id)
    {
        return Transaction::find($id);
    }

    public function findUserTransactions($userId)
    {
        return Transaction::where('payer_id', $userId)
            ->orWhere('payee_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByIdUser($id)
    {
        return User::find($id);
    }

    public function incrementBalance(User $user, float $amount)
    {
        $user->balance += $amount;
        $user->save();
    }

    public function decrementBalance(User $user, float $amount)
    {
        $user->balance -= $amount;
        $user->save();
    }
}
