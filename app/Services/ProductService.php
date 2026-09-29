<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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

class ProductService
{
    /**
     * @param  array{page?: int, limit?: int, search?: string|null}  $filters
     * @return array{products: Collection<int, Product>, page: int, limit: int, total: int}
     */
    public function paginate(array $filters): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $query = Product::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $regex = new Regex(preg_quote($search), 'i');
            $query->where(function ($query) use ($regex): void {
                $query
                    ->where('code', 'regex', $regex)
                    ->orWhere('name', 'regex', $regex)
                    ->orWhere('brand', 'regex', $regex);
            });
        }

        $total = $query->count();
        $products = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return compact('products', 'page', 'limit', 'total');
    }

    /** @param array{name: string, brand: string, price: float|int|string, photo?: UploadedFile} $data */
    public function create(array $data, User $actor): Product
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        $data['code'] = $this->nextCode();
        $data['price'] = (float) $data['price'];
        $data['photo'] = $photo instanceof UploadedFile
            ? $this->storePhoto($photo, $data['code'])
            : null;

        try {
            $product = Product::create($data);
        } catch (Throwable $exception) {
            if ($data['photo']) {
                Storage::disk('public')->delete($data['photo']);
            }

            throw $exception;
        }

        $this->audit($actor, 'created', $product, null, $this->snapshot($product));

        return $product;
    }

    public function find(string $id): Product
    {
        $product = Product::where('_id', $this->objectId($id))->first();

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$id]);
        }

        return $product;
    }

    /** @param array{name?: string, brand?: string, price?: float|int|string, photo?: UploadedFile} $data */
    public function update(string $id, array $data, User $actor): Product
    {
        $product = $this->find($id);
        $oldValues = $this->snapshot($product);
        $oldPhoto = $product->photo;
        $newPhoto = null;

        if (($data['photo'] ?? null) instanceof UploadedFile) {
            $newPhoto = $this->storePhoto($data['photo'], $product->code);
            $data['photo'] = $newPhoto;
        }

        if (array_key_exists('price', $data)) {
            $data['price'] = (float) $data['price'];
        }

        try {
            $product->update($data);
        } catch (Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }

            throw $exception;
        }

        if ($newPhoto && $oldPhoto && $oldPhoto !== $newPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $product->refresh();
        $this->audit($actor, 'updated', $product, $oldValues, $this->snapshot($product));

        return $product;
    }

    public function delete(string $id, User $actor): void
    {
        $product = $this->find($id);
        $oldValues = $this->snapshot($product);
        $productId = $product->_id;
        $product->delete();

        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }

        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => 'deleted',
            'auditable_type' => 'product',
            'auditable_id' => $productId,
            'old_values' => $oldValues,
            'new_values' => null,
        ]);
    }

    /** @return Collection<int, Product> */
    public function exportProducts(): Collection
    {
        return Product::query()->orderBy('created_at')->get();
    }

    /** @return array{code: string, name: string, brand: string, price: float, photo: string|null} */
    private function snapshot(Product $product): array
    {
        return [
            'code' => $product->code,
            'name' => $product->name,
            'brand' => $product->brand,
            'price' => (float) $product->price,
            'photo' => $product->photo,
        ];
    }

    private function storePhoto(UploadedFile $photo, string $code): string
    {
        $filename = 'photo-'.Str::uuid().'.'.$photo->extension();
        $path = $photo->storeAs("products/{$code}", $filename, 'public');

        if (! $path) {
            throw new RuntimeException('No fue posible almacenar la foto del producto.');
        }

        return $path;
    }

    private function audit(
        User $actor,
        string $action,
        Product $product,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        AuditLog::create([
            'user_id' => $actor->_id,
            'action' => $action,
            'auditable_type' => 'product',
            'auditable_id' => $product->_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    private function nextCode(): string
    {
        $latestCode = Product::where('code', 'regex', new Regex('^PRD-\d+$'))
            ->orderBy('code', 'desc')
            ->value('code');
        $latestSequence = $latestCode ? (int) substr($latestCode, 4) : 0;

        $counter = DB::connection('mongodb')
            ->getCollection('counters')
            ->findOneAndUpdate(
                ['_id' => 'products'],
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

        return sprintf('PRD-%06d', (int) $counter['sequence']);
    }

    private function objectId(string $id): ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidArgumentException) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$id]);
        }
    }
}
