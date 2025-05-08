<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionControllerApi extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }
    /**
     * @OA\Post(
     *     path="/api/transactions/transfer",
     *     summary="Realiza uma transferência entre usuários",
     *     tags={"Transações"},
     *      security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "payee_id"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.50),
     *             @OA\Property(property="payee_id", type="integer", example=2),
     *             @OA\Property(property="metadata", type="object", example={"descricao": "pagamento de serviço"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transferência realizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transferência realizada com sucesso"),
     *             @OA\Property(property="transaction", type="object"),
     *             @OA\Property(property="balance", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Saldo insuficiente ou bloqueado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos"
     *     )
     * )
     */
    public function transfer(TransferRequest $request)
    {
        try {
            $transaction = $this->transactionService->transfer($request->validated());

            return response()->json([
                'message' => 'Transferência realizada com sucesso.',
                'transaction' => $transaction,
                'balance' => Auth::user()->balance,

            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/transactions/deposit",
     *     summary="Realiza um depósito na carteira do usuário autenticado",
     *     description="Usuários autenticados podem realizar depósitos. O saldo será atualizado.",
     *     tags={"Transações"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Depósito realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Depósito realizado com sucesso."),
     *             @OA\Property(property="transaction", type="object"),
     *             @OA\Property(property="balance", type="number", format="float", example=1200.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Erro interno ao processar depósito.")
     *         )
     *     )
     * )
     */

    public function deposit(DepositRequest $request)
    {
        try {
            $transaction = $this->transactionService->deposit($request->validated());

            return response()->json([
                'message' => 'Depósito realizado com sucesso.',
                'transaction' => $transaction,
                'balance' => Auth::user()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar o depósito: ' . $e->getMessage(),
            ], $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/reverse/{id}",
     *     summary="Reverte uma transação",
     *     tags={"Transações"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da transação original a ser revertida",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transação revertida com sucesso.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transação revertida com sucesso."),
     *             @OA\Property(property="transaction", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Você não tem permissão para reverter esta transação."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Transação inválida ou já revertida."
     *     )
     * )
     */
    public function reverse(int $id, Request $request)
    {
        try {
            $transaction = $this->transactionService->reverse($id, $request->input('reason'));


            return response()->json([
                'message' => 'Transação revertida com sucesso.',
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/transactions/statement",
     *     summary="Consulta o extrato do usuário autenticado",
     *     tags={"Transações"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Extrato consultado com sucesso.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Extrato consultado com sucesso."),
     *             @OA\Property(
     *                 property="transactions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="deposit"),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(property="amount", type="number", format="float", example=150.00),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-08T16:02:53.000000Z")
     *                 )
     *             ),
     *             @OA\Property(property="balance", type="number", format="float", example=1200.50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Você não tem transações registradas."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno no servidor."
     *     )
     * )
     */

    public function statement()
    {
        try {
            $result = $this->transactionService->getUserStatement();

            return response()->json([
                'message' => 'Extrato consultado com sucesso.',
                'transactions' => $result['transactions'],
                'balance' => $result['balance'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
