<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }
}
