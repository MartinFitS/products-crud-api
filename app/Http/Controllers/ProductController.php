<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Http\Requests\Product\ListProductsRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\User;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(ListProductsRequest $request): JsonResponse
    {
        $result = $this->productService->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Productos obtenidos correctamente.',
            'data' => ProductResource::collection($result['products'])->resolve($request),
            'meta' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $product = $this->productService->create($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => ProductResource::make($product)->resolve($request),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Producto obtenido correctamente.',
            'data' => ProductResource::make($this->productService->find($id))->resolve($request),
        ]);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $product = $this->productService->update($id, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => ProductResource::make($product)->resolve($request),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->productService->delete($id, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
            'data' => null,
        ]);
    }

    public function exportPdf(): Response
    {
        $products = $this->productService->exportProducts();

        return Pdf::loadView('exports.products-pdf', compact('products'))
            ->setPaper('a4', 'landscape')
            ->download('products-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(
            new ProductsExport($this->productService->exportProducts()),
            'products-'.now()->format('Y-m-d').'.xlsx',
        );
    }
}
