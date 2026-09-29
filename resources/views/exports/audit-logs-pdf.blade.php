<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Bitácora de actividad</title>
    <style>
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin-bottom: 4px; font-size: 20px; }
        .date { margin-bottom: 18px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 7px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Bitácora de actividad</h1>
    <div class="date">Generada el {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Correo</th><th>Acción</th><th>Entidad</th><th>ID del registro</th></tr></thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr><td>{{ $entry->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $entry->user?->name ?? 'Usuario eliminado' }}</td><td>{{ $entry->user?->email }}</td><td>{{ $entry->action }}</td><td>{{ $entry->auditable_type }}</td><td>{{ (string) $entry->auditable_id }}</td></tr>
            @empty
                <tr><td colspan="6">No hay actividad registrada.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
