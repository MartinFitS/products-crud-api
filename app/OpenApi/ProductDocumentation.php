<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Administración y exportación de productos')]
class ProductDocumentation
{
    #[OA\Get(
        path: '/api/products', operationId: 'productsIndex', summary: 'Lista productos con búsqueda y paginación',
        security: [['bearerAuth' => []]], tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), example: 'Laptop'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado.', content: new OA\JsonContent(example: [
                'success' => true, 'message' => 'Productos obtenidos correctamente.',
                'data' => [['id' => '66f9f09a2aa3f63ef50b1b90', 'code' => 'PRD-000001', 'name' => 'Teclado', 'brand' => 'Logitech', 'price' => 899.99, 'created_at' => '2026-09-29T10:00:00+00:00']],
                'meta' => ['page' => 1, 'limit' => 10, 'total' => 1],
            ])),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 422, description: 'Filtros inválidos.'),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/products', operationId: 'productsStore', summary: 'Crea un producto con código automático',
        security: [['bearerAuth' => []]], tags: ['Products'],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data', schema: new OA\Schema(
                required: ['name', 'brand', 'price'], properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Teclado'),
                    new OA\Property(property: 'brand', type: 'string', maxLength: 150, example: 'Logitech'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, maximum: 999.99, example: 899.99),
                    new OA\Property(property: 'photo', type: 'string', format: 'binary'),
                ], type: 'object'
            )
        )),
        responses: [
            new OA\Response(response: 201, description: 'Producto creado.', content: new OA\JsonContent(ref: '#/components/schemas/ProductSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 422, description: 'Datos inválidos.'),
        ]
    )]
    public function store(): void {}

    #[OA\Get(
        path: '/api/products/{id}', operationId: 'productsShow', summary: 'Obtiene el detalle de un producto',
        security: [['bearerAuth' => []]], tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Producto obtenido.', content: new OA\JsonContent(ref: '#/components/schemas/ProductSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 404, description: 'Producto no encontrado.'),
        ]
    )]
    public function show(): void {}

    #[OA\Put(
        path: '/api/products/{id}', operationId: 'productsUpdate', summary: 'Actualiza parcialmente un producto',
        security: [['bearerAuth' => []]], tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Teclado mecánico'),
            new OA\Property(property: 'brand', type: 'string', maxLength: 150, example: 'Logitech'),
            new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, maximum: 999.99, example: 999.99),
        ], type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Producto actualizado.', content: new OA\JsonContent(ref: '#/components/schemas/ProductSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 404, description: 'Producto no encontrado.'),
            new OA\Response(response: 422, description: 'Datos inválidos.'),
        ]
    )]
    public function update(): void {}

    #[OA\Post(
        path: '/api/products/{id}', operationId: 'productsUpdateMultipart', summary: 'Actualiza un producto y permite reemplazar su foto',
        security: [['bearerAuth' => []]], tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\MediaType(
            mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Teclado mecánico'),
                new OA\Property(property: 'brand', type: 'string', maxLength: 150, example: 'Logitech'),
                new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, maximum: 999.99, example: 999.99),
                new OA\Property(property: 'photo', type: 'string', format: 'binary'),
            ], type: 'object')
        )),
        responses: [
            new OA\Response(response: 200, description: 'Producto actualizado.', content: new OA\JsonContent(ref: '#/components/schemas/ProductSuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 404, description: 'Producto no encontrado.'),
            new OA\Response(response: 422, description: 'Datos inválidos.'),
        ]
    )]
    public function updateMultipart(): void {}

    #[OA\Delete(
        path: '/api/products/{id}', operationId: 'productsDestroy', summary: 'Elimina un producto',
        security: [['bearerAuth' => []]], tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Producto eliminado.', content: new OA\JsonContent(example: ['success' => true, 'message' => 'Producto eliminado correctamente.', 'data' => null])),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
            new OA\Response(response: 404, description: 'Producto no encontrado.'),
        ]
    )]
    public function destroy(): void {}

    #[OA\Get(
        path: '/api/products/export/pdf', operationId: 'productsExportPdf', summary: 'Descarga productos en PDF',
        security: [['bearerAuth' => []]], tags: ['Products'], responses: [
            new OA\Response(response: 200, description: 'Archivo PDF.', content: new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
        ]
    )]
    public function exportPdf(): void {}

    #[OA\Get(
        path: '/api/products/export/excel', operationId: 'productsExportExcel', summary: 'Descarga productos en Excel',
        security: [['bearerAuth' => []]], tags: ['Products'], responses: [
            new OA\Response(response: 200, description: 'Archivo XLSX.', content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'Sin acceso a Products.'),
        ]
    )]
    public function exportExcel(): void {}
}
