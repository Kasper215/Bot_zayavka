<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {name} {email} {password} {--role=manager}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать нового пользователя для админ-панели';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $role = $this->option('role');

        if (!in_array($role, ['admin', 'manager'])) {
            $this->error('Роль должна быть "admin" или "manager"');
            return 1;
        }

        // Проверяем, существует ли пользователь
        if (User::where('email', $email)->exists()) {
            $this->error("Пользователь с email {$email} уже существует");
            return 1;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        $this->info("✅ Пользователь успешно создан:");
        $this->line("   Имя: {$user->name}");
        $this->line("   Email: {$user->email}");
        $this->line("   Роль: {$user->role}");
        $this->line("");
        $this->info("Теперь можно войти в админ-панель по адресу: /admin/leads");

        return 0;
    }
}
