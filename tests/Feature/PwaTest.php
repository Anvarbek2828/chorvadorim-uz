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

    public function test_cache_version_is_v4(): void
    {
        $this->assertStringContainsString("const CACHE_VERSION = 'v4';", file_get_contents(public_path('sw.js')));
    }

    public function test_sw_navigate_strategy_never_serves_cache_online(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // Sahifa funksiyasi: tarmoq javobi har doim qaytariladi; kesh faqat catch (tarmoq xatosi) ichida
        preg_match('/async function networkFirstPage\(event\) \{(.*?)\n\}/s', $sw, $m);
        $this->assertNotEmpty($m, 'networkFirstPage topilmadi');
        $body = $m[1];
        $this->assertMatchesRegularExpression('/try \{\s*response = await fetch\(req\);\s*\} catch \(err\) \{\s*(\/\/[^\n]*\n\s*)?return offlineFallback\(req\);\s*\}/', $body);
        $this->assertStringNotContainsString('matchPage', $body);
        $this->assertStringNotContainsString('caches.match', $body);
        $this->assertStringContainsString('return response;', $body);
        $this->assertSame(1, substr_count($sw, 'return offlineFallback(req)'), 'offlineFallback faqat tarmoq xatosida chaqiriladi');

        // Faqat 200, redirect bo'lmagan, basic HTML keshlanadi
        preg_match('/function isCacheablePage\(response\) \{(.*?)\n\}/s', $sw, $c);
        $this->assertNotEmpty($c);
        foreach (['response.status === 200', 'response.ok', "response.type === 'basic'", '!response.redirected', "type.includes('text/html')", "!cacheControl.includes('no-store')"] as $rule) {
            $this->assertStringContainsString($rule, $c[1], "isCacheablePage da yo'q: {$rule}");
        }

        // SW o'zi yuboradigan so'rovlar cookie'siz (sessiya cookie'sini qayta yozmasin)
        $this->assertStringContainsString("new Request(url, { cache: 'reload', credentials: 'omit' })", $sw);
        $this->assertStringNotContainsString("credentials: 'same-origin'", $sw);
    }

    public function test_pwa_js_never_posts_from_cached_page(): void
    {
        $js = file_get_contents(public_path('pwa.js'));

        // Keshdagi sahifa: POST to'xtatiladi (internet qaytgan bo'lsa ham), internet qaytsa qayta yuklanadi
        $this->assertStringContainsString("if (method !== 'get' && (isOffline() || fromCache))", $js);
        $this->assertStringContainsString('t.cachedForm', $js);
        $this->assertStringContainsString('if (fromCache && !typed && !reloadingFresh)', $js);
        $this->assertStringNotContainsString('fromCache = false', $js);

        // Internetni tekshirish statik fayl orqali, cookie'siz (Laravel sessiyasiga tegmaydi)
        $this->assertStringContainsString("fetch('/manifest.json?online=' + Date.now(), { method: 'HEAD', cache: 'no-store', credentials: 'omit' })", $js);
        $this->assertStringNotContainsString("fetch('/offline'", $js);
        $this->assertStringNotContainsString("credentials: 'same-origin'", $js);

        $this->get('/')->assertSee('"cachedForm":', false);
    }

    public function test_ios_steps_in_three_languages(): void
    {
        // config nomi => [kalit (uz), kerakli belgi]
        $steps = [
            'iosStep1Other' => ["Tepada, «chorvadorim.uz» yozuvi yonidagi :share tugmasini bosing.", ':share'],
            'iosStep1SafariPhone' => ['Ekran pastidagi :share tugmasini bosing.', ':share'],
            'iosStep1SafariPad' => ['Ekran tepasidagi :share tugmasini bosing.', ':share'],
            'iosStep2' => ["Ochilgan ro'yxatni pastga suring.", null],
            'iosStep3Other' => [":add «Добавить на экран «Домой»» ni bosing (telefon inglizcha bo'lsa — «Add to Home Screen»).", ':add'],
            'iosStep3Safari' => [':add «Добавить на экран «Домой»» ni bosing (inglizcha — «Add to Home Screen»).', ':add'],
        ];

        foreach (['uz_cyrl', 'ru'] as $locale) {
            $json = json_decode(file_get_contents(lang_path($locale . '.json')), true);
            foreach ($steps as [$key, $icon]) {
                $this->assertArrayHasKey($key, $json, "{$locale}.json da yo'q: {$key}");
                if ($icon) {
                    $this->assertStringContainsString($icon, $json[$key], "{$locale}: belgi o'rni yo'q");
                }
                // Menyu bandi nomi tarjima qilinmaydi
                if ($icon === ':add') {
                    $this->assertStringContainsString('«Добавить на экран «Домой»»', $json[$key]);
                    $this->assertStringContainsString('«Add to Home Screen»', $json[$key]);
                }
            }
        }

        foreach (['uz', 'uz_cyrl', 'ru'] as $locale) {
            $response = $this->withSession(['locale' => $locale])->get('/')->assertOk()
                ->assertSee('«Добавить на экран «Домой»»', false)
                ->assertSee('«Add to Home Screen»', false);
            foreach ($steps as $name => [$key]) {
                $value = json_encode(__($key, [], $locale), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                $response->assertSee('"' . $name . '":' . $value, false);
            }
        }
    }

    public function test_pwa_js_builds_steps_with_share_and_add_icons(): void
    {
        $js = file_get_contents(public_path('pwa.js'));
        $partial = file_get_contents(resource_path('views/layouts/partials/pwa-scripts.blade.php'));

        // :add — yumaloq burchakli kvadrat ichida "+"
        $this->assertStringContainsString("add: { cls: 'chv-add-icon', rect: true, paths: ['M12 8v8', 'M8 12h8'] }", $js);
        $this->assertStringContainsString("split(/(:share|:add)/)", $js);
        $this->assertStringContainsString("el('ol', 'chv-install__steps')", $js);
        $this->assertStringContainsString('.chv-install--ios', $partial);
        $this->assertStringContainsString('max-height: 50vh', $partial);
        $this->assertStringContainsString('width: 20px; height: 20px', $partial);
    }

    public function test_pwa_js_detects_ios_browsers_and_in_app(): void
    {
        $js = file_get_contents(public_path('pwa.js'));

        foreach (['CriOS', 'EdgiOS', 'FxiOS', 'iPhone|iPod', 'Macintosh', 'maxTouchPoints', 'Telegram', 'Instagram', 'FBAN', 'TelegramWebviewProxy', 'navigator.standalone', '(display-mode: standalone)'] as $needle) {
            $this->assertStringContainsString($needle, $js, "pwa.js da yo'q: {$needle}");
        }

        // In-app brauzer va o'rnatilgan holatda taklif yo'q; iOS ko'rsatmasi ham in-app'da chiqmaydi
        $this->assertStringContainsString('var canOffer = !standalone && !inApp;', $js);
        $this->assertStringContainsString('var iosOffer = isIOS && !inApp;', $js);
        // Eski "faqat Safari" sharti olib tashlangan
        $this->assertStringNotContainsString('var iosSafari', $js);
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
