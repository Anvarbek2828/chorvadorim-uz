<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'admin:make {email : Admin qilinadigan mavjud foydalanuvchining emaili}';

    protected $description = "Mavjud foydalanuvchini super admin qiladi (parol o'zgarmaydi)";

    public function handle(AdminAuditService $audit)
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            $this->error(__('Bu email bilan foydalanuvchi topilmadi: :email', ['email' => $email]));

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info(__(':email allaqachon admin.', ['email' => $user->email]));
        } else {
            $user->is_admin = true;
            $user->save();

            $audit->log(AdminAuditLog::ACTION_USER_MADE_ADMIN, 'user', $user->id, ['is_admin' => false], ['is_admin' => true], null, 'cli');

            $this->info(__(':email endi admin. Admin panel manzili: /admin', ['email' => $user->email]));
        }

        if ($user->is_blocked) {
            $this->warn(__("Diqqat: bu foydalanuvchi bloklangan, blokdan chiqarilmaguncha admin panelga kira olmaydi."));
        }

        return self::SUCCESS;
    }
}
