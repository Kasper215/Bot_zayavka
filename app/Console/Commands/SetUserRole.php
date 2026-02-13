<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Установить роль пользователя (admin или manager)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        if (!in_array($role, ['admin', 'manager'])) {
            $this->error('Роль должна быть "admin" или "manager"');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email {$email} не найден");
            return 1;
        }

        $user->role = $role;
        $user->save();

        $this->info("Роль пользователя {$user->name} ({$email}) успешно изменена на: {$role}");
        return 0;
    }
}
