<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Section;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Operation\FindOneAndUpdate;

class SectionService
{
    /**
     * @return Collection<int, Section>
     */
    public function all(): Collection
    {
        return Section::query()->orderBy('code')->get();
    }

    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function create(array $data): Section
    {
        return Section::create([
            ...$data,
            'code' => $this->nextCode(),
            'is_system' => false,
        ]);
    }

    public function delete(string $id): void
    {
        $section = $this->find($id);

        if ($section->is_system) {
            throw ValidationException::withMessages([
                'section' => ['No se puede eliminar una sección del sistema.'],
            ]);
        }

        $isAssigned = Profile::query()
            ->get(['sections'])
            ->contains(
                fn (Profile $profile): bool => in_array($section->slug, $profile->sections ?? [], true)
            );

        if ($isAssigned) {
            throw ValidationException::withMessages([
                'section' => ['No se puede eliminar una sección asignada a uno o más perfiles.'],
            ]);
        }

        $section->delete();
    }

    private function find(string $id): Section
    {
        $section = Section::where('_id', $this->objectId($id))->first();

        if (! $section) {
            throw (new ModelNotFoundException)->setModel(Section::class, [$id]);
        }

        return $section;
    }

    private function nextCode(): string
    {
        $latestCode = Section::where('code', 'regex', new Regex('^SEC-\d+$'))
            ->orderBy('code', 'desc')
            ->value('code');
        $latestSequence = $latestCode ? (int) substr($latestCode, 4) : 0;

        $counter = DB::connection('mongodb')
            ->getCollection('counters')
            ->findOneAndUpdate(
                ['_id' => 'sections'],
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

        return sprintf('SEC-%06d', (int) $counter['sequence']);
    }

    private function objectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(Section::class, [$id]);
        }
    }
}
