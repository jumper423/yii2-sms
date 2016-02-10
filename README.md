# yii2-sms
Приём смс сообщений

```php
$sms = new Sms();
while (true) {
    try {
        try {
            $number = $sms->getNumber();
            $this->driver->setNumber($number);
            if (count($this->driver->error() == 'Превышено суточное количество запросов кодов подтверждения.') {
                $sms->setStatus(SMS::STATUS_CANCEL);
                throw new Exception('Превышено суточное количество запросов кодов подтверждения.', 201);
            }
            $sms->setStatus(SMS::STATUS_READY);
            list($status, $code) = $sms->getCode();
            if ($status == 'OK') {
                $this->driver->setCode($code);
                $sms->setStatus(SMS::STATUS_COMPLETE);
                break;
            } elseif ($status == 'RETURN') {
                $this->driver->return();
                sleep(185);
            }
        } catch (Exception $e) {
            $sms->setStatus(SMS::STATUS_CANCEL);
            throw $e;
        }
    }catch(Exception $e) {
        switch ($e->getCode()) {
            case 300:
            case 707:
                $this->driver->return();
                break;
            default:
                throw $e;
        }
    }
}
```