<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aloqa ma'lumotlari: config, har sahifa pastidagi yordam qatori (footer), /tariflar sahifasidagi tugmalar.
 */
class ContactInfoTest extends TestCase
{
    use RefreshDatabase;

    private const TELEGRAM_LINK = 'href="https://t.me/smartshop571" target="_blank" rel="noopener"';

    private const PHONE_LINK = 'href="tel:+998972362828"';

    public function test_contact_config_values(): void
    {
        $this->assertSame([
            'phone' => '+998972362828',
            'phone_display' => '+998 97 236 28 28',
            'telegram' => 'smartshop571',
        ], config('chorvadorim.contact'));
    }

    public function test_footer_on_main_layout_pages(): void
    {
        $user = User::factory()->create();

        foreach (['dashboard', 'profile.edit', 'plans.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('class="chv-support"', false)
                ->assertSee(self::TELEGRAM_LINK, false)
                ->assertSee(__("Yordam kerak bo'lsa, Telegram orqali yozing:"))
                ->assertSee(__('Chorvadorim yordam xizmati'))
                ->assertSee(__('Imkon qadar tez javob beramiz.'));
        }
    }

    public function test_footer_on_admin_pages(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('class="chv-support"', false)
            ->assertSee(self::TELEGRAM_LINK, false);
    }

    public function test_footer_on_guest_layout_pages(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('class="chv-support"', false)
                ->assertSee(self::TELEGRAM_LINK, false)
                ->assertSee(__('Chorvadorim yordam xizmati'));
        }
    }

    public function test_footer_text_in_three_languages(): void
    {
        $expected = [
            'uz' => ["Yordam kerak bo'lsa, Telegram orqali yozing:", 'Chorvadorim yordam xizmati', 'Imkon qadar tez javob beramiz.'],
            'uz_cyrl' => ['Ёрдам керак бўлса, Telegram орқали ёзинг:', 'Chorvadorim ёрдам хизмати', 'Имкон қадар тез жавоб берамиз.'],
            'ru' => ['Если нужна помощь, напишите в Telegram:', 'Служба поддержки Chorvadorim', 'Ответим как можно быстрее.'],
        ];

        foreach ($expected as $locale => $texts) {
            $this->withSession(['locale' => $locale])->get(route('login'))->assertOk()->assertSeeInOrder($texts);
        }
    }

    public function test_plans_page_has_phone_and_telegram_buttons(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('plans.index'))->assertOk()
            ->assertSee(__("Tarifni o'zgartirish yoki to'lov qilish uchun biz bilan bog'laning:"))
            ->assertDontSee("administratsiyaga murojaat qiling", false)
            ->assertSee(self::PHONE_LINK, false)
            ->assertSee('+998 97 236 28 28')
            ->assertSee('href="https://t.me/smartshop571" target="_blank" rel="noopener" class="chv-contact-btn chv-contact-btn--telegram"', false)
            ->assertSee('@smartshop571');

        $texts = [
            'uz_cyrl' => 'Тарифни ўзгартириш ёки тўлов қилиш учун биз билан боғланинг:',
            'ru' => 'Чтобы сменить тариф или оплатить подписку, свяжитесь с нами:',
        ];
        foreach ($texts as $locale => $text) {
            $this->actingAs($owner)->withSession(['locale' => $locale])->get(route('plans.index'))->assertOk()
                ->assertSee($text)->assertSee(self::PHONE_LINK, false);
        }
    }
}
