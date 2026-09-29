<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $entries) {}

    public function collection(): Collection
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return ['Fecha', 'Usuario', 'Correo', 'Acción', 'Entidad', 'ID del registro'];
    }

    /** @param AuditLog $row */
    public function map($row): array
    {
        return [
            $row->created_at?->format('d/m/Y H:i:s'),
            $row->user?->name ?? 'Usuario eliminado',
            $row->user?->email,
            $row->action,
            $row->auditable_type,
            (string) $row->auditable_id,
        ];
    }
}
