# Пример кода - API Client

Адаптированный фрагмент кода API-клиента для одного из прошлых проектов.

Клиент предназначен для работы со сторонним аукционным сервисом, обозначенным как Source.

Цель: упростить работу с API сервиса в коде нескольких проектов Компании.

## Требования
- Обеспечить возможность единообразной работы как с публичными эндпоинтами, так и с эндпоинтами, закрытыми различными видами авторизации;
- Возможность использования клиента в проектах на PHP 5.6 и PHP 7.0-7.3;
- Вызывающий код должен быть компактным;

## Реализация

Класс Client - точка входа для вызывающего кода, класс Auth отвечает непосредственно за работу с внешним API. Используется DI, так как была необходимость делать запросы к закрытым эндпоинтам под разными логинами в цикле. Так как токены хранились в базе, инъекция зависимостей позволила кэшировать объекты Auth и тем самым снизить нагрузку на БД.

Гарантированно возвращает объект результата, либо выбрасывает исключение единственного типа.

Клиент реализует автоматическую авторизацию в случае устаревания access token и refresh token. Это казалось хорошей идеей на старте, но позже выяснилось, что такое решение приводит к "лавине" авторизаций, если клиент используется в независимых друг от друга фоновых процессах.
Решили не переделывать пока, добавили лишь периодическое принудительное обновление токенов.

## basic usage

```php
$auth           = \Native\ApiClient\Auth::create(
    'your_appid'
);
$client = \Native\ApiClient\Client::create($auth);

$result = $client->search('マーカーブラック', 100);

if ($result->isSuccess()) {
    // do some stuff
} else {
    // process error
}
```

## request oauth-protected node

```php
$host = 'http://selenium.host:4444/wd/hub'; // url of selenium webdriver standalone server

try {
    $auth = \Native\ApiClient\Auth::create(
        'your_appid',
        'your_client_secret',
        'login',
        'some_password',
        [
            'webdriverHost' => $host,
            'redirectUri' => 'https://exmaple.com/auth/redirect',
        ]
    );
    
    $auth->setPersistMethod(function ($accessToken, $refreshToken) use ($yahooLogin) {
        // some code to save acquired tokens to storage
    });
    
    $auth->setLoadMethod(function () use ($yahooLogin) {
        
        // some code to load saved tokens from storage
        
        return [
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
        ];
    });
} catch (\Native\ApiClient\Exceptions\AuthException $exception) {
    // process error
}

$client = \Native\ApiClient\Client::create($auth);

$result = $client->auctionItemAuth("l440678116");

if ($result->isSuccess()) {
    // do some useful stuff
} else {
    // process error
}

```