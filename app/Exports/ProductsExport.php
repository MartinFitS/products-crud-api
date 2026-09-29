<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $products) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'Marca', 'Precio', 'Fecha de creación'];
    }

    /** @param Product $row */
    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->brand,
            number_format((float) $row->price, 2, '.', ''),
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
