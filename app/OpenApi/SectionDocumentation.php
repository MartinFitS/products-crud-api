<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sections', description: 'Catálogo de secciones asignables a perfiles')]
class SectionDocumentation
{
    #[OA\Get(
        path: '/api/sections',
        operationId: 'sectionsIndex',
        summary: 'Obtiene todas las secciones disponibles',
        security: [['bearerAuth' => []]],
        tags: ['Sections'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado completo de secciones.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Secciones obtenidas correctamente.',
                    'data' => [[
                        'id' => '66f9f09a2aa3f63ef50b1b85',
                        'code' => 'SEC-000001',
                        'name' => 'Productos',
                        'slug' => 'products',
                        'is_system' => true,
                        'created_at' => '2026-09-28T20:00:00.000000Z',
                        'updated_at' => '2026-09-28T20:00:00.000000Z',
                    ]],
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/sections',
        operationId: 'sectionsStore',
        summary: 'Crea una sección con código automático',
        description: 'El slug es opcional; si se omite se genera a partir del nombre.',
        security: [['bearerAuth' => []]],
        tags: ['Sections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Reportes'),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 100, example: 'reports'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Sección creada.', content: new OA\JsonContent(ref: '#/components/schemas/SectionSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 422, description: 'Datos inválidos, nombre o slug duplicado.'),
        ]
    )]
    public function store(): void {}

    #[OA\Delete(
        path: '/api/sections/{id}',
        operationId: 'sectionsDestroy',
        summary: 'Elimina una sección personalizada no asignada',
        security: [['bearerAuth' => []]],
        tags: ['Sections'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sección eliminada.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Sección eliminada correctamente.',
                    'data' => null,
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Profiles.'),
            new OA\Response(response: 404, description: 'Sección no encontrada.'),
            new OA\Response(response: 422, description: 'La sección es del sistema o está asignada a un perfil.'),
        ]
    )]
    public function destroy(): void {}
}
