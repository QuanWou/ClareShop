<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Content\Support\SiteContentRegistry;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_only_administrators_can_manage_website_content(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get(route('admin.content.edit'))
            ->assertRedirect(route('login'));

        $this->actingAs($customer)
            ->get(route('admin.content.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_edit_content_and_see_it_on_the_storefront(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $content = $this->defaultTextContent();
        $content['global_announcement'] = 'Một thông báo mới từ Clare.';
        $content['catalog_hero_title'] = 'Ánh sáng được chọn riêng';
        $content['login_title'] = 'Rất vui được gặp lại bạn.';

        $this->actingAs($admin)
            ->get(route('admin.content.edit'))
            ->assertOk()
            ->assertSee('Nội dung website.')
            ->assertSee('Trang chủ — Mở đầu');

        $this->actingAs($admin)
            ->patch(route('admin.content.update'), ['content' => $content])
            ->assertRedirect(route('admin.content.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_contents', [
            'key' => 'global_announcement',
            'value' => 'Một thông báo mới từ Clare.',
            'updated_by' => $admin->getKey(),
        ]);

        $this->get(route('catalog.home'))
            ->assertOk()
            ->assertSee('Một thông báo mới từ Clare.');

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertSee('Ánh sáng được chọn riêng');

        $this->post(route('logout'))
            ->assertRedirect(route('catalog.home'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Rất vui được gặp lại bạn.');
    }

    public function test_admin_can_replace_an_editorial_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.content.update'), [
                'content' => $this->defaultTextContent(),
                'images' => [
                    'home_banner_image' => UploadedFile::fake()->image('clare-banner.webp', 1200, 700),
                ],
            ])
            ->assertRedirect(route('admin.content.edit'))
            ->assertSessionHasNoErrors();

        $path = (string) $this->app->make('db')
            ->table('site_contents')
            ->where('key', 'home_banner_image')
            ->value('value');

        $this->assertStringStartsWith('storage/content/home_banner_image/', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('storage/')));

        $this->get(route('catalog.home'))
            ->assertOk()
            ->assertSee(asset($path), false);
    }

    /** @return array<string, string> */
    private function defaultTextContent(): array
    {
        return collect(app(SiteContentRegistry::class)->definitions())
            ->reject(fn (array $definition): bool => $definition['type'] === 'image')
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => (string) ($definition['default'] ?? ''),
            ])
            ->all();
    }
}
