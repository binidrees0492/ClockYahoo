<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageAssetServingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_storage_serving_route_returns_file_with_correct_mime_and_cache_headers(): void
    {
        // Store a fake avatar in storage/app/public/avatars/test-avatar.png
        $file = UploadedFile::fake()->image('test-avatar.png', 50, 50);
        $path = $file->storeAs('avatars', 'test-avatar.png', 'public');

        $response = $this->get('/storage/' . $path);

        $response->assertOk();
        $this->assertStringContainsString('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    public function test_user_avatar_url_attribute_is_clean_and_routable(): void
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/my-profile.jpg',
        ]);

        $this->assertStringContainsString('/storage/avatars/my-profile.jpg', $user->avatar_url);
        $this->assertStringNotContainsString('//storage', $user->avatar_url);
        $this->assertStringNotContainsString('/storage/storage', $user->avatar_url);

        // Even if avatar was saved with leading storage/ or /public/
        $user->avatar = 'storage/avatars/my-profile.jpg';
        $user->save();
        $this->assertStringContainsString('/storage/avatars/my-profile.jpg', $user->avatar_url);
        $this->assertStringNotContainsString('/storage/storage', $user->avatar_url);

        $user->avatar = 'public/avatars/my-profile.jpg';
        $user->save();
        $this->assertStringContainsString('/storage/avatars/my-profile.jpg', $user->avatar_url);
        $this->assertStringNotContainsString('/storage/public', $user->avatar_url);
    }

    public function test_company_setting_logo_and_favicon_urls(): void
    {
        $this->assertNull(CompanySetting::getLogoUrl());

        CompanySetting::set('company_logo', 'company/main-logo.png');
        $this->assertStringContainsString('/storage/company/main-logo.png', CompanySetting::getLogoUrl());
        $this->assertStringNotContainsString('/storage/storage', CompanySetting::getLogoUrl());

        // Favicon defaults to logo if no explicit favicon
        $this->assertStringContainsString('/storage/company/main-logo.png', CompanySetting::getFaviconUrl());

        // External or data URLs are preserved as-is
        CompanySetting::set('company_logo', 'https://cdn.example.com/logo.png');
        $this->assertEquals('https://cdn.example.com/logo.png', CompanySetting::getLogoUrl());
    }

    public function test_storage_route_returns_404_for_missing_file_and_blocks_path_traversal(): void
    {
        $response = $this->get('/storage/avatars/non-existent-file-12345.png');
        $response->assertNotFound();

        // Path traversal attempts must return 404
        $traversalResponse = $this->get('/storage/../../.env');
        $traversalResponse->assertNotFound();
    }
}
