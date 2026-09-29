<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Api apra el examen de admision a TAP',
    description: 'API REST del examen técnico TAP Admission.'
)]
#[OA\Server(url: '/', description: 'API')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token personal emitido por el endpoint de login.'
)]
#[OA\Tag(name: 'Auth', description: 'Autenticación y recuperación de contraseña')]
class ApiDocumentation {}

#[OA\Schema(
    schema: 'User',
    required: ['id', 'code', 'name', 'email', 'is_active', 'profile_ids'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1b84'),
        new OA\Property(property: 'code', type: 'string', example: 'USR-000001'),
        new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'photo', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'profile_ids',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['66f9f09a2aa3f63ef50b1b83']
        ),
        new OA\Property(
            property: 'profiles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Profile')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class UserSchema {}

#[OA\Schema(
    schema: 'Profile',
    required: ['id', 'code', 'name', 'sections', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1b83'),
        new OA\Property(property: 'code', type: 'string', example: 'PRF-000001'),
        new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
        new OA\Property(
            property: 'sections',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['products', 'users', 'profiles']
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class ProfileSchema {}

#[OA\Schema(
    schema: 'ProfileSuccessResponse',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Perfil obtenido correctamente.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Profile'),
    ],
    type: 'object'
)]
class ProfileSuccessResponseSchema {}

#[OA\Schema(
    schema: 'Section',
    required: ['id', 'code', 'name', 'slug', 'is_system', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1b85'),
        new OA\Property(property: 'code', type: 'string', example: 'SEC-000001'),
        new OA\Property(property: 'name', type: 'string', example: 'Productos'),
        new OA\Property(property: 'slug', type: 'string', example: 'products'),
        new OA\Property(property: 'is_system', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class SectionSchema {}

#[OA\Schema(
    schema: 'SectionSuccessResponse',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Sección creada correctamente.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Section'),
    ],
    type: 'object'
)]
class SectionSuccessResponseSchema {}

#[OA\Schema(
    schema: 'Product',
    required: ['id', 'code', 'name', 'brand', 'price', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '66f9f09a2aa3f63ef50b1b90'),
        new OA\Property(property: 'code', type: 'string', example: 'PRD-000001'),
        new OA\Property(property: 'name', type: 'string', example: 'Teclado'),
        new OA\Property(property: 'brand', type: 'string', example: 'Logitech'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 899.99),
        new OA\Property(property: 'photo', type: 'string', nullable: true, example: 'products/PRD-000001/photo.png'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class ProductSchema {}

#[OA\Schema(
    schema: 'ProductSuccessResponse', required: ['success', 'message', 'data'], properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Producto obtenido correctamente.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
    ], type: 'object'
)]
class ProductSuccessResponseSchema {}

#[OA\Schema(
    schema: 'UserSuccessResponse',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Usuario obtenido correctamente.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]
class UserSuccessResponseSchema {}
