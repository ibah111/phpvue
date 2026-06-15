<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url:http,https', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $url = (string) $this->input('url', '');
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                $path = strtolower((string) parse_url($url, PHP_URL_PATH));
                $isYandexHost = preg_match('/(^|\.)yandex\.[a-z.]+$/', $host) === 1 || $host === 'ya.ru';
                $looksLikeMaps = str_contains($path, '/maps') || str_starts_with($host, 'maps.');

                if (! $isYandexHost || ! $looksLikeMaps) {
                    $validator->errors()->add('url', 'Введите ссылку на карточку организации в Яндекс.Картах.');
                }
            },
        ];
    }
}
