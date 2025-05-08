<?php



namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerApi extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Cadastro"},
     *     summary="Registra um novo usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "cpf_cnpj", "password", "password_confirmation", "street", "number", "neighborhood", "city", "state", "zipcode"},
     *             @OA\Property(property="name", type="string", example="João da Silva"),
     *             @OA\Property(property="email", type="string", example="joao@email.com"),
     *             @OA\Property(property="cpf_cnpj", type="string", example="00000000000"),
     *             @OA\Property(property="password", type="string", example="Senha123!"),
     *             @OA\Property(property="password_confirmation", type="string", example="Senha123!"),
     *             @OA\Property(property="street", type="string", example="Rua A"),
     *             @OA\Property(property="number", type="string", example="123"),
     *             @OA\Property(property="neighborhood", type="string", example="Centro"),
     *             @OA\Property(property="city", type="string", example="São Paulo"),
     *             @OA\Property(property="state", type="string", example="SP"),
     *             @OA\Property(property="zipcode", type="string", example="12345-678")
     *         )
     *     ),
     * @OA\Header(
     *         header="X-CSRF-TOKEN",
     *         required=false,
     *         description="Token CSRF necessário em alguns ambientes",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário registrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="João da Silva"),
     *             @OA\Property(property="token", type="string", example="Bearer eyJ..")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno no servidor"
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            $token = JWTAuth::fromUser($user);

            return response()->json([
                "id" => $user->id,
                "name" => $user->name,
                "token" => 'Bearer ' . $token,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Autenticação"},
     *     summary="Realiza o login de um usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="joao@email.com"),
     *             @OA\Property(property="password", type="string", example="Senha123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login bem-sucedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="João da Silva"),
     *             @OA\Property(property="token", type="string", example="Bearer eyJ...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas"
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $response = $this->authService->login($request->validated());

            return response()->json([
                'id' => $response['user']->id,
                'name' => $response['user']->name,
                'token' => $response['token'],
            ], 200);
        } catch (\Throwable $e) {
            $code = $e->getMessage() === 'Credenciais inválidas.' ? 401 : 500;

            return response()->json([
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Autenticação"},
     *     summary="Faz logout do usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao realizar logout"
     *     )
     * )
     */
    public function logout()
    {
        try {
            $this->authService->logout();

            return response()->json([
                'message' => 'Logout realizado com sucesso.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
