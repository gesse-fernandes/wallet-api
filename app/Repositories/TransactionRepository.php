<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    public function findUserTransactions(int $userId): iterable
    {
        return Transaction::where('payer_id', $userId)
            ->orWhere('payee_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function findByIdUser(int $id)
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
