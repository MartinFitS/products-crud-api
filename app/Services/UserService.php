<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Operation\FindOneAndUpdate;
use RuntimeException;
use Throwable;

class UserService
{
    /**
     * @param  array{page?: int, limit?: int, search?: string|null, is_active?: bool}  $filters
     * @return array{users: EloquentCollection<int, User>, page: int, limit: int, total: int}
     */
    public function paginate(array $filters): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $query = User::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $regex = new Regex(preg_quote($search), 'i');
            $query->where(function ($query) use ($regex): void {
                $query
                    ->where('code', 'regex', $regex)
                    ->orWhere('name', 'regex', $regex)
                    ->orWhere('email', 'regex', $regex);
            });
        }

        if (array_key_exists('is_active', $filters)) {
            if ($filters['is_active']) {
                $query->where(function ($query): void {
                    $query->where('is_active', true)->orWhereNull('is_active');
                });
            } else {
                $query->where('is_active', false);
            }
        }

        $total = $query->count();
        $users = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return compact('users', 'page', 'limit', 'total');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        /** @var UploadedFile $photo */
        $photo = $data['photo'];
        unset($data['photo']);

        $data['code'] = $this->nextCode();
        $data['profile_ids'] = $this->profileObjectIds($data['profile_ids'] ?? []);
        $data['is_active'] = true;
        $data['photo'] = $this->storePhoto($photo, $data['code']);

        try {
            $user = User::create($data);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($data['photo']);

            throw $exception;
        }

        $this->audit($actor, 'created', $user, null, $this->snapshot($user));

        return $user;
    }

    public function find(string $id): User
    {
        $user = User::where('_id', $this->objectId($id))->first();

        if (! $user) {
            throw (new ModelNotFoundException)->setModel(User::class, [$id]);
        }

        return $user;
    }

    public function findWithProfiles(string $id): User
    {
        return $this->attachProfiles($this->find($id));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data, User $actor): User
    {
        $user = $this->find($id);
        $oldValues = $this->snapshot($user);
        $oldPhoto = $user->photo;
        $newPhoto = null;

        if (isset($data['photo'])) {
            /** @var UploadedFile $photo */
            $photo = $data['photo'];
            $newPhoto = $this->storePhoto($photo, $user->code);
            $data['photo'] = $newPhoto;
        }

        if (array_key_exists('profile_ids', $data)) {
            $data['profile_ids'] = $this->profileObjectIds($data['profile_ids']);
        }

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        try {
            $user->update($data);
        } catch (Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }

            throw $exception;
        }

        if ($newPhoto && $oldPhoto && $oldPhoto !== $newPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $user->refresh();
        $this->audit($actor, 'updated', $user, $oldValues, $this->snapshot($user));

        return $this->attachProfiles($user);
    }

    public function updateStatus(string $id, bool $isActive, User $actor): User
    {
        $user = $this->find($id);
        $oldValues = $this->snapshot($user);
        $user->update(['is_active' => $isActive]);

        if (! $isActive) {
            $user->tokens()->delete();
        }

        $user->refresh();
        $this->audit($actor, 'status_updated', $user, $oldValues, $this->snapshot($user));

        return $user;
    }

    public function delete(string $id, User $actor): void
    {
        $user = $this->find($id);
        $oldValues = $this->snapshot($user);
        $userId = $user->_id;
        $user->tokens()->delete();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => 'deleted',
            'auditable_type' => 'user',
            'auditable_id' => $userId,
            'old_values' => $oldValues,
            'new_values' => null,
        ]);
    }

    /**
     * @return EloquentCollection<int, User>
     */
    public function exportUsers(): EloquentCollection
    {
        return User::query()->orderBy('created_at')->get();
    }

    private function nextCode(): string
    {
        $latestCode = User::where('code', 'regex', new Regex('^USR-\d+$'))
            ->orderBy('code', 'desc')
            ->value('code');
        $latestSequence = $latestCode ? (int) substr($latestCode, 4) : 0;

        $counter = DB::connection('mongodb')
            ->getCollection('counters')
            ->findOneAndUpdate(
                ['_id' => 'users'],
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

        return sprintf('USR-%06d', (int) $counter['sequence']);
    }

    private function storePhoto(UploadedFile $photo, string $code): string
    {
        $filename = 'photo-'.Str::uuid().'.'.$photo->extension();
        $path = $photo->storeAs("users/{$code}", $filename, 'public');

        if (! $path) {
            throw new RuntimeException('No fue posible almacenar la foto del usuario.');
        }

        return $path;
    }

    /**
     * @param  array<int, string>  $profileIds
     * @return array<int, ObjectId>
     */
    private function profileObjectIds(array $profileIds): array
    {
        return array_map(fn (string $id): ObjectId => new ObjectId($id), $profileIds);
    }

    private function objectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(User::class, [$id]);
        }
    }

    private function attachProfiles(User $user): User
    {
        $profileIds = collect($user->profile_ids ?? []);
        $profilesById = Profile::whereIn('_id', $profileIds->all())
            ->get()
            ->keyBy(fn (Profile $profile): string => (string) $profile->getKey());

        $profiles = $profileIds
            ->map(fn (mixed $profileId) => $profilesById->get((string) $profileId))
            ->filter()
            ->values();

        $user->setRelation('profiles', $profiles);

        return $user;
    }

    /**
     * @return array{code: string, name: string, email: string, phone: string|null, photo: string|null, profile_ids: array<int, string>, is_active: bool}
     */
    private function snapshot(User $user): array
    {
        return [
            'code' => $user->code,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo' => $user->photo,
            'profile_ids' => collect($user->profile_ids ?? [])
                ->map(fn (mixed $profileId): string => (string) $profileId)
                ->values()
                ->all(),
            'is_active' => (bool) ($user->is_active ?? true),
        ];
    }

    private function audit(
        User $actor,
        string $action,
        User $target,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => $action,
            'auditable_type' => 'user',
            'auditable_id' => $target->_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
