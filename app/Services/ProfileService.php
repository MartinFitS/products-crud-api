<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Operation\FindOneAndUpdate;

class ProfileService
{
    /**
     * @param  array{page?: int, limit?: int, search?: string|null}  $filters
     * @return array{profiles: Collection<int, Profile>, page: int, limit: int, total: int}
     */
    public function paginate(array $filters): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $query = Profile::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $regex = new Regex(preg_quote($search), 'i');
            $query->where(function ($query) use ($regex): void {
                $query
                    ->where('code', 'regex', $regex)
                    ->orWhere('name', 'regex', $regex);
            });
        }

        $total = $query->count();
        $profiles = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return compact('profiles', 'page', 'limit', 'total');
    }

    /**
     * @param  array{name: string, sections: array<int, string>}  $data
     */
    public function create(array $data, User $actor): Profile
    {
        $data['code'] = $this->nextCode();
        $profile = Profile::create($data);
        $this->audit($actor, 'created', $profile, null, $this->snapshot($profile));

        return $profile;
    }

    public function find(string $id): Profile
    {
        $profile = Profile::where('_id', $this->objectId($id))->first();

        if (! $profile) {
            throw (new ModelNotFoundException)->setModel(Profile::class, [$id]);
        }

        return $profile;
    }

    /**
     * @param  array{name?: string, sections?: array<int, string>}  $data
     */
    public function update(string $id, array $data, User $actor): Profile
    {
        $profile = $this->find($id);
        $oldValues = $this->snapshot($profile);
        $profile->update($data);
        $profile->refresh();
        $this->audit($actor, 'updated', $profile, $oldValues, $this->snapshot($profile));

        return $profile;
    }

    public function delete(string $id, User $actor): void
    {
        $profile = $this->find($id);

        if (User::whereIn('profile_ids', [$profile->_id])->exists()) {
            throw ValidationException::withMessages([
                'profile' => ['No se puede eliminar un perfil asignado a uno o más usuarios.'],
            ]);
        }

        $oldValues = $this->snapshot($profile);
        $profileId = $profile->_id;
        $profile->delete();

        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => 'deleted',
            'auditable_type' => 'profile',
            'auditable_id' => $profileId,
            'old_values' => $oldValues,
            'new_values' => null,
        ]);
    }

    /**
     * @return Collection<int, Profile>
     */
    public function exportProfiles(): Collection
    {
        return Profile::query()->orderBy('created_at')->get();
    }

    private function nextCode(): string
    {
        $latestCode = Profile::where('code', 'regex', new Regex('^PRF-\d+$'))
            ->orderBy('code', 'desc')
            ->value('code');
        $latestSequence = $latestCode ? (int) substr($latestCode, 4) : 0;

        $counter = DB::connection('mongodb')
            ->getCollection('counters')
            ->findOneAndUpdate(
                ['_id' => 'profiles'],
                [[
                    '$set' => [
                        'sequence' => [
                            '$add' => [
                                ['$max' => [['$ifNull' => ['$sequence', 0]], $latestSequence]],
                                1,
                            ],
                        ],
                    ],
                ]],
                [
                    'upsert' => true,
                    'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                ]
            );

        return sprintf('PRF-%06d', (int) $counter['sequence']);
    }

    private function objectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(Profile::class, [$id]);
        }
    }

    /** @return array{code: string, name: string, sections: array<int, string>} */
    private function snapshot(Profile $profile): array
    {
        return [
            'code' => $profile->code,
            'name' => $profile->name,
            'sections' => array_values($profile->sections ?? []),
        ];
    }

    private function audit(
        User $actor,
        string $action,
        Profile $profile,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => $action,
            'auditable_type' => 'profile',
            'auditable_id' => $profile->_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
