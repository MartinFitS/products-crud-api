<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de perfiles</title>
    <style>
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { margin-bottom: 4px; font-size: 22px; }
        .date { margin-bottom: 20px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Listado de perfiles</h1>
    <div class="date">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Secciones</th>
                <th>Fecha de creación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($profiles as $profile)
                <tr>
                    <td>{{ $profile->code }}</td>
                    <td>{{ $profile->name }}</td>
                    <td>{{ implode(', ', $profile->sections ?? []) }}</td>
                    <td>{{ $profile->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No hay perfiles registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
