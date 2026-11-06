<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PageExpired;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 419 (CSRF token eskirgan): tushunarli qaytish, logout baribir chiqaradi, JSON o'zgarmaydi.
 * Testlarda Laravel CSRF tekshiruvini o'tkazib yuboradi, shuning uchun bu yerda u haqiqiy holatda yoqiladi.
 */
class PageExpiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken {
                protected function runningUnitTests()
                {
                    return false;
                }
            };
        });
    }

    public function test_csrf_is_really_enforced_in_these_tests(): void
    {
        $this->post(route('login'), ['email' => 'a@example.com', 'password' => 'x'], ['Accept' => 'application/json'])
            ->assertStatus(419);
    }

    public function test_expired_login_form_returns_back_with_message_and_input(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            '_token' => 'eskirgan-token',
            'email' => 'fermer@example.com',
            'password' => 'maxfiy-parol',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas(PageExpired::FLASH_KEY, __("Sahifa eskirgan edi. Iltimos, qayta urinib ko'ring."))
            ->assertSessionHasInput('email', 'fermer@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input._token');

        // Xabar va kiritilgan email kirish sahifasida ko'rinadi
        $this->get(route('login'))->assertOk()
            ->assertSee(__("Sahifa eskirgan edi. Iltimos, qayta urinib ko'ring."))
            ->assertSee('value="fermer@example.com"', false);

        $this->assertGuest();
    }

    public function test_message_in_three_languages(): void
    {
        $expected = [
            'uz' => "Sahifa eskirgan edi. Iltimos, qayta urinib ko'ring.",
            'uz_cyrl' => 'Саҳифа эскирган эди. Илтимос, қайта уриниб кўринг.',
            'ru' => 'Страница устарела. Пожалуйста, попробуйте ещё раз.',
        ];

        foreach ($expected as $locale => $text) {
            $this->withSession(['locale' => $locale])
                ->from(route('login'))
                ->post(route('login'), ['email' => 'x@example.com', 'password' => 'p'])
                ->assertRedirect(route('login'))
                ->assertSessionHas(PageExpired::FLASH_KEY, $text);
        }
    }

    public function test_expired_form_of_logged_in_user_returns_to_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => 'Yangi ism', 'email' => $user->email])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas(PageExpired::FLASH_KEY)
            ->assertSessionHasInput('name', 'Yangi ism');

        // O'zgarish saqlanmagan, foydalanuvchi tizimda qolgan, xabar sahifada ko'rinadi
        $this->assertNotSame('Yangi ism', $user->fresh()->name);
        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.edit'))->assertOk()
            ->assertSee(__("Sahifa eskirgan edi. Iltimos, qayta urinib ko'ring."));
    }

    public function test_without_referer_goes_to_login_or_home(): void
    {
        $this->post(route('login'), ['email' => 'x@example.com', 'password' => 'p'])
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'X'])
            ->assertRedirect(url('/'));

        // Boshqa saytdan kelgan Referer ishlatilmaydi
        $this->actingAs($user)->withHeader('Referer', 'https://boshqa-sayt.example/sahifa')
            ->patch(route('profile.update'), ['name' => 'X'])
            ->assertRedirect(url('/'));
    }

    public function test_expired_logout_still_logs_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('logout'), ['_token' => 'eskirgan-token'])
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_json_request_keeps_json_419(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('telegram.generate-code'))
            ->assertStatus(419)
            ->assertJsonStructure(['message']);

        $this->actingAs($user)
            ->postJson(route('logout'))
            ->assertStatus(419);
        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_token_still_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['_token' => 'togri-token'])
            ->post(route('logout'), ['_token' => 'togri-token'])
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
