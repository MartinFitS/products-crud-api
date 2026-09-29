<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'Administración y exportación de usuarios')]
class UserDocumentation
{
    #[OA\Get(
        path: '/api/users',
        operationId: 'usersIndex',
        summary: 'Lista usuarios con búsqueda, estado y paginación',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), example: 'martin'),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'), example: true),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Usuarios obtenidos correctamente.',
                    'data' => [[
                        'id' => '66f9f09a2aa3f63ef50b1b84',
                        'code' => 'USR-000001',
                        'name' => 'Administrador',
                        'email' => 'admin@example.com',
                        'phone' => null,
                        'photo' => 'users/USR-000001/photo.png',
                        'is_active' => true,
                        'profile_ids' => ['66f9f09a2aa3f63ef50b1b83'],
                        'created_at' => '2026-09-28T20:00:00+00:00',
                    ]],
                    'meta' => ['page' => 1, 'limit' => 10, 'total' => 1],
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/users',
        operationId: 'usersStore',
        summary: 'Crea un usuario',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'email', 'password', 'password_confirmation', 'photo'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Juan Pérez'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
                        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+523141234567'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary'),
                        new OA\Property(
                            property: 'profile_ids',
                            description: 'ObjectId existente en profiles. Puedes obtener los asignados al usuario actual desde /api/auth/me.',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado.', content: new OA\JsonContent(ref: '#/components/schemas/UserSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 422, description: 'Datos inválidos, email duplicado o perfil inexistente.'),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/users/{id}',
        operationId: 'usersShow',
        summary: 'Obtiene el detalle de un usuario y sus perfiles',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del usuario.', content: new OA\JsonContent(ref: '#/components/schemas/UserSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 404, description: 'Usuario no encontrado.'),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/api/users/{id}',
        operationId: 'usersUpdate',
        summary: 'Actualiza parcialmente un usuario; todos los campos son opcionales',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 150),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'phone', type: 'string', nullable: true),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary'),
                        new OA\Property(
                            property: 'profile_ids',
                            description: 'ObjectIds existentes en profiles.',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado.', content: new OA\JsonContent(ref: '#/components/schemas/UserSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 404, description: 'Usuario no encontrado.'),
            new OA\Response(response: 422, description: 'Datos inválidos.'),
        ]
    )]
    public function update(): void {}

    #[OA\Patch(
        path: '/api/users/{id}/status',
        operationId: 'usersStatus',
        summary: 'Activa o desactiva un usuario',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_active'],
                properties: [new OA\Property(property: 'is_active', type: 'boolean', example: false)],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado.', content: new OA\JsonContent(ref: '#/components/schemas/UserSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 404, description: 'Usuario no encontrado.'),
            new OA\Response(response: 422, description: 'Estado inválido.'),
        ]
    )]
    public function status(): void {}

    #[OA\Delete(
        path: '/api/users/{id}',
        operationId: 'usersDestroy',
        summary: 'Elimina físicamente un usuario',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario eliminado.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente.',
                    'data' => null,
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
            new OA\Response(response: 404, description: 'Usuario no encontrado.'),
        ]
    )]
    public function destroy(): void {}

    #[OA\Get(
        path: '/api/users/export/pdf',
        operationId: 'usersExportPdf',
        summary: 'Descarga el listado de usuarios en PDF',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo PDF.',
                content: new OA\MediaType(
                    mediaType: 'application/pdf',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
        ]
    )]
    public function exportPdf(): void {}

    #[OA\Get(
        path: '/api/users/export/excel',
        operationId: 'usersExportExcel',
        summary: 'Descarga el listado de usuarios en Excel',
        security: [['bearerAuth' => []]],
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo XLSX.',
                content: new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Users.'),
        ]
    )]
    public function exportExcel(): void {}
}
