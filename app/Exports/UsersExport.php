<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $users) {}

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return ['Código', 'Usuario', 'Nombre', 'Fecha de creación'];
    }

    /**
     * @param  User  $row
     */
    public function map($row): array
    {
        return [
            $row->code,
            $row->email,
            $row->name,
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
