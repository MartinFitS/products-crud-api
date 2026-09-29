<?php

namespace App\Http\Controllers;

use App\Exports\ProfilesExport;
use App\Http\Requests\Profile\ListProfilesRequest;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\ProfileService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function index(ListProfilesRequest $request): JsonResponse
    {
        $result = $this->profileService->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Perfiles obtenidos correctamente.',
            'data' => ProfileResource::collection($result['profiles'])->resolve($request),
            'meta' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
            ],
        ]);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $profile = $this->profileService->create($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Perfil creado correctamente.',
            'data' => ProfileResource::make($profile)->resolve($request),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $profile = $this->profileService->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Perfil obtenido correctamente.',
            'data' => ProfileResource::make($profile)->resolve($request),
        ]);
    }

    public function update(UpdateProfileRequest $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $profile = $this->profileService->update($id, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data' => ProfileResource::make($profile)->resolve($request),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->profileService->delete($id, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado correctamente.',
            'data' => null,
        ]);
    }

    public function exportPdf(): Response
    {
        $profiles = $this->profileService->exportProfiles();
        $filename = 'profiles-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('exports.profiles-pdf', compact('profiles'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'profiles-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ProfilesExport($this->profileService->exportProfiles()),
            $filename,
        );
    }
}
