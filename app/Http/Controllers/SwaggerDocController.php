<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API - Carteira Financeira",
 *     description="Documentação da API de autenticação, cadastro e operações financeiras.",
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor de desenvolvimento"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Insira o token JWT no formato: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints de registro e login"
 * )
 *
 * @OA\Tag(
 *     name="Transações",
 *     description="Operações de saldo e transferências"
 * )
 */
class SwaggerDocController extends Controller {}
