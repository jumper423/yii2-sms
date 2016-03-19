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
    public $apiKey = null;
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
    const ALIAS = null;

    /** Количества доступных номеров */
    public static $METHOD_GET_NUMBERS_STATUS = null;
    /** Баланс */
    public static $METHOD_GET_BALANCE = null;
    /** Заказ номера */
    public static $METHOD_GET_NUMBER = null;
    /** сообщить о готовности номера (смс на номер отправлено) */
    public static $METHOD_READY = null;
    /** отменить активацию */
    public static $METHOD_CANCEL = null;
    /** запросить еще один код (бесплатно) */
    public static $METHOD_INVALID = null;
    /** завершить активацию(если был статус "код получен" - помечает успешно и завершает, если был "подготовка" - удаляет и помечает ошибка, если был статус "ожидает повтора" - переводит активацию в ожидание смс) */
    public static $METHOD_COMPLETE = null;
    /** сообщить о том, что номер использован и отменить активацию */
    public static $METHOD_USED = null;
    /** получает статус */
    public static $METHOD_GET_STATUS = null;

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
                $this->site = $this->sites[$site];
            } else {
                $this->site = $this->sites[SmsSites::OTHER];
            }
        }
    }

    /**
     * Цена
     * @param null|array $site
     * @return null|integer
     */
    public function getPrice($site = null)
    {
        $this->setSite($site);
        if (isset($this->site['price'])) {
            return $this->site['price'];
        }
        return null;
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
        $params = [$this::SITE => $this->site['name']];
        if (!is_null($this::ALIAS) && isset($this->site['alias'])) {
            $params[$this::ALIAS] = $this->site['alias'];
        }
        return $this->curl($this::$METHOD_GET_NUMBERS_STATUS, $params);
    }

    /**
     * Баланс
     * @return integer
     * @throws SmsException
     */
    public function getBalance()
    {
        return $this->curl($this::$METHOD_GET_BALANCE, [
//            $this::SITE => $this->site,
        ]);
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
        return $this->curl($this::$METHOD_GET_NUMBER, [
            $this::SITE => $this->site['name'],
        ]);
    }

    /**
     * Задаём статус
     * @param null $status
     * @return string
     * @throws SmsException
     */
    public function setStatus($status = null)
    {
        if ($this->site) {
            if (is_null($status)) {
                $status = $this::$METHOD_READY;
            }
            return $this->curl($status, [
                $this::ID => $this->sessionId,
                $this::SITE => $this->site['name'],
            ]);
        } else {
            return null;
        }
    }

    /**
     * Получаем код
     * @return array|void
     * @throws SmsException
     */
    public function getCode()
    {
        return $this->curl($this::$METHOD_GET_STATUS, [
            $this::ID => $this->sessionId,
            $this::SITE => $this->site['name'],
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
            $this::API_KEY => $this->apiKey,
        ], $params);

        $ch = curl_init();
        if (strpos($this->href, '?') !== false) {
            $url = str_replace("{method}", $method, $this->href) . '&' . http_build_query($params);
        } else {
            $url = str_replace("{method}", $method, $this->href) . '?' . http_build_query($params);
        }
        \Yii::info($url, 'curl');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("CURL вернул ошибку: " . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    protected static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}