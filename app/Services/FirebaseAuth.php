<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Facades\Storage;

class FirebaseAuth
{
    public static function getAccessToken()
    {
        $serviceAccountPath = Storage::disk('local')->path(env('FIREBASE_CREDENTIALS'));

        if (!file_exists($serviceAccountPath)) {
            throw new \Exception('Файл сервисного аккаунта не найден.');
        }

        // Загружаем ключи сервисного аккаунта
        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountPath);

        // Получаем токен доступа
        $authToken = $credentials->fetchAuthToken(HttpHandlerFactory::build())['access_token'];

        return $authToken;
    }
}
