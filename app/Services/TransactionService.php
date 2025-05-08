<?php

namespace App\Services;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected TransactionRepositoryInterface $transactionRepo;
    public function __construct(
        TransactionRepositoryInterface $transactionRepo

    ) {
        $this->transactionRepo = $transactionRepo;
    }

    public function transfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $payer = Auth::user();
            $payee = $this->transactionRepo->findByIdUser($data['payee_id']);

            if (!$payee || $payee->id === $payer->id) {
                throw new \Exception('Destinatário inválido.', 422);
            }

            if ($payer->balance < 0) {
                throw new \Exception('Transação não autorizada. Seu saldo atual está negativo.', 403);
            }

            if ($payer->balance < $data['amount']) {
                throw new \Exception('Saldo insuficiente para a transferência.', 403);
            }

            $this->transactionRepo->decrementBalance($payer, $data['amount']);
            $this->transactionRepo->incrementBalance($payee, $data['amount']);

            return $this->transactionRepo->create([
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'type' => 'transfer',
                'status' => 'completed',
                'amount' => $data['amount'],
                'metadata' => $data['metadata'] ?? [],
            ]);
        });
    }
}
