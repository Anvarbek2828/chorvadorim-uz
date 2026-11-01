<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\User;
use App\Services\AiUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Xodimlar A: egalik farm_id bo'yicha. Boshqa fermaning yozuviga murojaat — 404.
 */
class FarmIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function farmOf(User $user): Farm
    {
        return $user->ownedFarms()->firstOrFail();
    }

    private function animalFor(User $user, array $attributes = []): Animal
    {
        return Animal::create($attributes + [
            'farm_id' => $this->farmOf($user)->id,
            'user_id' => $user->id,
            'name' => 'Hayvon ' . $user->id,
            'direction' => 'sut',
        ]);
    }

    private function addMember(Farm $farm, string $role, string $status = FarmUser::STATUS_ACTIVE): User
    {
        $member = User::factory()->create();
        // Factory har bir foydalanuvchiga o'z fermasini yaratadi; xodim faqat shu fermaga a'zo bo'lishi uchun olib tashlanadi
        $member->ownedFarms()->delete();
        $farm->members()->attach($member->id, ['role' => $role, 'status' => $status]);
        $member->forceFill(['current_farm_id' => $farm->id])->save();

        return $member;
    }

    public function test_registration_creates_farm_and_owner_membership(): void
    {
        $this->post('/register', [
            'name' => 'Yangi Fermer',
            'email' => 'fermer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'fermer@example.com')->firstOrFail();
        $farm = Farm::where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('Yangi Fermer', $farm->name);
        $this->assertSame($farm->id, $user->current_farm_id);
        $this->assertSame(Plan::free()->id, $farm->plan_id);
        $this->assertDatabaseHas('farm_user', [
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'role' => FarmUser::ROLE_OWNER,
            'status' => FarmUser::STATUS_ACTIVE,
        ]);
        $this->assertSame(1, Farm::where('owner_id', $user->id)->count());
    }

    public function test_owner_sees_and_creates_animals_in_own_farm(): void
    {
        $owner = User::factory()->create();
        $animal = $this->animalFor($owner);

        $this->actingAs($owner)->get(route('animals.show', $animal))->assertOk();

        $this->actingAs($owner)->post(route('animals.store'), [
            'name' => 'Yangi buzoq',
            'direction' => 'gosht',
        ])->assertRedirect(route('animals.index'));

        $created = Animal::where('name', 'Yangi buzoq')->firstOrFail();
        $this->assertSame($this->farmOf($owner)->id, (int) $created->farm_id);
        $this->assertSame($owner->id, (int) $created->user_id);
    }

    public function test_other_farm_animal_returns_404(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $animalB = $this->animalFor($userB, ['name' => 'B ning sigiri']);

        $this->actingAs($userA);

        $this->get(route('animals.index'))->assertOk()->assertDontSee('B ning sigiri');
        $this->get(route('animals.show', $animalB))->assertNotFound();
        $this->get(route('animals.edit', $animalB))->assertNotFound();
        $this->put(route('animals.update', $animalB), ['name' => 'Buzildi', 'direction' => 'sut'])->assertNotFound();
        $this->delete(route('animals.destroy', $animalB))->assertNotFound();
        $this->get(route('chat.show', $animalB))->assertNotFound();
        $this->get(route('photo-analysis.index', $animalB))->assertNotFound();
        $this->post(route('expenses.store', $animalB), ['type' => 'yem', 'amount' => 10, 'expense_date' => '2026-11-01'])->assertNotFound();

        $animalB->refresh();
        $this->assertSame('B ning sigiri', $animalB->name);
        $this->assertFalse($animalB->trashed());

        // Arxivdagi hayvon: tiklash va butunlay o'chirish ham 404
        $animalB->delete();
        $this->get(route('animals.archive'))->assertOk()->assertDontSee('B ning sigiri');
        $this->post(route('animals.restore', $animalB->id))->assertNotFound();
        $this->delete(route('animals.forceDelete', $animalB->id))->assertNotFound();
        $this->assertNotNull(Animal::withTrashed()->find($animalB->id));
    }

    public function test_other_farm_reminder_returns_404(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $animalA = $this->animalFor($userA);
        $animalB = $this->animalFor($userB);
        $reminderB = $animalB->reminders()->create(['title' => 'Emlash', 'due_date' => '2026-11-10']);

        $this->actingAs($userA);

        $this->post(route('reminders.store', $animalB), ['title' => 'Begona', 'due_date' => '2026-11-10'])->assertNotFound();
        $this->delete(route('reminders.destroy', [$animalB, $reminderB]))->assertNotFound();
        // O'z hayvoni orqali begona eslatmani o'chirishga urinish
        $this->delete(route('reminders.destroy', [$animalA, $reminderB]))->assertNotFound();

        $this->assertNotNull(Reminder::find($reminderB->id));
        $this->assertSame(0, $animalB->reminders()->where('title', 'Begona')->count());
    }

    public function test_ai_limit_is_counted_per_farm(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $otherFarm = $this->farmOf(User::factory()->create());

        $usage = app(AiUsageService::class);
        $dailyLimit = $usage->dailyLimit($farm->activePlan()->monthlyLimit(AiUsageLog::TYPE_CHAT));

        // Boshqa fermaning ishlatishi bu fermaga ta'sir qilmaydi
        for ($i = 0; $i < $dailyLimit; $i++) {
            AiUsageLog::create(['farm_id' => $otherFarm->id, 'user_id' => $otherFarm->owner_id, 'turi' => AiUsageLog::TYPE_CHAT, 'model' => 'gpt-4o-mini']);
        }

        $this->assertNull($usage->denyReason($farm, AiUsageLog::TYPE_CHAT));

        // Fermaning boshqa a'zosi (menejer) ishlatgani ham shu ferma limitidan ketadi
        for ($i = 0; $i < $dailyLimit; $i++) {
            AiUsageLog::create(['farm_id' => $farm->id, 'user_id' => $manager->id, 'turi' => AiUsageLog::TYPE_CHAT, 'model' => 'gpt-4o-mini']);
        }

        $this->assertSame($dailyLimit, $usage->usageCount($farm, AiUsageLog::TYPE_CHAT, $usage->dayStart()));
        $this->assertNotNull($usage->denyReason($farm, AiUsageLog::TYPE_CHAT));

        // Ega so'rasa ham AI chaqirilmaydi
        Http::fake();
        $animal = $this->animalFor($owner);

        $this->actingAs($owner)
            ->from(route('chat.show', $animal))
            ->post(route('chat.ask', $animal), ['question' => 'Yem qancha berish kerak?'])
            ->assertRedirect(route('chat.show', $animal))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_animal_limit_uses_farm_plan(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $limit = $farm->activePlan()->max_hayvon;

        for ($i = 0; $i < $limit; $i++) {
            $this->animalFor($owner, ['name' => 'H' . $i]);
        }

        $this->assertNotNull(app(AiUsageService::class)->animalLimitMessage($farm));

        // Tarif fermada oshirilsa, limit ham oshadi
        $farm->plan_id = Plan::where('kod', 'basic')->value('id');
        $farm->save();

        $this->assertNull(app(AiUsageService::class)->animalLimitMessage($farm->fresh()));
    }

    public function test_member_without_active_membership_gets_403(): void
    {
        $owner = User::factory()->create();
        $suspended = $this->addMember($this->farmOf($owner), FarmUser::ROLE_WORKER, FarmUser::STATUS_SUSPENDED);

        $this->actingAs($suspended)->get(route('animals.index'))->assertForbidden();
        // Shaxsiy sahifalar ishlayveradi
        $this->actingAs($suspended)->get('/profile')->assertOk();
    }

    public function test_role_permissions_are_enforced(): void
    {
        $owner = User::factory()->create();
        $animal = $this->animalFor($owner);
        $viewer = $this->addMember($this->farmOf($owner), FarmUser::ROLE_VIEWER);

        $this->assertTrue($owner->canInFarm('animals.delete'));
        $this->assertFalse($viewer->canInFarm('animals.delete'));

        $this->actingAs($viewer)->get(route('animals.show', $animal))->assertOk();
        $this->actingAs($viewer)->delete(route('animals.destroy', $animal))->assertForbidden();
        $this->assertFalse($animal->fresh()->trashed());
    }

    public function test_admin_plan_change_updates_farm_and_user(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $user = User::factory()->create();
        $pro = Plan::where('kod', 'pro')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.users.plan', $user), ['plan_id' => $pro->id, 'plan_tugash_sanasi' => '2027-01-31'])
            ->assertSessionHas('success');

        $farm = $this->farmOf($user);
        $this->assertSame($pro->id, (int) $farm->plan_id);
        $this->assertSame('2027-01-31', $farm->plan_tugash_sanasi->toDateString());
        $this->assertSame($pro->id, (int) $user->fresh()->plan_id);
        $this->assertDatabaseHas('admin_audit_logs', ['farm_id' => $farm->id, 'obyekt_id' => $user->id]);
    }

    public function test_telegram_bot_lists_only_current_farm_animals(): void
    {
        config(['services.telegram.webhook_secret' => 'secret', 'services.telegram.bot_token' => 'token']);
        Http::fake();

        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '555'])->save();
        $this->animalFor($owner, ['name' => 'Mening sigirim']);
        $this->animalFor(User::factory()->create(), ['name' => 'Begona sigir']);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')
            ->postJson('/telegram/webhook', ['message' => ['text' => '/hayvonlarim', 'chat' => ['id' => 555]]])
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'Mening sigirim')
            && !str_contains($request['text'] ?? '', 'Begona sigir'));
    }
}
