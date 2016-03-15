<?php

namespace jumper423\sms;

use yii\helpers\ArrayHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Json;
use jumper423\behaviors\СallableBehavior;

class Sms extends Component
{
    /** @var string Ключ API */
    private $apiKey = null;
    /** @var string Сокращение названия сервиса */
    private $service = 'ot';
    private $number = null;
    private $sessionId = null;

    private $href = 'http://sms-activate.ru/stubs/handler_api.php';

    /** отменить активацию */
    const STATUS_CANCEL = -1;
    /** сообщить о готовности номера (смс на номер отправлено) */
    const STATUS_READY = 1;
    /** сообщить о неверном коде */
    const STATUS_INVALID = 3;
    /** завершить активацию(если был статус "код получен" - помечает успешно и завершает, если был "подготовка" - удаляет и помечает ошибка, если был статус "ожидает повтора" - переводит активацию в ожидание смс) */
    const STATUS_COMPLETE = 6;
    /** сообщить о том, что номер использован и отменить активацию */
    const STATUS_USED = 8;

    const EVENT_INIT = 'init';

    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    public function behaviors()
    {
        return [
            [
                'class' => СallableBehavior::className(),
                'attributes' => [
                    self::EVENT_INIT => ['apiKey',],
                ],
            ],
        ];
    }

    public function setService($service = null)
    {
        if (!is_null($service)) {
            $this->service = $service;
        }
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Доступное количество номеров
     * @param null $service
     * @return mixed
     * @throws Exception
     */
    public function getNumbersStatus($service = null)
    {
        $this->setService($service);
        $request = Json::decode($this->curl([
            'action' => 'getNumbersStatus',
        ]));
        return ArrayHelper::getValue($request, "{$this->service}_0", 0);
    }

    /**
     * Баланс
     * @return integer
     * @throws Exception
     */
    public function getBalance()
    {
        $request = $this->curl([
            'action' => 'getBalance',
        ]);
        list($message, $result) = explode(':', $request);
        switch ($message) {
            case 'ACCESS_BALANCE':
                return $result;
            default:
                throw new Exception($message);
        }
    }

    /**
     * Получить номер
     * @param null $service
     * @return string
     * @throws Exception
     */
    public function getNumber($service = null)
    {
        $this->setService($service);
        $while = true;
        while ($while) {
            $curl = $this->curl([
                'action' => 'getNumber',
                'service' => $this->service,
            ]);
            $result = explode(':', $curl);
            $result[] = null;
            $result[] = null;
            list($request, $id, $number) = $result;
            switch ($request) {
                case 'NO_NUMBERS':
                    //sleep(60);
                    //break;
                    throw new Exception($request, 404);
                case 'ACCESS_NUMBER':
                    $this->sessionId = $id;
                    $this->number = str_pad($number, 12, "+7", STR_PAD_LEFT);
                    $while = false;
                    break;
                default:
                    throw new Exception($request);
            }
        }
        return $this->number;
    }

    /**
     * Задаём статус
     * @param int $status
     * @throws Exception
     */
    public function setStatus($status = self::STATUS_READY)
    {
        $request = $this->curl([
            'action' => 'setStatus',
            'status' => $status,
            'id' => $this->sessionId,
        ]);
        switch ($request) {
            case 'ACCESS_READY':
            case 'ACCESS_RETRY_GET':
            case 'ACCESS_ACTIVATION':
            case 'ACCESS_CANCEL':
                break;
            default:
                throw new Exception($request, 707);
        }
    }

    /**
     * Получаем код
     * @return array
     * @throws Exception
     */
    public function getCode()
    {
        $time = time();
        while (true) {
            if (time() - $time > 60 * 15) {
                throw new Exception('Превышенно время ожидания смс', 300);
            }
            $curl = $this->curl([
                'action' => 'getStatus',
                'id' => $this->sessionId,
            ]);
            $result = explode(':', $curl);
            $result[] = null;
            $request = array_shift($result);
            $code = [];
            foreach ($result as $resultRow) {
                $code[] = $resultRow;
            }
            $code = implode(':', $code);
            switch ($request) {
                case 'STATUS_WAIT_RETRY':
                case 'STATUS_WAIT_CODE':
                    sleep(30);
                    break;
                case 'STATUS_WAIT_RESEND':
                    $this->setStatus(self::STATUS_COMPLETE);
                    return ['RETURN', null];
                case 'STATUS_OK':
                    return ['OK', $code];
                default:
                    throw new Exception($request);
            }
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    private function curl($params = [])
    {
        $params = ArrayHelper::merge([
            'api_key' => $this->apiKey,
        ], $params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->href);
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("CURL вернул ошибку: " . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}