<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * Название команды.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Создание резервной копии базы данных';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');

        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "backup_{$database}_{$date}.sql";

        // путь для сохранения
        $path = storage_path("app/backups/{$fileName}");

        // команда mysqldump
        $command = "mysqldump --user={$username} --password={$password} --host={$host} {$database} > {$path}";

        $this->info("Выполняется бэкап базы данных...");
        $result = null;
        system($command, $result);

        if ($result === 0) {
            $this->info("Бэкап успешно создан: {$path}");
        } else {
            $this->error("Ошибка при создании бэкапа");
        }
    }
}
