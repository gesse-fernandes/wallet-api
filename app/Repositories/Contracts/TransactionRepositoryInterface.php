<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Models\User;

interface TransactionRepositoryInterface
{
    public function create(array $data);

    public function findById(int $id);

    public function findUserTransactions(int $userId);
    public function findByIdUser(int $id);

    public function incrementBalance(User $user, float $amount);

    public function decrementBalance(User $user, float $amount);
}
