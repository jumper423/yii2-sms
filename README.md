# yii2-sms
Приём смс сообщений

Компонент позволяет объединить несколько сервисов по приёму смс сообщений.

Сервисы
-----------
На данные момент разработано api для сервисов
* [Sim Sms](http://simsms.org)
* [Sms Activate](http://sms-activate.ru)
* [Sms-Area](http://sms-area.org/signup.php?referrer=NjE4Mjk=)
* [Sms-Reg](http://sms-reg.com)

Особенности
------------
* Сразу несколько сервисов по приёму смс сообщений
* Лёгкая возможность добавить пользовательский сервис
* Анализ на каком из сервисов есть доступные номера
* Выбор самого выгодного сервиса для определённого сайта

Установка
------------
Предпочтительный способ установить это расширение через [composer](http://getcomposer.org/download/).

Либо запустить

```
php composer.phar require --prefer-dist jumper423/yii2-sms "*"
```

или добавить

```
"jumper423/yii2-sms": "*"
```

в файл `composer.json`.

Конфигурация
------------
Указать ключи от своих аккаунтов и от куда по умолчанию будут приходить смс сообщения.

```php
'components' => [
    'sms' => [
        'class' => \jumper423\sms\Sms::className(),
        'site' => \jumper423\sms\service\SmsSites::OTHER,
        'services' => [
            [
                'class' => \jumper423\sms\service\SmsActivateService::className(),
                'apiKey' => 'apiKey1234567890',
            ],
            [
                'class' => \jumper423\sms\service\SmsAreaService::className(),
                'apiKey' => 'apiKey1234567890',
            ],
            [
                'class' => \jumper423\sms\service\SmsSimService::className(),
                'apiKey' => 'apiKey1234567890',
            ],
            [
                'class' => \jumper423\sms\service\SmsRegService::className(),
                'apiKey' => 'apiKey1234567890',
            ],
        ],
    ],
],
```

Методы
------------
```php
/** @var Sms $sms */
$sms = \Yii::$app->sms;
```

#### Запрос на получение общего баланса
```php
$balance = $sms->getBalance(); 
if (!$balance) {
    throw new Exception('Нет денег на смс');
}
```

#### Изменяем сайт с которого необходимо получить смс
```php
$sms->site = \jumper423\sms\service\SmsSites::VKONTAKTE;
```

#### Количество доступных номеров
```php
$count = $sms->getNumbersStatus();
```

#### Получение номера
```php
$number = $sms->getNumber();
```

#### Изменяем статус
```php
// Отменить активацию
$sms->setStatus($sms::STATUS_CANCEL);
// Сообщить о готовности номера (смс на номер отправлено)
$sms->setStatus($sms::STATUS_READY);
// Сообщить о неверном коде
$sms->setStatus($sms::STATUS_INVALID);
// Завершить активацию(если был статус "код получен" - помечает успешно и завершает, если был "подготовка" - удаляет и помечает ошибка, если был статус "ожидает повтора" - переводит активацию в ожидание смс)
$sms->setStatus($sms::STATUS_COMPLETE);
// Сообщить о том, что номер использован и отменить активацию
$sms->setStatus($sms::STATUS_USED);
```

#### Получение кода
```php
$code = $sms->getCode();
```

Пример использования
------------
```php
$sms = new Sms();
try {
    $number = $sms->getNumber();
    ...
    $sms->setStatus($sms::STATUS_READY);
    list($status, $code) = $sms->getCode();
    if ($status) {
        ...
        $sms->setStatus($sms::STATUS_COMPLETE);
    } else {
        ...
    }
} catch (Exception $e) {
    $sms->setStatus($sms::STATUS_CANCEL);
    throw $e;
}
```