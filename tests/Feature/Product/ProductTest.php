<?php

namespace Tests\Feature\Product;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MongoDB\BSON\Regex;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private User $actor;

    private Profile $profile;

    private string $marker;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->marker = (string) Str::uuid();
        $this->profile = Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => 'Products Access '.$this->marker,
            'sections' => ['products'],
        ]);
        $this->actor = User::create([
            'code' => 'TEST-ACTOR-'.Str::upper(Str::random(8)),
            'name' => 'Products Test Actor',
            'email' => "products-{$this->marker}@example.test",
            'password' => 'ActorPassword123!',
            'profile_ids' => [$this->profile->_id],
            'is_active' => true,
        ]);
        $this->token = $this->actor->createToken('products-test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        AuditLog::where('user_id', $this->actor->_id)->delete();
        Product::where('name', 'regex', new Regex(preg_quote($this->marker), 'i'))->delete();
        $this->actor->tokens()->delete();
        $this->actor->delete();
        $this->profile->delete();

        parent::tearDown();
    }

    public function test_product_routes_require_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_product_list_supports_search_and_pagination(): void
    {
        $product = $this->createProduct('Teclado Buscable', 'Logitech', 899.99);

        $this->withToken($this->token)
            ->getJson('/api/products?page=1&limit=10&search=Buscable')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $product->getKey())
            ->assertJsonPath('data.0.price', 899.99)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_authorized_user_can_create_a_product_and_audit_log(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/products', [
            'name' => 'Mouse '.$this->marker,
            'brand' => 'Logitech',
            'price' => 599.50,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.brand', 'Logitech')
            ->assertJsonPath('data.price', 599.5);
        $this->assertMatchesRegularExpression('/^PRD-\d{6,}$/', $response->json('data.code'));

        $product = Product::findOrFail($response->json('data.id'));
        $audit = AuditLog::where('auditable_id', $product->_id)->firstOrFail();
        $this->assertSame('created', $audit->action);
        $this->assertNull($audit->old_values);
        $this->assertSame($product->name, $audit->new_values['name']);
    }

    public function test_product_requires_fields_and_a_valid_price(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'brand', 'price']);

        foreach ([1000, -1, 10.999] as $invalidPrice) {
            $this->withToken($this->token)
                ->postJson('/api/products', [
                    'name' => 'Inválido '.$invalidPrice.' '.$this->marker,
                    'brand' => 'Marca',
                    'price' => $invalidPrice,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('price');
        }
    }

    public function test_authorized_user_can_view_and_update_a_product_with_audit(): void
    {
        $product = $this->createProduct('Producto Editable', 'Marca anterior', 100);

        $this->withToken($this->token)
            ->getJson('/api/products/'.$product->getKey())
            ->assertOk()
            ->assertJsonPath('data.code', $product->code);

        $this->withToken($this->token)
            ->putJson('/api/products/'.$product->getKey(), [
                'brand' => 'Marca nueva',
                'price' => 250.75,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.brand', 'Marca nueva')
            ->assertJsonPath('data.price', 250.75);

        $audit = AuditLog::where('auditable_id', $product->_id)->latest()->firstOrFail();
        $this->assertSame('updated', $audit->action);
        $this->assertSame('Marca anterior', $audit->old_values['brand']);
        $this->assertSame('Marca nueva', $audit->new_values['brand']);
    }

    public function test_authorized_user_can_delete_a_product_with_audit(): void
    {
        $product = $this->createProduct('Producto Eliminable', 'Marca', 99.99);
        $productId = $product->_id;

        $this->withToken($this->token)
            ->deleteJson('/api/products/'.$product->getKey())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Product::find($productId));
        $audit = AuditLog::where('auditable_id', $productId)->firstOrFail();
        $this->assertSame('deleted', $audit->action);
        $this->assertSame($product->name, $audit->old_values['name']);
        $this->assertNull($audit->new_values);
    }

    public function test_product_exports_return_downloads(): void
    {
        $this->withToken($this->token)
            ->get('/api/products/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('products-'.now()->format('Y-m-d').'.pdf');

        $this->withToken($this->token)
            ->get('/api/products/export/excel')
            ->assertOk()
            ->assertDownload('products-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_product_photo_can_be_created_replaced_and_deleted(): void
    {
        $create = $this->withToken($this->token)->post('/api/products', [
            'name' => 'Producto con foto '.$this->marker,
            'brand' => 'Marca',
            'price' => 199.99,
            'photo' => UploadedFile::fake()->image('original.png', 600, 600),
        ], ['Accept' => 'application/json']);

        $create->assertCreated();
        $productId = $create->json('data.id');
        $oldPhoto = $create->json('data.photo');
        Storage::disk('public')->assertExists($oldPhoto);

        $update = $this->withToken($this->token)->post('/api/products/'.$productId, [
            'photo' => UploadedFile::fake()->image('replacement.webp', 600, 600),
        ], ['Accept' => 'application/json']);

        $update->assertOk();
        $newPhoto = $update->json('data.photo');
        $this->assertNotSame($oldPhoto, $newPhoto);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($newPhoto);

        $this->withToken($this->token)
            ->deleteJson('/api/products/'.$productId)
            ->assertOk();
        Storage::disk('public')->assertMissing($newPhoto);
    }

    public function test_user_without_products_section_cannot_access_products(): void
    {
        $this->profile->update(['sections' => ['users']]);

        $this->withToken($this->token)
            ->getJson('/api/products')
            ->assertForbidden();
    }

    private function createProduct(string $name, string $brand, float $price): Product
    {
        return Product::create([
            'code' => 'TEST-PRD-'.Str::upper(Str::random(8)),
            'name' => $name.' '.$this->marker,
            'brand' => $brand,
            'price' => $price,
        ]);
    }
}
