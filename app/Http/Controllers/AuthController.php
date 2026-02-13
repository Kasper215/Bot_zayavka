<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller {
    public function redirectToYandex() {
        return Socialite::driver('yandex')->redirect();
    }

    public function handleYandexCallback() {
        $user = Socialite::driver('yandex')->user();
        // $user->token содержит OAuth токен
        // Сохраните токен в БД для дальнейшей работы с API Диска
    }
}

