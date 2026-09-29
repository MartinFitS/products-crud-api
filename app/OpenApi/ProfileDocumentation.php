<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Profiles', description: 'Administración y exportación de perfiles de acceso')]
class ProfileDocumentation
{
    #[OA\Get(
        path: '/api/profiles',
        operationId: 'profilesIndex',
        summary: 'Lista perfiles con búsqueda y paginación',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), example: 'Administrador'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Perfiles obtenidos correctamente.',
                    'data' => [[
                        'id' => '66f9f09a2aa3f63ef50b1b83',
                        'code' => 'PRF-000001',
                        'name' => 'Administrador',
                        'sections' => ['products', 'users', 'profiles'],
                        'created_at' => '2026-09-28T20:00:00.000000Z',
                        'updated_at' => '2026-09-28T20:00:00.000000Z',
                    ]],
                    'meta' => ['page' => 1, 'limit' => 10, 'total' => 1],
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/profiles',
        operationId: 'profilesStore',
        summary: 'Crea un perfil y genera su código automáticamente',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'sections'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Operador de productos'),
                    new OA\Property(
                        property: 'sections',
                        description: 'Slugs existentes obtenidos desde GET /api/sections.',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['products']
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Perfil creado.', content: new OA\JsonContent(ref: '#/components/schemas/ProfileSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 422, description: 'Datos inválidos o nombre duplicado.'),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/profiles/{id}',
        operationId: 'profilesShow',
        summary: 'Obtiene el detalle de un perfil',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del perfil.', content: new OA\JsonContent(ref: '#/components/schemas/ProfileSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 404, description: 'Perfil no encontrado.'),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/profiles/{id}',
        operationId: 'profilesUpdate',
        summary: 'Actualiza el nombre o las secciones de un perfil',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Supervisor'),
                    new OA\Property(
                        property: 'sections',
                        description: 'Slugs existentes obtenidos desde GET /api/sections.',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['products', 'users']
                    ),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Perfil actualizado.', content: new OA\JsonContent(ref: '#/components/schemas/ProfileSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 404, description: 'Perfil no encontrado.'),
            new OA\Response(response: 422, description: 'Datos inválidos o nombre duplicado.'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/profiles/{id}',
        operationId: 'profilesDestroy',
        summary: 'Elimina un perfil que no esté asignado a usuarios',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil eliminado.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Perfil eliminado correctamente.',
                    'data' => null,
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 404, description: 'Perfil no encontrado.'),
            new OA\Response(response: 422, description: 'El perfil todavía está asignado a usuarios.'),
        ]
    )]
    public function destroy(): void {}

    #[OA\Get(
        path: '/api/profiles/export/pdf',
        operationId: 'profilesExportPdf',
        summary: 'Descarga el listado de perfiles en PDF',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
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
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
        ]
    )]
    public function exportPdf(): void {}

    #[OA\Get(
        path: '/api/profiles/export/excel',
        operationId: 'profilesExportExcel',
        summary: 'Descarga el listado de perfiles en Excel',
        security: [['bearerAuth' => []]],
        tags: ['Profiles'],
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
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
        ]
    )]
    public function exportExcel(): void {}
}
