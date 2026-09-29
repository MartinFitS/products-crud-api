<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditActor',
    required: ['id', 'code', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1b84'),
        new OA\Property(property: 'code', type: 'string', example: 'USR-000001'),
        new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
    ],
    type: 'object',
    nullable: true
)]
class AuditActorSchema {}
