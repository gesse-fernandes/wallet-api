<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Models\User;

interface TransactionRepositoryInterface
{
    public function create($data);

    public function findById($id);
    public function findUserTransactions($userId);


    public function findByIdUser($id);

    public function incrementBalance(User $user, float $amount);

    public function decrementBalance(User $user, float $amount);
}
