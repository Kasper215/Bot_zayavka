<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{


    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {


        DB::table('users')->insert([
            'name' => "СуперАдмин",
            'email' => 'test@example.com',
            'fio_from_telegram' => "Test Test",
            'telegram_chat_id' => env("TELEGRAM_ADMIN_CHANNEL"),
            'role' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'), // Пароль secret
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);


    }
}
