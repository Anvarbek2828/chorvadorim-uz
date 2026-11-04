<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFarmWritable;
use App\Models\AdminAuditLog;
use App\Models\Animal;
use App\Models\BankAnimalWatcher;
use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmPayment;
use App\Models\FarmUser;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\SubscriptionNotification;
use App\Models\User;
use App\Services\DailySubscriptionRunner;
use App\Services\SubscriptionNotifier;
use App\Services\SubscriptionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Js;
use Tests\TestCase;

/**
 * Obuna va to'lov: holat, muddat hisobi, admin amallari, faqat o'qish rejimi, banner, bot xabarlari, kunlik ish.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    // Testlardagi "bugun" (Toshkent): 2026-11-10, 09:00
    private const TODAY = '2026-11-10';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse(self::TODAY . ' 09:00', 'Asia/Tashkent'));
    }

    private function farmOf(User $user): Farm
    {
        return $user->ownedFarms()->firstOrFail();
    }

    private function setPlan(Farm $farm, string $kod, ?string $endDate): Farm
    {
        $farm->plan_id = Plan::where('kod', $kod)->value('id');
        $farm->plan_tugash_sanasi = $endDate;
        $farm->save();

        // Testda ilova so'rovlar orasida qayta yaratilmaydi: joriy ferma keshi (CurrentFarm) tozalanadi
        $this->app->forgetScopedInstances();

        return $farm->fresh();
    }

    // Bugundan $days kun oldin/keyin tugaydigan (manfiy — o'tgan) Basic tarifli ferma
    private function farmEndingIn(int $days, ?User $owner = null): Farm
    {
        $owner ??= User::factory()->create();

        return $this->setPlan($this->farmOf($owner), 'basic', CarbonImmutable::parse(self::TODAY)->addDays($days)->toDateString());
    }

    private function addMember(Farm $farm, string $role, string $status = FarmUser::STATUS_ACTIVE): User
    {
        $member = User::factory()->create();
        $member->ownedFarms()->delete();
        $farm->members()->attach($member->id, ['role' => $role, 'status' => $status]);
        $member->forceFill(['current_farm_id' => $farm->id])->save();

        return $member;
    }

    private function animalIn(Farm $farm, array $attributes = []): Animal
    {
        return Animal::create($attributes + [
            'farm_id' => $farm->id,
            'user_id' => $farm->owner_id,
            'name' => 'Sigir ' . $farm->id,
            'direction' => 'sut',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function farmStatus(Farm $farm): string
    {
        return app(SubscriptionService::class)->status($farm->fresh());
    }

    private function webhook(array $payload)
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')->postJson('/telegram/webhook', $payload)->assertOk();
    }

    private function fakeTelegram(): void
    {
        config(['services.telegram.webhook_secret' => 'secret', 'services.telegram.bot_token' => 'token']);
        Http::fake();
    }

    // ---------------------------------------------------------------- holat

    public function test_status_free_unlimited_active_grace_read_only(): void
    {
        $farm = $this->farmOf(User::factory()->create());
        $this->assertSame(SubscriptionService::STATUS_FREE, $this->farmStatus($farm));

        // Bepul tarif, eski tugash sanasi bilan ham — free
        $this->setPlan($farm, 'free', '2026-01-01');
        $this->assertSame(SubscriptionService::STATUS_FREE, $this->farmStatus($farm));

        $this->setPlan($farm, 'basic', null);
        $this->assertSame(SubscriptionService::STATUS_UNLIMITED, $this->farmStatus($farm));

        $cases = [
            '2026-12-01' => SubscriptionService::STATUS_ACTIVE,
            '2026-11-10' => SubscriptionService::STATUS_ACTIVE,      // T kuni
            '2026-11-09' => SubscriptionService::STATUS_GRACE,       // T+1
            '2026-11-08' => SubscriptionService::STATUS_GRACE,       // T+2
            '2026-11-07' => SubscriptionService::STATUS_READ_ONLY,   // T+3
            '2026-10-01' => SubscriptionService::STATUS_READ_ONLY,
        ];

        foreach ($cases as $end => $expected) {
            $this->setPlan($farm, 'basic', $end);
            $this->assertSame($expected, $this->farmStatus($farm), "T = {$end}");
        }

        $this->setPlan($farm, 'basic', '2026-11-09');
        $this->assertSame(2, app(SubscriptionService::class)->daysUntilReadOnly($farm->fresh()));
    }

    public function test_status_uses_tashkent_day_boundary(): void
    {
        $farm = $this->setPlan($this->farmOf(User::factory()->create()), 'basic', '2026-11-10');

        // UTC 18:59 = Toshkent 23:59 (T kuni)
        $this->travelTo(CarbonImmutable::parse('2026-11-10 18:59:59', 'UTC'));
        $this->assertSame(SubscriptionService::STATUS_ACTIVE, $this->farmStatus($farm));

        // UTC 19:00 = Toshkent ertasi 00:00 (T+1)
        $this->travelTo(CarbonImmutable::parse('2026-11-10 19:00:00', 'UTC'));
        $this->assertSame(SubscriptionService::STATUS_GRACE, $this->farmStatus($farm));

        // UTC 23:59 hali ham Toshkentda T+1
        $this->travelTo(CarbonImmutable::parse('2026-11-10 23:59:59', 'UTC'));
        $this->assertSame(SubscriptionService::STATUS_GRACE, $this->farmStatus($farm));

        // T+2 -> T+3 chegarasi
        $this->setPlan($farm, 'basic', '2026-11-08');
        $this->travelTo(CarbonImmutable::parse('2026-11-10 18:59:59', 'UTC'));
        $this->assertSame(SubscriptionService::STATUS_GRACE, $this->farmStatus($farm));
        $this->travelTo(CarbonImmutable::parse('2026-11-10 19:00:00', 'UTC'));
        $this->assertSame(SubscriptionService::STATUS_READ_ONLY, $this->farmStatus($farm));
    }

    // ---------------------------------------------------------------- muddat hisobi

    public function test_new_end_date_calculation(): void
    {
        $service = app(SubscriptionService::class);
        $farm = $this->farmOf(User::factory()->create());
        $end = fn (?string $planEnd, int $months, string $kod = 'basic') => $service->newEndDate($this->setPlan($farm, $kod, $planEnd), $months)->toDateString();

        // Faoldan: T+1 dan davom etadi
        $this->assertSame('2026-12-20', $end('2026-11-20', 1));
        // Imtiyozdan (T+1, T+2): baribir T+1 dan
        $this->assertSame('2026-12-09', $end('2026-11-09', 1));
        $this->assertSame('2026-12-08', $end('2026-11-08', 1));
        // Faqat o'qishdan: bugundan
        $this->assertSame('2026-12-09', $end('2026-11-01', 1));
        // Bepul va muddatsiz: bugundan
        $this->assertSame('2026-12-09', $end(null, 1, 'free'));
        $this->assertSame('2026-12-09', $end(null, 1));
        // 3 / 6 / 12 oy (bugundan)
        $this->assertSame('2027-02-09', $end(null, 3, 'free'));
        $this->assertSame('2027-05-09', $end(null, 6, 'free'));
        $this->assertSame('2027-11-09', $end(null, 12, 'free'));
        // 3 / 6 / 12 oy (faoldan)
        $this->assertSame('2027-02-20', $end('2026-11-20', 3));
        $this->assertSame('2027-05-20', $end('2026-11-20', 6));
        $this->assertSame('2027-11-20', $end('2026-11-20', 12));

        // Oy oxiri: boshlanish 31-yanvar, +1 oy (toshib ketmaydi) = 28-fevral, -1 kun
        $this->travelTo(CarbonImmutable::parse('2027-01-31 09:00', 'Asia/Tashkent'));
        $this->assertSame('2027-02-27', $end(null, 1, 'free'));
        $this->assertSame('2027-04-29', $end(null, 3, 'free'));
        // Kabisa yili: 31-yanvar 2028 -> 29-fevral -> 28-fevral
        $this->travelTo(CarbonImmutable::parse('2028-01-31 09:00', 'Asia/Tashkent'));
        $this->assertSame('2028-02-28', $end(null, 1, 'free'));
        // Boshlanish 28-fevral (oddiy yil) va 29-fevral (kabisa)
        $this->travelTo(CarbonImmutable::parse('2027-02-28 09:00', 'Asia/Tashkent'));
        $this->assertSame('2027-03-27', $end(null, 1, 'free'));
        $this->travelTo(CarbonImmutable::parse('2028-02-29 09:00', 'Asia/Tashkent'));
        $this->assertSame('2028-03-28', $end(null, 1, 'free'));
        $this->assertSame('2029-02-27', $end(null, 12, 'free'));
        // Faol, T = 30-yanvar -> boshlanish 31-yanvar
        $this->travelTo(CarbonImmutable::parse('2027-01-20 09:00', 'Asia/Tashkent'));
        $this->assertSame('2027-02-27', $end('2027-01-30', 1));
    }

    // ---------------------------------------------------------------- admin: to'landi

    public function test_admin_payment_extends_plan_syncs_user_and_writes_audit(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(5, $owner);
        $pro = Plan::where('kod', 'pro')->firstOrFail();

        // Free tarif va summasiz — rad etiladi
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => Plan::free()->id, 'oylar' => 1, 'summa' => 100, 'usul' => 'bank',
        ])->assertSessionHasErrors('plan_id');
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => $pro->id, 'oylar' => 1, 'usul' => 'bank',
        ])->assertSessionHasErrors('summa');
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => $pro->id, 'oylar' => 2, 'summa' => 100, 'usul' => 'bank',
        ])->assertSessionHasErrors('oylar');
        $this->assertSame(0, FarmPayment::count());

        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => $pro->id, 'oylar' => 3, 'summa' => 450000, 'usul' => 'naqd', 'hujjat_raqami' => 'K-15', 'izoh' => 'Kassa',
        ])->assertRedirect(route('admin.farms.show', $farm))->assertSessionHas('success');

        // T = 15-noyabr -> 16-noyabr + 3 oy - 1 kun
        $farm->refresh();
        $this->assertSame($pro->id, $farm->plan_id);
        $this->assertSame('2027-02-15', $farm->plan_tugash_sanasi->toDateString());
        $owner->refresh();
        $this->assertSame($pro->id, $owner->plan_id);
        $this->assertSame('2027-02-15', $owner->plan_tugash_sanasi->toDateString());

        $payment = FarmPayment::firstOrFail();
        $this->assertSame(FarmPayment::TYPE_PAYMENT, $payment->tur);
        $this->assertSame($farm->name, $payment->farm_nomi);
        $this->assertSame(Plan::where('kod', 'basic')->value('id'), $payment->avvalgi_plan_id);
        $this->assertSame('2026-11-15', $payment->avvalgi_tugash->toDateString());
        $this->assertSame(450000, $payment->summa);
        $this->assertSame('naqd', $payment->usul);
        $this->assertSame('K-15', $payment->hujjat_raqami);
        $this->assertSame($admin->id, $payment->admin_id);

        $this->assertDatabaseHas('admin_audit_logs', ['amal' => AdminAuditLog::ACTION_SUBSCRIPTION_EXTENDED, 'farm_id' => $farm->id, 'admin_id' => $admin->id]);
        $this->assertDatabaseHas('farm_audit_logs', ['amal' => FarmAuditLog::ACTION_SUBSCRIPTION_EXTENDED, 'farm_id' => $farm->id, 'user_id' => $admin->id, 'obyekt_id' => $payment->id]);

        // Oylar berilmasa — 1 oy
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), ['plan_id' => $pro->id, 'summa' => 150000, 'usul' => 'bank'])
            ->assertSessionHas('success');
        $this->assertSame('2027-03-15', $farm->fresh()->plan_tugash_sanasi->toDateString());

        // Admin sahifalari ochiladi
        $this->actingAs($admin)->get(route('admin.farms.show', $farm))->assertOk()->assertSee('K-15')->assertSee('450 000');
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee($farm->name);
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => $owner->email]))->assertOk()->assertSee(route('admin.farms.show', $farm));
    }

    public function test_payment_via_payment_systems_method(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(5, $owner);
        $pro = Plan::where('kod', 'pro')->firstOrFail();
        $label = "To'lov tizimlari orqali";

        // Tanlov ro'yxatidagi tartib: bank, to'lov tizimlari, naqd, boshqa
        $this->assertSame(['bank', 'tolov_tizimi', 'naqd', 'boshqa'], FarmPayment::METHODS);
        $this->actingAs($admin)->get(route('admin.farms.show', $farm))->assertOk()
            ->assertSeeInOrder(['value="bank"', 'value="tolov_tizimi"', 'value="naqd"', 'value="boshqa"'], false)
            ->assertSee(__($label));

        // Ro'yxatda yo'q qiymat — rad etiladi
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => $pro->id, 'oylar' => 1, 'summa' => 150000, 'usul' => 'karta',
        ])->assertSessionHasErrors('usul');
        $this->assertSame(0, FarmPayment::count());

        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => $pro->id, 'oylar' => 1, 'summa' => 150000, 'usul' => 'tolov_tizimi', 'hujjat_raqami' => 'PT-9',
        ])->assertSessionHas('success');
        $this->assertSame('tolov_tizimi', FarmPayment::firstOrFail()->usul);

        // Admin "To'lovlar" ro'yxati, ferma sahifasidagi tarix va egasining to'lovlar tarixi
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee('150 000')->assertSee(__($label));
        $this->actingAs($admin)->get(route('admin.farms.show', $farm))->assertOk()->assertSee('PT-9')->assertSee('150 000');
        $this->actingAs($owner)->get(route('farm-payments.index'))->assertOk()->assertSee('PT-9')->assertSee(__($label));
        $this->actingAs($owner)->withSession(['locale' => 'ru'])->get(route('farm-payments.index'))->assertOk()->assertSee('Через платёжные системы');
        $this->actingAs($owner)->withSession(['locale' => 'uz_cyrl'])->get(route('farm-payments.index'))->assertOk()->assertSee('Тўлов тизимлари орқали');
    }

    public function test_payment_with_smaller_plan_suspends_excess_staff(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->setPlan($this->farmOf($owner), 'pro', '2026-12-01');
        $first = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $this->travel(1)->minutes();
        $second = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $this->travel(1)->minutes();
        $third = $this->addMember($farm, FarmUser::ROLE_VIEWER);

        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), [
            'plan_id' => Plan::where('kod', 'basic')->value('id'), 'oylar' => 1, 'summa' => 1, 'usul' => 'bank',
        ])->assertSessionHas('success');

        $status = fn (User $user) => FarmUser::where('farm_id', $farm->id)->where('user_id', $user->id)->value('status');
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($first));
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($second));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($third));
    }

    // ---------------------------------------------------------------- admin: bonus

    public function test_bonus_requires_reason_and_plan_when_free_or_read_only(): void
    {
        $admin = $this->admin();
        $basic = Plan::where('kod', 'basic')->firstOrFail();

        // Bepul ferma: sabab va tarif majburiy
        $free = $this->farmOf(User::factory()->create());
        $this->actingAs($admin)->post(route('admin.farms.bonus', $free), ['plan_id' => $basic->id])->assertSessionHasErrors('sabab');
        $this->actingAs($admin)->post(route('admin.farms.bonus', $free), ['sabab' => 'Aksiya'])->assertSessionHasErrors('plan_id');
        $this->actingAs($admin)->post(route('admin.farms.bonus', $free), ['sabab' => 'Aksiya', 'plan_id' => Plan::free()->id])->assertSessionHasErrors('plan_id');
        $this->assertSame(0, FarmPayment::count());

        $this->actingAs($admin)->post(route('admin.farms.bonus', $free), ['sabab' => 'Aksiya', 'plan_id' => $basic->id])->assertSessionHas('success');
        $free->refresh();
        $this->assertSame($basic->id, $free->plan_id);
        $this->assertSame('2026-12-09', $free->plan_tugash_sanasi->toDateString());
        $bonus = FarmPayment::firstOrFail();
        $this->assertSame(FarmPayment::TYPE_BONUS, $bonus->tur);
        $this->assertSame(0, $bonus->summa);
        $this->assertSame(1, $bonus->oylar);
        $this->assertNull($bonus->usul);
        $this->assertSame('Aksiya', $bonus->izoh);
        $this->assertDatabaseHas('admin_audit_logs', ['amal' => AdminAuditLog::ACTION_SUBSCRIPTION_BONUS, 'farm_id' => $free->id]);
        $this->assertDatabaseHas('farm_audit_logs', ['amal' => FarmAuditLog::ACTION_SUBSCRIPTION_BONUS, 'farm_id' => $free->id]);

        // Faol ferma: joriy tarif, tanlangan tarif e'tiborga olinmaydi, T+1 dan
        $active = $this->farmEndingIn(4);
        $this->actingAs($admin)->post(route('admin.farms.bonus', $active), ['sabab' => 'Uzr', 'plan_id' => Plan::where('kod', 'max')->value('id')])
            ->assertSessionHas('success');
        $active->refresh();
        $this->assertSame($basic->id, $active->plan_id);
        $this->assertSame('2026-12-14', $active->plan_tugash_sanasi->toDateString());

        // Faqat o'qishdagi ferma: tarif majburiy, bugundan
        $readOnly = $this->farmEndingIn(-5);
        $this->actingAs($admin)->post(route('admin.farms.bonus', $readOnly), ['sabab' => 'Uzr'])->assertSessionHasErrors('plan_id');
        $this->actingAs($admin)->post(route('admin.farms.bonus', $readOnly), ['sabab' => 'Uzr', 'plan_id' => $basic->id])->assertSessionHas('success');
        $this->assertSame('2026-12-09', $readOnly->fresh()->plan_tugash_sanasi->toDateString());

        // Muddatsiz fermaga bonus berilmaydi
        $unlimited = $this->setPlan($this->farmOf(User::factory()->create()), 'pro', null);
        $this->actingAs($admin)->post(route('admin.farms.bonus', $unlimited), ['sabab' => 'Uzr'])->assertSessionHas('error');
        $this->assertNull($unlimited->fresh()->plan_tugash_sanasi);
    }

    // ---------------------------------------------------------------- admin: to'lovsiz tarif almashtirish

    public function test_users_list_has_no_old_plan_form_and_links_to_farm_subscription(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => Plan::where('kod', 'pro')->value('id'), 'plan_tugash_sanasi' => '2026-12-31', 'sabab' => 'Test']);

        // Eski marshrut va controller metodi yo'q
        $this->assertFalse(Route::has('admin.users.plan'));
        $this->assertFalse(method_exists(\App\Http\Controllers\Admin\UserController::class, 'updatePlan'));
        $this->assertContains($this->actingAs($admin)->patch('/admin/users/' . $owner->id . '/plan', ['plan_id' => Plan::free()->id])->status(), [404, 405]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('/admin/users/' . $owner->id . '/plan')
            ->assertDontSee(__("Tarifni o'zgartirish"))
            ->assertSee('href="' . route('admin.farms.show', $farm) . '"', false)
            ->assertSee(__('Obuna') . ' →')
            // Joriy tarif va tugash sanasi ro'yxatda ko'rinadi
            ->assertSee('Pro')
            ->assertSee('31.12.2026');

        $this->assertSame(Plan::where('kod', 'pro')->value('id'), (int) $owner->fresh()->plan_id);
    }

    public function test_change_plan_without_payment_free_paid_with_date_and_unlimited(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->setPlan($this->farmOf($owner), 'basic', '2026-12-01');
        $pro = Plan::where('kod', 'pro')->firstOrFail();
        $free = Plan::free();

        // Ferma sahifasida forma, ogohlantirish va tasdiqlash matni
        $this->actingAs($admin)->get(route('admin.farms.show', $farm))
            ->assertOk()
            ->assertSee(route('admin.farms.plan', $farm))
            ->assertSee(__("Tarifni almashtirish (to'lovsiz)"))
            ->assertSee(__("Yangi tarifda xodimlar limiti kamroq bo'lsa, ortiqcha xodimlar (eng oxirgi qo'shilganlar) to'xtatiladi."))
            ->assertSee(Js::from(__("Bu amal to'lov yozuvisiz tarifni o'zgartiradi. Davom etasizmi?"))->toHtml(), false);

        // Pullik + sana
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro->id, 'plan_tugash_sanasi' => '2027-03-15', 'sabab' => 'Hamkor'])
            ->assertRedirect(route('admin.farms.show', $farm))
            ->assertSessionHas('success');
        $farm->refresh();
        $this->assertSame($pro->id, $farm->plan_id);
        $this->assertSame('2027-03-15', $farm->plan_tugash_sanasi->toDateString());
        $this->assertSame($pro->id, (int) $owner->fresh()->plan_id);
        $this->assertSame('2027-03-15', $owner->fresh()->plan_tugash_sanasi->toDateString());
        $this->assertSame(SubscriptionService::STATUS_ACTIVE, $this->farmStatus($farm));

        $adminLog = AdminAuditLog::where('amal', AdminAuditLog::ACTION_SUBSCRIPTION_PLAN_CHANGED)->where('farm_id', $farm->id)->sole();
        $this->assertSame(['plan' => 'basic', 'plan_tugash_sanasi' => '2026-12-01'], $adminLog->eski_qiymat);
        $this->assertSame(['plan' => 'pro', 'plan_tugash_sanasi' => '2027-03-15', 'sabab' => 'Hamkor'], $adminLog->yangi_qiymat);
        $this->assertSame($admin->id, $adminLog->admin_id);
        $farmLog = FarmAuditLog::where('amal', FarmAuditLog::ACTION_PLAN_CHANGED)->where('farm_id', $farm->id)->sole();
        $this->assertSame(['plan' => 'basic', 'plan_tugash_sanasi' => '2026-12-01'], $farmLog->eski_qiymat);
        $this->assertSame(['plan' => 'pro', 'plan_tugash_sanasi' => '2027-03-15', 'sabab' => 'Hamkor'], $farmLog->yangi_qiymat);
        $this->actingAs($owner)->get(route('farm-audit.index'))->assertOk()->assertSee(__('Tarif almashtirildi'));

        // Pullik + bo'sh sana — muddatsiz
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro->id, 'plan_tugash_sanasi' => '', 'sabab' => 'Doimiy'])
            ->assertSessionHas('success');
        $farm->refresh();
        $this->assertSame($pro->id, $farm->plan_id);
        $this->assertNull($farm->plan_tugash_sanasi);
        $this->assertNull($owner->fresh()->plan_tugash_sanasi);
        $this->assertSame(SubscriptionService::STATUS_UNLIMITED, $this->farmStatus($farm));

        // Free: sana berilsa ham NULL
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $free->id, 'plan_tugash_sanasi' => '2027-05-01', 'sabab' => "To'lamadi"])
            ->assertSessionHas('success');
        $farm->refresh();
        $this->assertSame($free->id, $farm->plan_id);
        $this->assertNull($farm->plan_tugash_sanasi);
        $this->assertSame($free->id, (int) $owner->fresh()->plan_id);
        $this->assertNull($owner->fresh()->plan_tugash_sanasi);
        $this->assertSame(SubscriptionService::STATUS_FREE, $this->farmStatus($farm));

        // farm_payments ga yozilmaydi, har bir almashtirish ikkala jurnalda
        $this->assertSame(0, FarmPayment::count());
        $this->assertSame(3, AdminAuditLog::where('amal', AdminAuditLog::ACTION_SUBSCRIPTION_PLAN_CHANGED)->where('farm_id', $farm->id)->count());
        $this->assertSame(3, FarmAuditLog::where('amal', FarmAuditLog::ACTION_PLAN_CHANGED)->where('farm_id', $farm->id)->count());
    }

    public function test_change_plan_requires_reason_and_admin(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->setPlan($this->farmOf($owner), 'basic', '2026-12-01');
        $pro = Plan::where('kod', 'pro')->value('id');

        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro])->assertSessionHasErrors('sabab');
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro, 'sabab' => '   '])->assertSessionHasErrors('sabab');
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['sabab' => 'Test'])->assertSessionHasErrors('plan_id');
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro, 'plan_tugash_sanasi' => 'ertaga', 'sabab' => 'Test'])->assertSessionHasErrors('plan_tugash_sanasi');

        // Admin bo'lmagan foydalanuvchi (ferma egasi ham): admin panel 404 qaytaradi
        $this->actingAs($owner)->post(route('admin.farms.plan', $farm), ['plan_id' => $pro, 'sabab' => 'Test'])->assertNotFound();
        $this->actingAs($owner)->get(route('admin.farms.show', $farm))->assertNotFound();

        $farm->refresh();
        $this->assertSame(Plan::where('kod', 'basic')->value('id'), $farm->plan_id);
        $this->assertSame('2026-12-01', $farm->plan_tugash_sanasi->toDateString());
        $this->assertSame(0, AdminAuditLog::where('amal', AdminAuditLog::ACTION_SUBSCRIPTION_PLAN_CHANGED)->count());
        $this->assertSame(0, FarmAuditLog::where('amal', FarmAuditLog::ACTION_PLAN_CHANGED)->count());
    }

    public function test_change_plan_to_smaller_plan_suspends_excess_staff(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->setPlan($this->farmOf($owner), 'pro', null);
        $first = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $this->travel(1)->minutes();
        $second = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $this->travel(1)->minutes();
        $third = $this->addMember($farm, FarmUser::ROLE_VIEWER);

        // Pro (5) -> Basic (2): eng oxirgi qo'shilgan to'xtatiladi
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => Plan::where('kod', 'basic')->value('id'), 'sabab' => 'Pasaytirish'])
            ->assertSessionHas('success', __('Tarif almashtirildi') . '. ' . __("Tarif limitidan ortiq :count ta xodim to'xtatildi.", ['count' => 1]));

        $status = fn (User $user) => FarmUser::where('farm_id', $farm->id)->where('user_id', $user->id)->value('status');
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($first));
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($second));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($third));
        $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'user_id' => null, 'amal' => FarmAuditLog::ACTION_STAFF_AUTO_SUSPENDED, 'obyekt_id' => $third->id]);

        // Free (0): qolgan xodimlar ham to'xtatiladi
        $this->actingAs($admin)->post(route('admin.farms.plan', $farm), ['plan_id' => Plan::free()->id, 'sabab' => 'Free'])->assertSessionHas('success');
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($first));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($second));
        $this->assertSame(0, FarmPayment::count());
    }

    // ---------------------------------------------------------------- admin: bekor qilish

    public function test_cancel_only_last_payment_restores_previous_values_once(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(5, $owner);
        $basicId = Plan::where('kod', 'basic')->value('id');
        $proId = Plan::where('kod', 'pro')->value('id');

        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), ['plan_id' => $basicId, 'oylar' => 1, 'summa' => 1, 'usul' => 'bank']);
        $this->actingAs($admin)->post(route('admin.farms.payment', $farm), ['plan_id' => $proId, 'oylar' => 1, 'summa' => 2, 'usul' => 'bank']);
        [$first, $second] = FarmPayment::orderBy('id')->get()->all();
        $this->assertSame('2027-01-15', $farm->fresh()->plan_tugash_sanasi->toDateString());

        // Oxirgisi emas — rad etiladi
        $this->actingAs($admin)->post(route('admin.payments.cancel', $first), ['sabab' => 'Xato'])->assertSessionHas('error');
        $this->assertNull($first->fresh()->bekor_qilingan_at);
        // Sababsiz — rad etiladi
        $this->actingAs($admin)->post(route('admin.payments.cancel', $second), [])->assertSessionHasErrors('sabab');

        $this->actingAs($admin)->post(route('admin.payments.cancel', $second), ['sabab' => 'Pul qaytdi'])->assertSessionHas('success');
        $farm->refresh();
        $this->assertSame($basicId, $farm->plan_id);
        $this->assertSame('2026-12-15', $farm->plan_tugash_sanasi->toDateString());
        $this->assertSame('2026-12-15', $owner->fresh()->plan_tugash_sanasi->toDateString());
        $second->refresh();
        $this->assertNotNull($second->bekor_qilingan_at);
        $this->assertSame('Pul qaytdi', $second->bekor_sababi);
        $this->assertSame($admin->id, $second->bekor_admin_id);
        // Yozuv o'chirilmaydi
        $this->assertSame(2, FarmPayment::count());

        // Ikkinchi marta bekor qilib bo'lmaydi
        $this->actingAs($admin)->post(route('admin.payments.cancel', $second), ['sabab' => 'Yana'])->assertSessionHas('error');
        $this->assertSame('Pul qaytdi', $second->fresh()->bekor_sababi);

        // Endi birinchisi oxirgi bekor qilinmagan — boshlang'ich holatga qaytadi
        $this->actingAs($admin)->post(route('admin.payments.cancel', $first), ['sabab' => 'Xato'])->assertSessionHas('success');
        $farm->refresh();
        $this->assertSame($basicId, $farm->plan_id);
        $this->assertSame('2026-11-15', $farm->plan_tugash_sanasi->toDateString());

        $this->assertSame(2, AdminAuditLog::where('amal', AdminAuditLog::ACTION_PAYMENT_CANCELLED)->where('farm_id', $farm->id)->count());
        $this->assertSame(2, FarmAuditLog::where('amal', FarmAuditLog::ACTION_PAYMENT_CANCELLED)->where('farm_id', $farm->id)->count());
    }

    // ---------------------------------------------------------------- faqat o'qish

    public function test_read_only_blocks_writes_for_every_role(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(-3, $owner);
        $animal = $this->animalIn($farm);
        $users = [
            'owner' => $owner,
            'manager' => $this->addMember($farm, FarmUser::ROLE_MANAGER),
            'worker' => $this->addMember($farm, FarmUser::ROLE_WORKER),
            'viewer' => $this->addMember($farm, FarmUser::ROLE_VIEWER),
        ];

        foreach ($users as $role => $user) {
            $this->actingAs($user)->from(route('animals.index'))
                ->post(route('animals.store'), ['name' => 'Yangi ' . $role, 'direction' => 'sut'])
                ->assertRedirect(route('animals.index'))
                ->assertSessionHas(EnsureFarmWritable::FLASH);

            $this->actingAs($user)->post(route('expenses.store', $animal), ['type' => 'yem', 'amount' => 100, 'expense_date' => '2026-11-10'])
                ->assertSessionHas(EnsureFarmWritable::FLASH);
            $this->actingAs($user)->post(route('reminders.store', $animal), ['title' => 'Emlash', 'due_date' => '2026-11-20'])
                ->assertSessionHas(EnsureFarmWritable::FLASH);

            // Ko'rish ishlaydi
            $this->actingAs($user)->get(route('animals.show', $animal))->assertOk();
        }

        $this->actingAs($owner)->put(route('animals.update', $animal), ['name' => 'Boshqa nom', 'direction' => 'sut'])->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->delete(route('animals.destroy', $animal))->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->post(route('animals.import.upload'))->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->patch(route('farm-settings.update'), ['name' => 'Yangi nom'])->assertSessionHas(EnsureFarmWritable::FLASH);

        // JSON so'rov — 403
        $this->actingAs($owner)->postJson(route('animals.store'), ['name' => 'Json', 'direction' => 'sut'])
            ->assertForbidden()
            ->assertJson(['message' => __("Bu amalni hozir bajarib bo'lmaydi: to'lov qilinmagani sababli faoliyat cheklangan.")]);

        $this->assertSame(1, Animal::withTrashed()->where('farm_id', $farm->id)->count());
        $this->assertSame('Sigir ' . $farm->id, $animal->fresh()->name);
        $this->assertFalse($animal->fresh()->trashed());
        $this->assertSame(0, $animal->expenses()->count());
        $this->assertSame(0, $animal->reminders()->count());
        $this->assertNotSame('Yangi nom', $farm->fresh()->name);

        // Xodimlar to'xtatilmaydi, ma'lumot va tarif o'zgarmaydi
        $this->assertSame(3, $farm->fresh()->activeStaffCount());
        $this->assertSame(Plan::where('kod', 'basic')->value('id'), $farm->fresh()->plan_id);

        // Xato xabari banner ichida ko'rinadi
        $this->actingAs($owner)->from(route('animals.index'))->post(route('animals.store'), ['name' => 'X', 'direction' => 'sut']);
        $this->actingAs($owner)->get(route('animals.index'))->assertSee(__("Bu amalni hozir bajarib bo'lmaydi: to'lov qilinmagani sababli faoliyat cheklangan."));
    }

    public function test_read_only_whitelist_and_staff_security_actions(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(-10, $owner);
        $animal = $this->animalIn($farm);
        $reminder = $animal->reminders()->create(['title' => 'Emlash', 'due_date' => '2026-11-10']);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $toSuspend = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $toRemove = $this->addMember($farm, FarmUser::ROLE_VIEWER);
        $suspended = $this->addMember($farm, FarmUser::ROLE_VIEWER, FarmUser::STATUS_SUSPENDED);
        $membership = fn (User $user) => FarmUser::where('farm_id', $farm->id)->where('user_id', $user->id)->first();

        // Eslatmani bajarildi deb belgilash ishlaydi
        $this->actingAs($worker)->post(route('reminders.complete', [$animal, $reminder]))->assertSessionHas('success');
        $this->assertNotNull($reminder->fresh()->bajarilgan_at);

        // Xodimni to'xtatish, chiqarish va vaqtinchalik parol — ochiq
        $this->actingAs($owner)->patch(route('staff.status', $membership($toSuspend)))->assertSessionHas('success');
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $membership($toSuspend)->status);
        $this->actingAs($owner)->delete(route('staff.destroy', $membership($toRemove)))->assertSessionHas('success');
        $this->assertNull($membership($toRemove));

        $created = $this->actingAs($owner)->post(route('staff.store'), ['name' => 'Yangi', 'email' => 'yangi@example.com', 'role' => 'worker']);
        $created->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->assertDatabaseMissing('users', ['email' => 'yangi@example.com']);

        $tempMember = FarmUser::create(['farm_id' => $farm->id, 'user_id' => User::factory()->create()->id, 'role' => 'worker', 'status' => 'active', 'invited_by' => $owner->id, 'must_change_password' => true]);
        $this->actingAs($owner)->post(route('staff.password', $tempMember))->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'amal' => FarmAuditLog::ACTION_STAFF_PASSWORD_RESET]);

        // Rol o'zgartirish va qayta faollashtirish — yopiq
        $this->actingAs($owner)->patch(route('staff.role', $membership($worker)), ['role' => 'manager'])->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->assertSame(FarmUser::ROLE_WORKER, $membership($worker)->role);
        $this->actingAs($owner)->patch(route('staff.status', $membership($suspended)))->assertSessionHasErrors('staff');
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $membership($suspended)->status);

        // Ferma almashtirish, til, parol va chiqish ishlaydi
        $other = $this->farmOf(User::factory()->create());
        $other->members()->attach($owner->id, ['role' => FarmUser::ROLE_WORKER, 'status' => FarmUser::STATUS_ACTIVE]);
        $this->actingAs($owner)->post(route('farm.switch'), ['farm_id' => $other->id])->assertRedirect(route('dashboard'));
        $this->assertSame($other->id, $owner->fresh()->current_farm_id);
        $this->actingAs($owner)->post(route('farm.switch'), ['farm_id' => $farm->id])->assertRedirect(route('dashboard'));

        $this->actingAs($owner)->get(route('lang.switch', 'ru'))->assertRedirect();
        $this->actingAs($owner)->from(route('profile.edit'))->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors()->assertSessionMissing(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->post(route('telegram.generate-code'))->assertOk();
        $this->actingAs($owner)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_ai_is_blocked_in_read_only_farm(): void
    {
        Http::fake();
        config(['services.openai.api_key' => 'test']);
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(-3, $owner);
        $animal = $this->animalIn($farm);

        $this->actingAs($owner)->post(route('chat.ask', $animal), ['question' => 'Salom?'])->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->post(route('photo-analysis.store', $animal), [])->assertSessionHas(EnsureFarmWritable::FLASH);
        $this->actingAs($owner)->get(route('chat.show', $animal))->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, \App\Models\AiUsageLog::count());
    }

    public function test_admin_panel_free_and_grace_farms_are_not_affected(): void
    {
        // Admin o'z fermasi faqat o'qishda bo'lsa ham admin panel ishlaydi
        $admin = $this->admin();
        $this->setPlan($this->farmOf($admin), 'basic', '2026-10-01');
        $target = $this->farmEndingIn(-20);

        $this->actingAs($admin)->post(route('admin.farms.payment', $target), [
            'plan_id' => Plan::where('kod', 'basic')->value('id'), 'oylar' => 1, 'summa' => 1, 'usul' => 'bank',
        ])->assertSessionHas('success');
        $this->assertSame(SubscriptionService::STATUS_ACTIVE, $this->farmStatus($target));
        $this->actingAs($admin)->patch(route('admin.users.block', $target->owner))->assertSessionHas('success');
        // Lekin adminning o'z fermasida yozish yopiq
        $this->actingAs($admin)->post(route('animals.store'), ['name' => 'Admin sigiri', 'direction' => 'sut'])->assertSessionHas(EnsureFarmWritable::FLASH);

        // Hech qachon to'lamagan bepul ferma
        $freeOwner = User::factory()->create();
        $this->actingAs($freeOwner)->post(route('animals.store'), ['name' => 'Bepul sigir', 'direction' => 'sut'])->assertSessionMissing(EnsureFarmWritable::FLASH);
        $this->assertTrue(Animal::where('name', 'Bepul sigir')->exists());

        // Imtiyozdagi ferma (T+2) — hammasi ishlaydi, pullik tarif limitlari saqlanadi
        $graceOwner = User::factory()->create();
        $grace = $this->farmEndingIn(-2, $graceOwner);
        $this->actingAs($graceOwner)->post(route('animals.store'), ['name' => 'Imtiyoz sigir', 'direction' => 'sut'])->assertSessionMissing(EnsureFarmWritable::FLASH);
        $this->assertTrue(Animal::where('name', 'Imtiyoz sigir')->exists());
        $this->assertSame('basic', $grace->fresh()->activePlan()->kod);
    }

    public function test_admin_pages_show_no_subscription_banner_and_admin_actions_work_for_restricted_admin(): void
    {
        $yellow = 'class="obn-banner obn-banner-sariq"';
        $red = 'class="obn-banner obn-banner-qizil"';
        $grey = 'class="obn-banner obn-banner-kulrang"';

        // Admin — cheklangan fermaning egasi
        $admin = $this->admin();
        $adminFarm = $this->setPlan($this->farmOf($admin), 'basic', '2026-10-01');
        $target = $this->farmEndingIn(-20);
        $this->assertSame(SubscriptionService::STATUS_READ_ONLY, $this->farmStatus($adminFarm));

        $adminPages = [
            route('admin.dashboard'),
            route('admin.payments.index'),
            route('admin.payments.index', ['filtr' => SubscriptionService::STATUS_READ_ONLY]),
            route('admin.farms.show', $target),
            route('admin.users.index'),
        ];

        // Oddiy sahifada qizil banner bor, admin sahifalarida yo'q
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee($red, false);
        foreach ($adminPages as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertDontSee($red, false)->assertDontSee($yellow, false)->assertDontSee($grey, false);
        }

        // Admin holat belgisi va filtri — "Cheklangan"
        $this->actingAs($admin)->get(route('admin.payments.index', ['filtr' => SubscriptionService::STATUS_READ_ONLY]))
            ->assertSee(__('Cheklangan'))->assertSee($target->name)->assertDontSee("Faqat o'qish");
        $this->actingAs($admin)->get(route('admin.farms.show', $target))->assertSee(__('Cheklangan'));

        // Admin amallari ishlaydi: to'lov, bonus, foydalanuvchini bloklash
        $this->actingAs($admin)->post(route('admin.farms.payment', $target), [
            'plan_id' => Plan::where('kod', 'basic')->value('id'), 'oylar' => 1, 'summa' => 1, 'usul' => 'tolov_tizimi',
        ])->assertSessionHas('success')->assertSessionMissing(EnsureFarmWritable::FLASH);
        $this->assertSame(SubscriptionService::STATUS_ACTIVE, $this->farmStatus($target));
        $other = $this->farmEndingIn(-10);
        $this->actingAs($admin)->post(route('admin.farms.bonus', $other), ['sabab' => 'Uzr', 'plan_id' => Plan::where('kod', 'basic')->value('id')])
            ->assertSessionHas('success')->assertSessionMissing(EnsureFarmWritable::FLASH);
        $this->assertSame(SubscriptionService::STATUS_ACTIVE, $this->farmStatus($other));
        $this->actingAs($admin)->patch(route('admin.users.block', $target->owner))->assertSessionHas('success');
        // Adminning o'z fermasi hali cheklangan
        $this->assertSame(SubscriptionService::STATUS_READ_ONLY, $this->farmStatus($adminFarm));

        // Imtiyoz (sariq) ham admin sahifalarida ko'rinmaydi
        $this->setPlan($adminFarm, 'basic', '2026-11-09');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee($yellow, false);
        foreach ($adminPages as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertDontSee($yellow, false);
        }

        // Kulrang: admin cheklangan fermada ishchi bo'lsa ham
        $restrictedOwner = User::factory()->create();
        $restricted = $this->farmEndingIn(-5, $restrictedOwner);
        $workerAdmin = $this->addMember($restricted, FarmUser::ROLE_WORKER);
        $workerAdmin->forceFill(['is_admin' => true])->save();
        $this->actingAs($workerAdmin)->get(route('dashboard'))->assertSee($grey, false);
        foreach ($adminPages as $url) {
            $this->actingAs($workerAdmin)->get($url)->assertOk()->assertDontSee($grey, false);
        }
    }

    public function test_expired_paid_farm_keeps_plan_limits_and_staff(): void
    {
        $admin = $this->admin();
        $farm = $this->farmEndingIn(-30);
        $first = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $second = $this->addMember($farm, FarmUser::ROLE_WORKER);

        $this->assertSame('basic', $farm->fresh()->activePlan()->kod);
        $this->assertSame(2, $farm->fresh()->staffLimit());

        // Tarif tahriri (Basic 2 -> 1) muddati o'tgan fermadagi xodimlarga tegmaydi
        $basic = Plan::where('kod', 'basic')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.plans.update', $basic), [
            'nomi' => $basic->nomi, 'narx_som' => $basic->narx_som, 'max_hayvon' => $basic->max_hayvon, 'max_staff' => 1,
            'oylik_chat_limit' => $basic->oylik_chat_limit, 'oylik_vision_limit' => $basic->oylik_vision_limit,
            'chat_model' => $basic->aiModel('chat'), 'vision_model' => $basic->aiModel('vision'), 'faol' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(2, $farm->fresh()->activeStaffCount());
        $this->assertSame(FarmUser::STATUS_ACTIVE, FarmUser::where('user_id', $second->id)->value('status'));
        $this->assertSame(FarmUser::STATUS_ACTIVE, FarmUser::where('user_id', $first->id)->value('status'));
    }

    // ---------------------------------------------------------------- banner va menyu

    public function test_banner_by_role_and_status(): void
    {
        $yellow = 'class="obn-banner obn-banner-sariq"';
        $red = 'class="obn-banner obn-banner-qizil"';
        $grey = 'class="obn-banner obn-banner-kulrang"';

        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(-1, $owner);
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $viewer = $this->addMember($farm, FarmUser::ROLE_VIEWER);

        // Imtiyoz: ega va menejer — sariq, ishchi/kuzatuvchi — hech narsa
        foreach ([$owner, $manager] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
                ->assertSee($yellow, false)
                ->assertSee(__("Obunangiz muddati tugadi. Iltimos, to'lovni amalga oshiring. Agar :days kun ichida to'lov qilinmasa, saytdagi faoliyatingizni to'lov qilinguncha cheklashga majbur bo'lamiz.", ['days' => 2]));
        }
        foreach ([$worker, $viewer] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee($yellow, false)->assertDontSee($grey, false)->assertDontSee($red, false);
        }

        // Faqat o'qish: ega va menejer — qizil, ishchi va kuzatuvchi — kulrang
        $this->setPlan($farm, 'basic', '2026-11-07');
        foreach ([$owner, $manager] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertSee($red, false)->assertDontSee($grey, false)
                ->assertSee(__("To'lov qilinmagani sababli saytdagi faoliyatingiz cheklandi. Ma'lumotlaringizni ko'rishingiz mumkin, lekin yangi ma'lumot kiritib bo'lmaydi. To'lov qilinishi bilan hammasi darhol tiklanadi."));
        }
        foreach ([$worker, $viewer] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertSee($grey, false)->assertDontSee($red, false)
                ->assertSee(__("Fermada yangi ma'lumot kiritish vaqtincha cheklangan. Batafsil ma'lumot uchun ferma egasiga murojaat qiling."));
        }

        // Faol, bepul, muddatsiz — banner yo'q
        foreach (['2026-11-10' => 'basic', '' => 'free', null => 'pro'] as $end => $kod) {
            $this->setPlan($farm, $kod, $end === '' ? null : $end);
            foreach ([$owner, $worker] as $user) {
                $this->actingAs($user)->get(route('dashboard'))->assertDontSee($yellow, false)->assertDontSee($red, false)->assertDontSee($grey, false);
            }
        }
    }

    public function test_farm_menu_shows_plan_and_payment_history_to_owner_only(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(20, $owner);
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        app(SubscriptionService::class)->extend($farm, Plan::where('kod', 'pro')->firstOrFail(), 1, FarmPayment::TYPE_PAYMENT, 123456, 'bank', 'P-77');

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertSee('Pro')->assertSee('31.12.2026')->assertSee(route('farm-payments.index'));
        $this->actingAs($manager)->get(route('dashboard'))->assertOk()
            ->assertSee('31.12.2026')->assertDontSee(route('farm-payments.index'));
        $this->actingAs($worker)->get(route('dashboard'))->assertOk()
            ->assertDontSee('31.12.2026')->assertDontSee(route('farm-payments.index'));

        $this->actingAs($owner)->get(route('farm-payments.index'))->assertOk()->assertSee('P-77')->assertSee('123 456');
        $this->actingAs($manager)->get(route('farm-payments.index'))->assertForbidden();
        $this->actingAs($worker)->get(route('farm-payments.index'))->assertForbidden();
    }

    // ---------------------------------------------------------------- obuna bot xabarlari

    public function test_subscription_bot_messages_on_right_days_once_to_owner_and_manager(): void
    {
        $this->fakeTelegram();
        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '111', 'locale' => 'ru'])->save();
        $farm = $this->setPlan($this->farmOf($owner), 'basic', '2026-11-13');
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $manager->forceFill(['telegram_chat_id' => '222'])->save();
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $worker->forceFill(['telegram_chat_id' => '333'])->save();
        $suspendedManager = $this->addMember($farm, FarmUser::ROLE_MANAGER, FarmUser::STATUS_SUSPENDED);
        $suspendedManager->forceFill(['telegram_chat_id' => '444'])->save();

        $notifier = app(SubscriptionNotifier::class);
        $sentTo = fn (string $chatId, string $needle) => Http::recorded(fn ($request) => ($request['chat_id'] ?? null) === $chatId && str_contains($request['text'] ?? '', $needle))->count();

        // T-4: hali erta
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'Asia/Tashkent'));
        $notifier->run();
        Http::assertNothingSent();

        // T-3: ega (ru) va menejer (3 tilda); ishchi va to'xtatilgan menejer olmaydi
        $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $before = __('Obunangiz :days kundan keyin (:date) tugaydi.', ['days' => 3, 'date' => '13.11.2026'], 'ru');
        $this->assertSame(1, $sentTo('111', $before));
        $this->assertSame(0, $sentTo('111', 'Obunangiz 3 kundan keyin'));
        $this->assertSame(1, $sentTo('222', 'Obunangiz 3 kundan keyin (13.11.2026) tugaydi.'));
        $this->assertSame(1, $sentTo('222', $before));
        $this->assertSame(0, $sentTo('333', ''));
        $this->assertSame(0, $sentTo('444', ''));

        // Takrorlanmaydi: o'sha kuni va keyingi kunlar (T-2..T) ham
        $notifier->run();
        $this->travelTo(CarbonImmutable::parse('2026-11-12 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $this->assertSame(1, $sentTo('111', ''));
        $this->assertSame(1, $sentTo('222', ''));

        // T+1: tugadi
        $this->travelTo(CarbonImmutable::parse('2026-11-14 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $expired = ":ferma — obunangiz muddati tugadi. Iltimos, :days kun ichida to'lovni amalga oshiring, aks holda saytdagi faoliyatingizni to'lov qilinguncha cheklashga majbur bo'lamiz.";
        $this->assertSame(1, $sentTo('222', __($expired, ['ferma' => $farm->name, 'days' => 2])));
        $this->assertSame(1, $sentTo('111', __($expired, ['ferma' => $farm->name, 'days' => 2], 'ru')));
        $this->travelTo(CarbonImmutable::parse('2026-11-15 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $this->assertSame(2, $sentTo('222', ''));

        // T+3: faqat o'qish; jurnalga tizim nomidan bir marta yoziladi
        $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $restricted = ":ferma — to'lov qilinmagani sababli saytdagi faoliyatingiz cheklandi. Ma'lumotlaringiz saqlanadi va ko'rinib turadi. To'lov qilinishi bilan hammasi darhol tiklanadi.";
        $this->assertSame(1, $sentTo('222', __($restricted, ['ferma' => $farm->name])));
        $this->assertSame(1, $sentTo('111', __($restricted, ['ferma' => $farm->name], 'ru')));
        // Ferma nomi matnning o'zida: alohida sarlavha bilan takrorlanmaydi (ru — bitta tilda, butun matn)
        $this->assertSame(1, Http::recorded(fn ($request) => ($request['chat_id'] ?? null) === '111'
            && ($request['text'] ?? '') === "\u{1F3E0} " . __($restricted, ['ferma' => $farm->name], 'ru'))->count());
        $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'amal' => FarmAuditLog::ACTION_READ_ONLY_STARTED, 'user_id' => null]);

        $this->travelTo(CarbonImmutable::parse('2026-11-20 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $this->assertSame(3, $sentTo('222', ''));
        $this->assertSame(1, FarmAuditLog::where('farm_id', $farm->id)->where('amal', FarmAuditLog::ACTION_READ_ONLY_STARTED)->count());

        // T+10 dan keyin xabar yo'q
        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00', 'Asia/Tashkent'));
        $notifier->run();
        $this->assertSame(3, $sentTo('222', ''));

        $this->assertSame(3, SubscriptionNotification::where('user_id', $manager->id)->where('holat', SubscriptionNotification::STATUS_SENT)->count());
        $this->assertSame(0, SubscriptionNotification::where('user_id', $worker->id)->count());

        // Bepul ferma xabar olmaydi
        $freeOwner = User::factory()->create();
        $freeOwner->forceFill(['telegram_chat_id' => '555'])->save();
        $notifier->run();
        $this->assertSame(0, $sentTo('555', ''));
    }

    public function test_missed_day_is_delivered_later_and_no_telegram_is_recorded(): void
    {
        $this->fakeTelegram();
        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '111'])->save();
        $farm = $this->farmEndingIn(0, $owner);
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);

        // T-3..T-1 o'tkazib yuborilgan: T kuni baribir yetkaziladi
        app(SubscriptionNotifier::class)->run();
        Http::assertSent(fn ($request) => $request['chat_id'] === '111' && str_contains($request['text'], 'Obunangiz bugun (10.11.2026) tugaydi.'));

        $row = SubscriptionNotification::where('user_id', $manager->id)->firstOrFail();
        $this->assertSame(SubscriptionNotification::STATUS_NO_TELEGRAM, $row->holat);
        $this->assertSame(SubscriptionNotification::TYPE_BEFORE, $row->tur);
        $this->assertSame('2026-11-10', substr($row->tugash_sanasi, 0, 10));

        // Telegram keyin bog'lansa ham shu xabar qayta yuborilmaydi
        $manager->forceFill(['telegram_chat_id' => '222'])->save();
        app(SubscriptionNotifier::class)->run();
        Http::assertNotSent(fn ($request) => ($request['chat_id'] ?? null) === '222');
        $this->assertSame(1, SubscriptionNotification::where('user_id', $manager->id)->count());
    }

    public function test_failed_message_is_retried_up_to_three_times(): void
    {
        config(['services.telegram.bot_token' => 'token']);
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);
        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '111'])->save();
        $this->farmEndingIn(2, $owner);
        $notifier = app(SubscriptionNotifier::class);

        $notifier->run();
        $row = SubscriptionNotification::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame(SubscriptionNotification::STATUS_FAILED, $row->holat);
        $this->assertSame(1, $row->urinishlar);
        $this->assertStringContainsString('bot was blocked', $row->xato);

        $notifier->run();
        $notifier->run();
        $notifier->run();
        $notifier->run();
        $this->assertSame(3, $row->fresh()->urinishlar);
        Http::assertSentCount(3);
    }

    // ---------------------------------------------------------------- eslatmalar

    public function test_reminders_are_sent_in_read_only_farm_only_recent_and_not_done(): void
    {
        $this->fakeTelegram();
        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '111', 'locale' => 'uz'])->save();
        $farm = $this->farmEndingIn(-30, $owner);
        $this->assertSame(SubscriptionService::STATUS_READ_ONLY, $this->farmStatus($farm));
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $manager->forceFill(['telegram_chat_id' => '222'])->save();
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $worker->forceFill(['telegram_chat_id' => '333'])->save();
        $animal = $this->animalIn($farm, ['name' => 'Malika']);

        $today = $animal->reminders()->create(['title' => 'Emlash', 'due_date' => '2026-11-10']);
        $twoDaysAgo = $animal->reminders()->create(['title' => 'Davolash', 'due_date' => '2026-11-08']);
        $tooOld = $animal->reminders()->create(['title' => 'Eski', 'due_date' => '2026-11-07']);
        $future = $animal->reminders()->create(['title' => 'Kelajak', 'due_date' => '2026-11-11']);
        $done = $animal->reminders()->create(['title' => 'Bajarilgan', 'due_date' => '2026-11-10']);
        $done->markDone();

        $result = app(DailySubscriptionRunner::class)->runIfDue();
        $this->assertSame(2, $result[DailySubscriptionRunner::RUN_REMINDERS]['eslatmalar']);

        $this->assertNotNull($today->fresh()->sent_at);
        $this->assertNotNull($twoDaysAgo->fresh()->sent_at);
        // Eskisi yuborilmaydi va belgilanmaydi
        $this->assertNull($tooOld->fresh()->sent_at);
        $this->assertNull($future->fresh()->sent_at);
        $this->assertNull($done->fresh()->sent_at);

        Http::assertSent(fn ($request) => $request['chat_id'] === '111'
            && $request['text'] === "\u{1F514} Eslatma: \"Malika\" — Emlash. Sana: 10.11.2026"
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'reminder:done:' . $today->id);
        Http::assertSent(fn ($request) => $request['chat_id'] === '222' && str_contains($request['text'], 'Davolash'));
        Http::assertNotSent(fn ($request) => ($request['chat_id'] ?? null) === '333');
        Http::assertNotSent(fn ($request) => str_contains($request['text'] ?? '', 'Eski') || str_contains($request['text'] ?? '', 'Bajarilgan'));
        Http::assertSentCount(4);
    }

    public function test_web_reminder_complete_permissions(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $animal = $this->animalIn($farm);
        $reminder = $animal->reminders()->create(['title' => 'Emlash', 'due_date' => '2026-11-12']);
        $viewer = $this->addMember($farm, FarmUser::ROLE_VIEWER);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $stranger = User::factory()->create();

        $this->actingAs($viewer)->post(route('reminders.complete', [$animal, $reminder]))->assertForbidden();
        $this->actingAs($stranger)->post(route('reminders.complete', [$animal, $reminder]))->assertNotFound();
        $this->assertNull($reminder->fresh()->bajarilgan_at);

        $this->actingAs($worker)->get(route('animals.show', $animal))->assertSee(route('reminders.complete', [$animal, $reminder]));
        $this->actingAs($viewer)->get(route('animals.show', $animal))->assertDontSee(route('reminders.complete', [$animal, $reminder]));

        $this->actingAs($worker)->post(route('reminders.complete', [$animal, $reminder]))->assertSessionHas('success');
        $this->actingAs($worker)->post(route('reminders.complete', [$animal, $reminder]))->assertSessionHas('success');
        $this->assertNotNull($reminder->fresh()->bajarilgan_at);
        $this->assertSame(1, FarmAuditLog::where('amal', FarmAuditLog::ACTION_REMINDER_COMPLETED)->where('user_id', $worker->id)->count());

        $this->actingAs($worker)->get(route('animals.show', $animal))->assertDontSee(route('reminders.complete', [$animal, $reminder]))->assertSee(__('bajarildi'));
    }

    public function test_bot_reminder_done_button(): void
    {
        $this->fakeTelegram();
        $owner = User::factory()->create();
        $farm = $this->farmEndingIn(-10, $owner);
        $animal = $this->animalIn($farm);
        $reminder = $animal->reminders()->create(['title' => 'Emlash', 'due_date' => '2026-11-10']);
        $viewer = $this->addMember($farm, FarmUser::ROLE_VIEWER);
        $viewer->forceFill(['telegram_chat_id' => '700'])->save();
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $worker->forceFill(['telegram_chat_id' => '800', 'locale' => 'ru'])->save();
        $stranger = User::factory()->create();
        $stranger->forceFill(['telegram_chat_id' => '900'])->save();

        $press = fn (string $chatId) => $this->webhook(['callback_query' => [
            'id' => 'cb' . $chatId, 'from' => ['id' => (int) $chatId], 'data' => 'reminder:done:' . $reminder->id,
            'message' => ['chat' => ['id' => (int) $chatId], 'message_id' => 5],
        ]]);

        // Kuzatuvchi va boshqa ferma foydalanuvchisi — huquq yo'q
        $press('700');
        $press('900');
        $this->assertNull($reminder->fresh()->bajarilgan_at);

        // Ishchi (faqat o'qish rejimidagi fermada ham)
        $press('800');
        $this->assertNotNull($reminder->fresh()->bajarilgan_at);
        Http::assertSent(fn ($request) => ($request['callback_query_id'] ?? null) === 'cb800'
            && $request['text'] === __('Eslatma bajarildi deb belgilandi.', [], 'ru'));
        $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'amal' => FarmAuditLog::ACTION_REMINDER_COMPLETED, 'user_id' => $worker->id]);

        // Ikkinchi bosish zararsiz
        $press('800');
        Http::assertSent(fn ($request) => ($request['callback_query_id'] ?? null) === 'cb800'
            && $request['text'] === __('Bu eslatma allaqachon bajarilgan.', [], 'ru'));
        $this->assertSame(1, FarmAuditLog::where('amal', FarmAuditLog::ACTION_REMINDER_COMPLETED)->count());
    }

    // ---------------------------------------------------------------- bot: faqat o'qish

    public function test_bot_write_is_refused_in_read_only_but_viewing_and_security_work(): void
    {
        $this->fakeTelegram();
        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '111'])->save();
        $farm = $this->farmEndingIn(-3, $owner);
        $animal = $this->animalIn($farm, ['name' => 'Bahor', 'passport_number' => 'AA1']);
        $pending = BankAnimalWatcher::create(['telegram_chat_id' => '501', 'animal_id' => $animal->id, 'status' => BankAnimalWatcher::STATUS_PENDING]);
        $toReject = BankAnimalWatcher::create(['telegram_chat_id' => '502', 'animal_id' => $animal->id, 'status' => BankAnimalWatcher::STATUS_PENDING]);
        $approved = BankAnimalWatcher::create(['telegram_chat_id' => '503', 'animal_id' => $animal->id, 'status' => BankAnimalWatcher::STATUS_APPROVED]);

        $callback = fn (string $data) => $this->webhook(['callback_query' => [
            'id' => 'cb', 'from' => ['id' => 111], 'data' => $data, 'message' => ['chat' => ['id' => 111], 'message_id' => 9],
        ]]);

        // Yangi kuzatuvchiga ruxsat — rad etiladi
        $callback('watch:approve:' . $pending->id);
        $this->assertSame(BankAnimalWatcher::STATUS_PENDING, $pending->fresh()->status);
        Http::assertSent(fn ($request) => ($request['chat_id'] ?? null) === '111'
            && str_contains($request['text'] ?? '', "Bu amalni hozir bajarib bo'lmaydi: to'lov qilinmagani sababli faoliyat cheklangan."));
        Http::assertNotSent(fn ($request) => ($request['chat_id'] ?? null) === '501');

        // Rad etish va bekor qilish — ochiq
        $callback('watch:reject:' . $toReject->id);
        $this->assertSame(BankAnimalWatcher::STATUS_REJECTED, $toReject->fresh()->status);
        $callback('watch:revoke:' . $approved->id);
        $this->assertSame(BankAnimalWatcher::STATUS_REVOKED, $approved->fresh()->status);

        // Ko'rish buyruqlari ishlaydi
        $this->webhook(['message' => ['text' => '/hayvonlarim', 'chat' => ['id' => 111]]]);
        Http::assertSent(fn ($request) => ($request['chat_id'] ?? null) === '111' && str_contains($request['text'] ?? '', 'Bahor'));

        // To'lovdan keyin ruxsat berish ishlaydi
        $this->setPlan($farm, 'basic', '2026-12-31');
        $callback('watch:approve:' . $pending->id);
        $this->assertSame(BankAnimalWatcher::STATUS_APPROVED, $pending->fresh()->status);
    }

    // ---------------------------------------------------------------- kunlik ish

    public function test_runner_runs_once_a_day_after_eight_and_only_one_concurrent_call(): void
    {
        $runner = app(DailySubscriptionRunner::class);
        $row = fn () => DB::table('system_runs')->where('nom', DailySubscriptionRunner::RUN_SUBSCRIPTIONS)->first();

        // 07:59 Toshkent — ishlamaydi
        $this->travelTo(CarbonImmutable::parse('2026-11-10 07:59:59', 'Asia/Tashkent'));
        $this->assertSame([DailySubscriptionRunner::RUN_SUBSCRIPTIONS => null, DailySubscriptionRunner::RUN_REMINDERS => null], $runner->runIfDue());
        $this->assertNull($row()->last_run_date);

        // 08:00 — ishlaydi
        $this->travelTo(CarbonImmutable::parse('2026-11-10 08:00:00', 'Asia/Tashkent'));
        $first = $runner->runIfDue();
        $this->assertIsArray($first[DailySubscriptionRunner::RUN_SUBSCRIPTIONS]);
        $this->assertIsArray($first[DailySubscriptionRunner::RUN_REMINDERS]);
        $this->assertSame('2026-11-10', $row()->last_run_date);
        $this->assertNotNull($row()->last_finished_at);
        $this->assertSame($first[DailySubscriptionRunner::RUN_SUBSCRIPTIONS], json_decode($row()->last_result, true));

        // Ikkinchi (parallel) chaqiruv — atomik UPDATE 0 qator, ishlamaydi
        $this->assertSame([DailySubscriptionRunner::RUN_SUBSCRIPTIONS => null, DailySubscriptionRunner::RUN_REMINDERS => null], $runner->runIfDue());
        $this->travelTo(CarbonImmutable::parse('2026-11-10 23:00:00', 'Asia/Tashkent'));
        $this->assertNull($runner->runIfDue()[DailySubscriptionRunner::RUN_SUBSCRIPTIONS]);

        // Ertasi kuni 08:00 dan oldin — yo'q, keyin — ha
        $this->travelTo(CarbonImmutable::parse('2026-11-11 07:00:00', 'Asia/Tashkent'));
        $this->assertNull($runner->runIfDue()[DailySubscriptionRunner::RUN_SUBSCRIPTIONS]);
        $this->travelTo(CarbonImmutable::parse('2026-11-11 08:30:00', 'Asia/Tashkent'));
        $this->assertIsArray($runner->runIfDue()[DailySubscriptionRunner::RUN_SUBSCRIPTIONS]);
        $this->assertSame('2026-11-11', $row()->last_run_date);
    }

    public function test_runner_is_triggered_after_web_request(): void
    {
        config(['services.obuna.auto_run' => true]);
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();

        $this->assertSame('2026-11-10', DB::table('system_runs')->where('nom', 'obuna')->value('last_run_date'));
        $this->assertSame('2026-11-10', DB::table('system_runs')->where('nom', 'eslatmalar')->value('last_run_date'));

        // Admin bosh sahifasida natija ko'rinadi
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()
            ->assertSee(__('Kunlik tekshiruv'))->assertSee('10.11.2026 09:00');
    }

    public function test_cron_url_requires_valid_token(): void
    {
        config(['services.obuna.cron_token' => null]);
        $this->get('/cron/obuna/anything')->assertNotFound();
        $this->get('/cron/obuna/')->assertNotFound();

        config(['services.obuna.cron_token' => 'maxfiy-token-123']);
        $this->get('/cron/obuna/notogri')->assertNotFound();
        $this->assertNull(DB::table('system_runs')->where('nom', 'obuna')->value('last_run_date'));

        $this->get('/cron/obuna/maxfiy-token-123')->assertOk()->assertJson(['ok' => true])->assertJsonStructure(['natija' => ['obuna', 'eslatmalar']]);
        $this->assertSame('2026-11-10', DB::table('system_runs')->where('nom', 'obuna')->value('last_run_date'));

        // Shu kuni qayta — ishlamaydi
        $this->get('/cron/obuna/maxfiy-token-123')->assertOk()->assertJson(['natija' => ['obuna' => null, 'eslatmalar' => null]]);
    }

    // ---------------------------------------------------------------- til

    public function test_user_locale_is_saved_on_switch_and_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('lang.switch', 'uz_cyrl'));
        $this->assertSame('uz_cyrl', $user->fresh()->locale);

        // Noto'g'ri qiymat saqlanmaydi
        $this->actingAs($user)->get(route('lang.switch', 'en'));
        $this->assertSame('uz_cyrl', $user->fresh()->locale);

        $this->post(route('logout'));
        $this->assertGuest();

        $this->withSession(['locale' => 'ru'])->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertSame('ru', $user->fresh()->locale);
    }
}
