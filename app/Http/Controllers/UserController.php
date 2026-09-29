<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        $result = $this->userService->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Usuarios obtenidos correctamente.',
            'data' => UserResource::collection($result['users'])->resolve($request),
            'meta' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $user = $this->userService->create($request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => UserResource::make($user)->resolve($request),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $this->userService->findWithProfiles($id);

        return response()->json([
            'success' => true,
            'message' => 'Usuario obtenido correctamente.',
            'data' => UserResource::make($user)->resolve($request),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $user = $this->userService->update($id, $request->validated(), $actor);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => UserResource::make($user)->resolve($request),
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $user = $this->userService->updateStatus(
            $id,
            $request->boolean('is_active'),
            $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado del usuario actualizado correctamente.',
            'data' => UserResource::make($user)->resolve($request),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->userService->delete($id, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
            'data' => null,
        ]);
    }

    public function exportPdf(): Response
    {
        $users = $this->userService->exportUsers();
        $filename = 'users-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('exports.users-pdf', compact('users'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'users-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new UsersExport($this->userService->exportUsers()),
            $filename,
        );
    }
}
