<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Http\Requests\AuditLog\ListAuditLogsRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        $result = $this->auditLogService->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bitácora obtenida correctamente.',
            'data' => AuditLogResource::collection($result['entries'])->resolve($request),
            'meta' => ['page' => $result['page'], 'limit' => $result['limit'], 'total' => $result['total']],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Registro de bitácora obtenido correctamente.',
            'data' => AuditLogResource::make($this->auditLogService->find($id))->resolve($request),
        ]);
    }

    public function exportPdf(ListAuditLogsRequest $request): Response
    {
        $entries = $this->auditLogService->exportEntries($request->validated());

        return Pdf::loadView('exports.audit-logs-pdf', compact('entries'))
            ->setPaper('a4', 'landscape')
            ->download('audit-logs-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(ListAuditLogsRequest $request): BinaryFileResponse
    {
        return Excel::download(
            new AuditLogsExport($this->auditLogService->exportEntries($request->validated())),
            'audit-logs-'.now()->format('Y-m-d').'.xlsx',
        );
    }
}
