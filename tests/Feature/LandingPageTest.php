<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bosh sahifa (/): mehmon va kirgan foydalanuvchi, bo'limlar, bazadagi tariflar, aloqa havolalari, 3 til.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $attributes): Plan
    {
        $plan = new Plan();
        $plan->forceFill(array_merge([
            'narx_som' => null,
            'max_hayvon' => 10,
            'max_staff' => 0,
            'oylik_chat_limit' => 10,
            'oylik_vision_limit' => 1,
            'faol' => true,
        ], $attributes))->save();

        return $plan;
    }

    public function test_guest_sees_landing_with_register_and_login(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(__('Chorvangiz hisobi — telefoningizda'))
            ->assertSee(__('Bepul boshlash'))
            ->assertSee('href="' . route('register') . '"', false)
            ->assertSee('href="' . route('login') . '"', false)
            ->assertDontSee(__("Kabinetga o'tish"));
    }

    public function test_logged_in_user_sees_landing_with_dashboard_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()
            ->assertSee(__("Kabinetga o'tish"))
            ->assertSee('href="' . route('dashboard') . '"', false)
            ->assertDontSee('href="' . route('register') . '"', false)
            ->assertDontSee('href="' . route('login') . '"', false);
    }

    public function test_main_sections_exist(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('class="lp-hero"', false)
            ->assertSee('id="imkoniyatlar"', false)
            ->assertSee('id="tariflar"', false)
            ->assertSee('id="savollar"', false)
            ->assertSee('href="#imkoniyatlar"', false)
            ->assertSee('href="#tariflar"', false)
            ->assertSee('href="#savollar"', false)
            ->assertSee('<main id="asosiy">', false)
            ->assertSee(__("Ma'lumotlarim xavfsizmi?"))
            ->assertSee(__("To'lov qanday qilinadi?"))
            ->assertSee(__('Bepul tarifda nima bor?'));
    }

    public function test_plans_come_from_database(): void
    {
        Plan::query()->update(['faol' => false]);

        $this->makePlan(['kod' => 'sinov', 'nomi' => 'Sinov tarifi', 'max_hayvon' => 77, 'max_staff' => 3, 'oylik_chat_limit' => 55, 'oylik_vision_limit' => 11]);
        $this->makePlan(['kod' => 'narxli', 'nomi' => 'Narxli tarif', 'narx_som' => 150000, 'max_hayvon' => 500, 'max_staff' => null]);
        $this->makePlan(['kod' => 'yashirin', 'nomi' => 'Yashirin tarif', 'faol' => false]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder([
                'Sinov tarifi',
                __("Narx bo'yicha murojaat qiling"),
                __(':count tagacha hayvon', ['count' => 77]),
                __('Oyiga :count ta AI maslahat', ['count' => 55]),
                __('Oyiga :count ta rasm tahlili', ['count' => 11]),
                __(':count tagacha xodim', ['count' => 3]),
            ])
            ->assertSeeInOrder([
                'Narxli tarif',
                __(":price so'm / oy", ['price' => '150 000']),
                __('Xodimlar soni cheklanmagan'),
            ])
            ->assertDontSee('Yashirin tarif')
            ->assertDontSee('>Free<', false);
    }

    public function test_free_plan_shows_free_text_not_contact(): void
    {
        Plan::where('kod', '!=', Plan::FREE)->update(['faol' => false]);
        Plan::where('kod', Plan::FREE)->update(['narx_som' => null, 'max_hayvon' => 15]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Free', __('Bepul'), __(':count tagacha hayvon', ['count' => 15])])
            ->assertDontSee(__("Narx bo'yicha murojaat qiling"))
            // Savol-javobdagi bepul tarif limitlari ham bazadan
            ->assertSee(__('Bepul tarifda :animals tagacha hayvon, oyiga :chat ta AI maslahat va :vision ta rasm tahlili bor. Eslatmalar, Telegram bot, xarajat va daromad hisobi ham ishlaydi.', [
                'animals' => 15,
                'chat' => Plan::free()->oylik_chat_limit,
                'vision' => Plan::free()->oylik_vision_limit,
            ]));
    }

    public function test_contact_and_bot_links(): void
    {
        $this->assertSame('chorvadorim_bot', config('chorvadorim.telegram_bot'));

        $this->get('/')->assertOk()
            ->assertSee('href="https://t.me/chorvadorim_bot" target="_blank" rel="noopener"', false)
            ->assertSee('href="tel:+998972362828"', false)
            ->assertSee('href="https://t.me/smartshop571" target="_blank" rel="noopener"', false)
            ->assertSee('+998 97 236 28 28')
            ->assertSee('@smartshop571')
            // Har sahifadagi yordam qatori
            ->assertSee('class="chv-support"', false)
            ->assertSee(__('Chorvadorim yordam xizmati'));
    }

    public function test_seo_meta_tags(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<meta name="description" content="' . e(__("Har bir hayvon, emlash, xarajat va daromad bir joyda. Eslatmalar Telegram orqali keladi, AI veterinar maslahat beradi. Bepul boshlash mumkin.")) . '">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta property="og:url" content="' . url('/') . '">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false);
    }

    public function test_title_and_heading_in_three_languages(): void
    {
        $cases = [
            'uz' => ['uz', 'Chorvangiz hisobi — telefoningizda'],
            'uz_cyrl' => ['uz-Cyrl', 'Чорвангиз ҳисоби — телефонингизда'],
            'ru' => ['ru', 'Учёт вашего скота — в вашем телефоне'],
        ];

        foreach ($cases as $locale => [$htmlLang, $heading]) {
            $this->withSession(['locale' => $locale])->get('/')->assertOk()
                ->assertSee('<html lang="' . $htmlLang . '">', false)
                ->assertSee('<title>' . e(__('Chorvadorim — chorvangiz hisobi telefoningizda', [], $locale)) . '</title>', false)
                ->assertSee('>' . $heading . '</h1>', false);
        }

        $this->assertNotSame(
            __('Chorvadorim — chorvangiz hisobi telefoningizda', [], 'uz'),
            __('Chorvadorim — chorvangiz hisobi telefoningizda', [], 'ru')
        );
    }
}
