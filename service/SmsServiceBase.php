<?php

namespace jumper423\sms\service;

use jumper423\sms\error\SmsException;
use yii\helpers\ArrayHelper;
use yii\base\Component;
use yii\base\Exception;
use jumper423\behaviors\СallableBehavior;

class SmsServiceBase extends Component
{
    /** @var string Ключ API */
    protected $apiKey = null;
    /** @var string Сокращение названия сервиса */
    protected $site = SmsSites::OTHER;
    protected $number = null;
    protected $sessionId = null;
    protected $balance = null;

    protected $sites = [];

    /** @var null Ссылка для запросов */
    protected $href = 'http:://sms.sms/api?method={method}';

    const API_KEY = 'api_key';
    const ID = 'id';
    const SITE = 'service';
    const NUMBER = 'number';

    /** Количества доступных номеров */
    const METHOD_GET_NUMBERS_STATUS = null;
    /** Баланс */
    const METHOD_GET_BALANCE = null;
    /** Заказ номера */
    const METHOD_GET_NUMBER = null;
    /** сообщить о готовности номера (смс на номер отправлено) */
    const METHOD_READY = null;
    /** отменить активацию */
    const METHOD_CANCEL = null;
    /** запросить еще один код (бесплатно) */
    const METHOD_INVALID = null;
    /** завершить активацию(если был статус "код получен" - помечает успешно и завершает, если был "подготовка" - удаляет и помечает ошибка, если был статус "ожидает повтора" - переводит активацию в ожидание смс) */
    const METHOD_COMPLETE = null;
    /** сообщить о том, что номер использован и отменить активацию */
    const METHOD_USED = null;
    /** получает статус */
    const METHOD_GET_STATUS = null;

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

    public function setSite($site = null)
    {
        if (!is_null($site)) {
            if (isset($this->sites[$site])) {
                $this->site = $site;
            } else {
                $this->site = SmsSites::OTHER;
            }
        }
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Доступное количество номеров
     * @param null $site
     * @return mixed
     * @throws SmsException
     */
    public function getNumbersStatus($site = null)
    {
        $this->setSite($site);
        return $this->curl(self::METHOD_GET_NUMBERS_STATUS, [
            self::SITE => $this->site['name'],
        ]);
    }

    /**
     * Баланс
     * @return integer
     * @throws SmsException
     */
    public function getBalance()
    {
        return $this->curl(self::METHOD_GET_BALANCE);
    }

    /**
     * Получить номер
     * @param null $site
     * @return string
     * @throws SmsException
     */
    public function getNumber($site = null)
    {
        $this->setSite($site);
        return $this->curl(self::METHOD_GET_NUMBER, [
            self::SITE => $this->site['name'],
        ]);
    }

    /**
     * Задаём статус
     * @param null $status
     * @return string
     * @throws SmsException
     */
    public function setStatus($status = self::METHOD_READY)
    {
        return $this->curl($status, [
            self::ID => $this->sessionId,
        ]);
    }

    /**
     * Получаем код
     * @return array
     * @throws SmsException
     */
    public function getCode()
    {
        return $this->curl(self::METHOD_GET_STATUS, [
            self::ID => $this->sessionId,
        ]);
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed|null
     * @throws Exception
     */
    protected function curl($method, $params = [])
    {
        if (is_null($method)) {
            return null;
        }
        if (is_array($method)) {
            foreach ($method as $key => $value) {
                if ($key != 'method') {
                    $params[$key] = $value;
                }
            }
            $method = $method['method'];
        }
        $params = ArrayHelper::merge([
            self::API_KEY => $this->apiKey,
        ], $params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace("{method}", $method, $this->href));
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

    protected static function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}