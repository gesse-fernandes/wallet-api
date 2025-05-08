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
            $metadata = [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),

            ];
            return $this->transactionRepo->create([
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'type' => 'transfer',
                'status' => 'completed',
                'amount' => $data['amount'],
                'metadata' => $metadata ?? [],
            ]);
        });
    }

    public function deposit(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $payee = Auth::user();

            $metadata = [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),

            ];
            $this->transactionRepo->incrementBalance($payee, $data['amount']);

            return $this->transactionRepo->create([
                'payer_id' => null,
                'payee_id' => $payee->id,
                'type' => 'deposit',
                'status' => 'completed',
                'amount' => $data['amount'],
                'metadata' => $metadata ?? [],
            ]);
        });
    }

    public function reverse($transactionId,  $reason = null)
    {
        return DB::transaction(function () use ($transactionId, $reason) {
            $user = Auth::user();
            $original = $this->transactionRepo->findById($transactionId);

            if (!$original || $original->status !== 'completed') {
                throw new \Exception('Transação não encontrada ou não pode ser revertida.', 400);
            }

            if ($original->payer_id !== $user->id && $original->payee_id !== $user->id) {
                throw new \Exception('Você não tem permissão para reverter esta transação.', 403);
            }

            // Reverter valores
            if ($original->type === 'transfer') {
                $this->transactionRepo->decrementBalance($original->payee, $original->amount);
                $this->transactionRepo->incrementBalance($original->payer, $original->amount);
            } elseif ($original->type === 'deposit') {
                $this->transactionRepo->decrementBalance($original->payee, $original->amount);
            } else {
                throw new \Exception('Tipo de transação inválido para reversão.', 422);
            }

            $metadata = [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason ?? 'Reversão solicitada'

            ];
            $original->status = 'reversed';
            $original->reversed_transaction_id = $original->id;
            $original->metadata = $metadata;
            $original->save();

            return [
                $original->type,
                $user->balance,

            ];
        });
    }

    public function getUserStatement()
    {
        $user = Auth::user();
        $transactions = $this->transactionRepo->findUserTransactions($user->id);

        if ($transactions->isEmpty()) {
            throw new \Exception('Você não tem transações registradas.', 404);
        }

        return [
            'transactions' => $transactions,
            'balance' => $user->balance,
        ];
    }
}
