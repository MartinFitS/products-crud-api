<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
    ) {}

    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        summary: 'Inicia sesión y emite un token Sanctum',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Admin123!'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inicio de sesión correcto.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Inicio de sesión correcto.',
                    'data' => [
                        'user' => [
                            'id' => '66f9f09a2aa3f63ef50b1b84',
                            'code' => 'USR-000001',
                            'name' => 'Administrador',
                            'email' => 'admin@example.com',
                            'phone' => null,
                            'photo' => null,
                            'profile_ids' => ['66f9f09a2aa3f63ef50b1b83'],
                        ],
                        'token' => '1|plain-text-token',
                        'token_type' => 'Bearer',
                    ],
                ])
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas.',
                content: new OA\JsonContent(example: [
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son inválidas.',
                    'errors' => null,
                ])
            ),
            new OA\Response(response: 422, description: 'Datos de entrada inválidos.'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->authService->login($credentials['email'], $credentials['password']);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son inválidas.',
                'errors' => null,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'data' => [
                'user' => UserResource::make(
                    $this->userService->findWithProfiles((string) $result['user']->getKey())
                )->resolve($request),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        operationId: 'authMe',
        summary: 'Obtiene el usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario autenticado.',
                content: new OA\JsonContent(
                    required: ['success', 'message', 'data'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario autenticado.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Token ausente o inválido.'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $this->userService->findWithProfiles((string) $request->user()->getKey());

        return response()->json([
            'success' => true,
            'message' => 'Usuario autenticado.',
            'data' => UserResource::make($user)->resolve($request),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'authLogout',
        summary: 'Revoca el token utilizado en la petición',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Sesión cerrada correctamente.',
                    'data' => null,
                ])
            ),
            new OA\Response(response: 401, description: 'Token ausente o inválido.'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logout($user);

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
            'data' => null,
        ]);
    }

    #[OA\Post(
        path: '/api/auth/forgot-password',
        operationId: 'authForgotPassword',
        summary: 'Solicita un enlace para restablecer la contraseña',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Respuesta genérica para evitar enumerar usuarios.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.',
                    'data' => null,
                ])
            ),
            new OA\Response(response: 422, description: 'Datos de entrada inválidos.'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.',
            'data' => null,
        ]);
    }

    #[OA\Post(
        path: '/api/auth/reset-password',
        operationId: 'authResetPassword',
        summary: 'Restablece la contraseña con un token vigente',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'token', type: 'string', example: 'token-recibido-por-correo'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NuevaPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NuevaPassword123!'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contraseña actualizada.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Contraseña actualizada correctamente.',
                    'data' => null,
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Token inválido o expirado.',
                content: new OA\JsonContent(example: [
                    'success' => false,
                    'message' => 'El token de recuperación es inválido o ha expirado.',
                    'errors' => ['token' => ['Solicita un nuevo enlace de recuperación.']],
                ])
            ),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $wasReset = $this->authService->resetPassword(
            $data['email'],
            $data['token'],
            $data['password'],
        );

        if (! $wasReset) {
            return response()->json([
                'success' => false,
                'message' => 'El token de recuperación es inválido o ha expirado.',
                'errors' => [
                    'token' => ['Solicita un nuevo enlace de recuperación.'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
            'data' => null,
        ]);
    }
}
