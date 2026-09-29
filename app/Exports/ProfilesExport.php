<?php

namespace App\Exports;

use App\Models\Profile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProfilesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $profiles) {}

    public function collection(): Collection
    {
        return $this->profiles;
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'Secciones', 'Fecha de creación'];
    }

    /**
     * @param  Profile  $row
     */
    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            implode(', ', $row->sections ?? []),
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
