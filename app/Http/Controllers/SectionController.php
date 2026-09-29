<?php

namespace App\Http\Controllers;

use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Resources\SectionResource;
use App\Services\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function __construct(private readonly SectionService $sectionService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Secciones obtenidas correctamente.',
            'data' => SectionResource::collection($this->sectionService->all())->resolve($request),
        ]);
    }

    public function store(StoreSectionRequest $request): JsonResponse
    {
        $section = $this->sectionService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sección creada correctamente.',
            'data' => SectionResource::make($section)->resolve($request),
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->sectionService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Sección eliminada correctamente.',
            'data' => null,
        ]);
    }
}
