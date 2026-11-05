<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PWA: manifest, ikonkalar, layout teglari, /offline sahifasi, service worker istisnolari, chiqishda kesh tozalash.
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    private function manifest(): array
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertIsArray($data, 'manifest.json yaroqli JSON emas');

        return $data;
    }

    private function assertPwaHead($response): void
    {
        $response->assertSee('<link rel="manifest" href="' . asset('manifest.json') . '">', false)
            ->assertSee('<meta name="theme-color" content="#2F4B33">', false)
            ->assertSee('<link rel="apple-touch-icon" href="' . asset('icons/apple-touch-icon.png') . '">', false)
            ->assertSee('<link rel="icon" type="image/png" sizes="32x32" href="' . asset('icons/favicon-32.png') . '">', false)
            ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false)
            ->assertSee('<meta name="apple-mobile-web-app-title" content="Chorvadorim">', false)
            ->assertSee('id="chv-pwa-config"', false)
            ->assertSee(asset('pwa.js') . '?v=', false);
    }

    public function test_manifest_is_valid_with_required_fields(): void
    {
        $m = $this->manifest();

        $this->assertSame('Chorvadorim', $m['name']);
        $this->assertSame('Chorvadorim', $m['short_name']);
        $this->assertNotEmpty($m['description']);
        $this->assertSame('/dashboard?source=pwa', $m['start_url']);
        $this->assertSame('/', $m['scope']);
        $this->assertSame('standalone', $m['display']);
        $this->assertSame('uz', $m['lang']);
        $this->assertSame('#2F4B33', $m['theme_color']);
        $this->assertSame('#FAF6EE', $m['background_color']);
        $this->assertArrayNotHasKey('orientation', $m);

        $icons = collect($m['icons']);
        $this->assertTrue($icons->contains(fn ($i) => $i['sizes'] === '192x192' && $i['purpose'] === 'any'));
        $this->assertTrue($icons->contains(fn ($i) => $i['sizes'] === '512x512' && $i['purpose'] === 'any'));
        $this->assertTrue($icons->contains(fn ($i) => $i['sizes'] === '512x512' && $i['purpose'] === 'maskable'));

        foreach ($icons as $icon) {
            $file = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($file);
            [$w, $h] = getimagesize($file);
            $this->assertSame($icon['sizes'], "{$w}x{$h}");
        }
    }

    public function test_icon_files_exist_with_correct_sizes(): void
    {
        $this->assertFileExists(public_path('icons/icon.svg'));

        foreach (['icon-192.png' => 192, 'icon-512.png' => 512, 'maskable-512.png' => 512, 'apple-touch-icon.png' => 180, 'favicon-32.png' => 32] as $file => $size) {
            $path = public_path('icons/' . $file);
            $this->assertFileExists($path);
            $info = getimagesize($path);
            $this->assertSame([$size, $size], [$info[0], $info[1]], $file);
            $this->assertSame('image/png', $info['mime']);
        }
    }

    public function test_pwa_tags_in_all_layouts(): void
    {
        // Bosh sahifa va guest layout
        foreach (['/', route('login'), route('register')] as $url) {
            $this->assertPwaHead($this->get($url)->assertOk());
        }

        // Asosiy layout (kirgan foydalanuvchi)
        $user = User::factory()->create();
        $this->assertPwaHead($this->actingAs($user)->get(route('dashboard'))->assertOk());
        $this->assertPwaHead($this->actingAs($user)->get(route('profile.edit'))->assertOk());
    }

    public function test_user_meta_tag(): void
    {
        $this->get('/')->assertSee('<meta name="chv-user" content="">', false);

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('<meta name="chv-user" content="' . $user->id . '">', false);
    }

    public function test_install_panel_disabled_on_admin_pages(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('"installPanel":false', false);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee('"installPanel":true', false);
    }

    public function test_landing_install_button_hidden_by_default(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-chv-install hidden', false)
            ->assertSee(__("Ilovani o'rnatish"));
    }

    public function test_offline_page_in_three_languages(): void
    {
        $cases = [
            'uz' => ['uz', "Internet yo'q"],
            'uz_cyrl' => ['uz-Cyrl', 'Интернет йўқ'],
            'ru' => ['ru', 'Нет интернета'],
        ];

        foreach ($cases as $locale => [$htmlLang, $title]) {
            $this->withSession(['locale' => $locale])->get(route('offline'))->assertOk()
                ->assertSee('<html lang="' . $htmlLang . '">', false)
                ->assertSee('<h1 id="offline-title">' . e($title) . '</h1>', false)
                ->assertSee(e(__('Ulanish tiklangach sahifa ochiladi.', [], $locale)), false)
                ->assertSee(e(__('Qayta urinish', [], $locale)), false);
        }
    }

    public function test_service_worker_exclusions(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path);
        $sw = file_get_contents($path);

        $this->assertMatchesRegularExpression("/const CACHE_VERSION = '[^']+';/", $sw);
        $this->assertStringContainsString("req.method !== 'GET'", $sw);
        $this->assertStringContainsString('url.origin !== self.location.origin', $sw);

        foreach (['/admin', '/login', '/logout', '/register', '/forgot-password', '/reset-password', '/cron', '/telegram', '/animals/import', '/xodimlar'] as $bypass) {
            $this->assertStringContainsString("'{$bypass}',", $sw, "sw.js da {$bypass} istisnosi yo'q");
        }

        // Oflayn sahifa oldindan keshlanadi, foydalanuvchi almashsa sahifa keshi tozalanadi
        $this->assertStringContainsString("const OFFLINE_URL = '/offline';", $sw);
        $this->assertStringContainsString('chv-user', $sw);
        $this->assertStringContainsString('CLEAR_PAGES', $sw);
    }

    public function test_logout_pages_load_cache_clearing_script(): void
    {
        $js = file_get_contents(public_path('pwa.js'));
        $this->assertStringContainsString('function isLogoutForm', $js);
        $this->assertStringContainsString("type: 'CLEAR_PAGES'", $js);

        // Chiqish tugmasi bor sahifalar pwa.js ni yuklaydi
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('action="' . route('logout') . '"', false)
            ->assertSee(asset('pwa.js') . '?v=', false);

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('verification.notice'))->assertOk()
            ->assertSee('action="' . route('logout') . '"', false)
            ->assertSee(asset('pwa.js') . '?v=', false);
    }
}
