<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditLogSuccessResponse',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Registro de bitácora obtenido correctamente.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuditLog'),
    ],
    type: 'object'
)]
class AuditLogSuccessResponseSchema {}
