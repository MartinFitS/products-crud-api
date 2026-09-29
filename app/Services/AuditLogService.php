<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Exception\InvalidArgumentException;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{entries: Collection<int, AuditLog>, page: int, limit: int, total: int}
     */
    public function paginate(array $filters): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $query = $this->filteredQuery($filters);
        $total = $query->count();
        $entries = $query
            ->with('user:_id,code,name,email')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return compact('entries', 'page', 'limit', 'total');
    }

    public function find(string $id): AuditLog
    {
        $entry = AuditLog::with('user:_id,code,name,email')
            ->where('_id', $this->objectId($id))
            ->first();

        if (! $entry) {
            throw (new ModelNotFoundException)->setModel(AuditLog::class, [$id]);
        }

        return $entry;
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, AuditLog>
     */
    public function exportEntries(array $filters): Collection
    {
        return $this->filteredQuery($filters)
            ->with('user:_id,code,name,email')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters)
    {
        $query = AuditLog::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $regex = new Regex(preg_quote($search), 'i');
            $actorIds = User::query()
                ->where(function ($users) use ($regex): void {
                    $users->where('code', 'regex', $regex)
                        ->orWhere('name', 'regex', $regex)
                        ->orWhere('email', 'regex', $regex);
                })
                ->pluck('_id')
                ->all();

            $query->where(function ($entries) use ($regex, $actorIds): void {
                $entries->where('action', 'regex', $regex)
                    ->orWhere('auditable_type', 'regex', $regex)
                    ->orWhere('old_values.code', 'regex', $regex)
                    ->orWhere('old_values.name', 'regex', $regex)
                    ->orWhere('new_values.code', 'regex', $regex)
                    ->orWhere('new_values.name', 'regex', $regex);
                if ($actorIds !== []) {
                    $entries->orWhereIn('user_id', $actorIds);
                }
            });
        }

        if ($action = $filters['action'] ?? null) {
            $query->where('action', $action);
        }
        if ($type = $filters['auditable_type'] ?? null) {
            $query->where('auditable_type', $type);
        }
        if ($dateFrom = $filters['date_from'] ?? null) {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat('Y-m-d', $dateFrom)->startOfDay());
        }
        if ($dateTo = $filters['date_to'] ?? null) {
            $query->where('created_at', '<=', CarbonImmutable::createFromFormat('Y-m-d', $dateTo)->endOfDay());
        }

        return $query;
    }

    private function objectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(AuditLog::class, [$id]);
        }
    }
}
