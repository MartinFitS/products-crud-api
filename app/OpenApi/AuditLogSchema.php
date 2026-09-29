<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditLog',
    required: ['id', 'actor', 'action', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1ba0'),
        new OA\Property(property: 'actor', ref: '#/components/schemas/AuditActor'),
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['created', 'updated', 'status_updated', 'deleted'],
            example: 'updated'
        ),
        new OA\Property(
            property: 'auditable_type',
            type: 'string',
            enum: ['product', 'user', 'profile'],
            example: 'product'
        ),
        new OA\Property(property: 'auditable_id', type: 'string', example: '66f9f09a2aa3f63ef50b1b90'),
        new OA\Property(
            property: 'old_values',
            type: 'object',
            nullable: true,
            example: ['code' => 'PRD-000001', 'name' => 'Teclado', 'brand' => 'Marca anterior', 'price' => 899.99]
        ),
        new OA\Property(
            property: 'new_values',
            type: 'object',
            nullable: true,
            example: ['code' => 'PRD-000001', 'name' => 'Teclado', 'brand' => 'Marca nueva', 'price' => 899.99]
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-09-29T20:21:01+00:00'),
    ],
    type: 'object'
)]
class AuditLogSchema {}
