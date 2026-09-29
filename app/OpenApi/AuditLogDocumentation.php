<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Audit Logs', description: 'Consulta y exportación de la bitácora inmutable de actividad')]
class AuditLogDocumentation
{
    #[OA\Get(
        path: '/api/audit-logs',
        operationId: 'auditLogsIndex',
        summary: 'Lista la bitácora con búsqueda, filtros y paginación',
        description: 'Requiere que alguno de los perfiles del usuario incluya la sección audit-logs.',
        security: [['bearerAuth' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10)),
            new OA\Parameter(name: 'search', description: 'Busca por responsable, correo, código, nombre, acción o entidad.', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100), example: 'Administrador'),
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string', enum: ['created', 'updated', 'status_updated', 'deleted'])),
            new OA\Parameter(name: 'auditable_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['product', 'user', 'profile'])),
            new OA\Parameter(name: 'date_from', description: 'Fecha inicial inclusiva.', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-09-01'),
            new OA\Parameter(name: 'date_to', description: 'Fecha final inclusiva.', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-09-30'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de actividad.',
                content: new OA\JsonContent(
                    required: ['success', 'message', 'data', 'meta'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Bitácora obtenida correctamente.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AuditLog')),
                        new OA\Property(
                            property: 'meta',
                            required: ['page', 'limit', 'total'],
                            properties: [
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'limit', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 16),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Audit Logs.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/audit-logs/{id}',
        operationId: 'auditLogsShow',
        summary: 'Obtiene un registro inmutable de la bitácora',
        security: [['bearerAuth' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la actividad.', content: new OA\JsonContent(ref: '#/components/schemas/AuditLogSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Audit Logs.'),
            new OA\Response(response: 404, description: 'Registro de bitácora no encontrado.'),
        ]
    )]
    public function show(): void {}

    #[OA\Get(
        path: '/api/audit-logs/export/pdf',
        operationId: 'auditLogsExportPdf',
        summary: 'Exporta la bitácora filtrada en PDF',
        security: [['bearerAuth' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string', enum: ['created', 'updated', 'status_updated', 'deleted'])),
            new OA\Parameter(name: 'auditable_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['product', 'user', 'profile'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Archivo PDF.', content: new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Audit Logs.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function exportPdf(): void {}

    #[OA\Get(
        path: '/api/audit-logs/export/excel',
        operationId: 'auditLogsExportExcel',
        summary: 'Exporta la bitácora filtrada en Excel',
        security: [['bearerAuth' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100)),
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string', enum: ['created', 'updated', 'status_updated', 'deleted'])),
            new OA\Parameter(name: 'auditable_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['product', 'user', 'profile'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Archivo XLSX.', content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El usuario no tiene acceso a Audit Logs.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function exportExcel(): void {}
}
