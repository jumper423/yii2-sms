<?php

namespace jumper423\sms\service;

use jumper423\sms\error\SmsException;
use yii\helpers\Json;

/**
 * http://sms-reg.com/
 *
 * Class SmsRegService
 * @package jumper423\sms\service
 */
class SmsRegService extends SmsServiceBase
{
    protected $sites = [
        SmsSites::GAME4 => [
            'name' => '4game',
            'price' => 4,
        ],
        SmsSites::GOOGLE => [
            'name' => 'gmail',
            'price' => 4,
        ],
        SmsSites::FACEBOOK => [
            'name' => 'facebook',
            'price' => 4,
        ],
        SmsSites::MAILRU => [
            'name' => 'mailru',
            'price' => 4,
        ],
        SmsSites::VKONTAKTE => [
            'name' => 'vk',
            'price' => 14,
        ],
        SmsSites::ODNOKLASSNIKI => [
            'name' => 'classmates',
            'price' => 8,
        ],
        SmsSites::TWITTER => [
            'name' => 'twitter',
            'price' => 4,
        ],
        SmsSites::MAMBA => [
            'name' => 'mamba',
            'price' => 4,
        ],
        SmsSites::LOVEPLANET => [
            'name' => 'loveplanet',
            'price' => 4,
        ],
        SmsSites::TELEGRAM => [
            'name' => 'telegram',
            'price' => 14,
        ],
        SmsSites::BADOO => [
            'name' => 'badoo',
            'price' => 4,
        ],
        SmsSites::DRUGVOKRUG => [
            'name' => 'drugvokrug',
            'price' => 4,
        ],
        SmsSites::AVITO => [
            'name' => 'avito',
            'price' => 4,
        ],
        SmsSites::WEBTRANSFER_FINANCE => [
            'name' => 'wabos',
            'price' => 4,
        ],
        SmsSites::STEAM => [
            'name' => 'steam',
            'price' => 4,
        ],
        SmsSites::FOTOSTRANA => [
            'name' => 'fotostrana',
            'price' => 4,
        ],
        SmsSites::HOSTING => [
            'name' => 'hosting',
            'price' => 4,
        ],
        SmsSites::VIBER => [
            'name' => 'Viber',
            'price' => 4,
        ],
        SmsSites::WHATSAPP => [
            'name' => 'whatsapp',
            'price' => 5,
        ],
        SmsSites::TABOR => [
            'name' => 'tabor',
            'price' => 4,
        ],
        SmsSites::SEOSPRINT => [
            'name' => 'seosprint',
            'price' => 4,
        ],
        SmsSites::INSTAGRAM => [
            'name' => 'instagram',
            'price' => 5,
        ],
        SmsSites::MATROSKIN => [
            'name' => 'matroskin',
            'price' => 4,
        ],
        SmsSites::OTHER => [
            'name' => 'other',
            'price' => 6,
        ],
    ];

    protected $href = 'http://api.sms-reg.com/{method}.php';

    const API_KEY = 'apikey';
    const ID = 'tzid';
    const SITE = 'service';
    const NUMBER = 'number';

    public static $METHOD_GET_BALANCE = 'getBalance';
    public static $METHOD_GET_NUMBER = [
        'method' => 'getNum',
        'country' => 'ru',
        'appid' => 'Заполнить потом', //todo подать заявку http://sms-reg.com/ui.php?action=dev
    ];
    public static $METHOD_READY = 'setReady';
    public static $METHOD_INVALID = 'getNumRepeat';
    public static $METHOD_COMPLETE = 'setOperationOk';
    public static $METHOD_USED = 'setOperationUsed';
    public static $METHOD_GET_STATUS = 'getState';

    /** @inheritdoc */
    public function getNumbersStatus($site = null)
    {
        return null;
    }

    /** @inheritdoc */
    public function getBalance()
    {
        if (is_null($this->balance)) {
            $result = parent::getBalance();
            if (self::isJson($result)) {
                $result = Json::decode($result);
                if (isset($result['response']) && $result['response'] == 1) {
                    $this->balance = $result['balance'];
                    return $this->balance;
                }
            }
            throw new SmsException(Json::encode($result));
        }
        return $this->balance;
    }

    /** @inheritdoc */
    public function getNumber($site = null)
    {
        $result = parent::getNumber($site);
        if (self::isJson($result)) {
            $result = Json::decode($result);
            if (isset($result['response']) && $result['response'] == 1) {
                $this->sessionId = $result['tzid'];
                $inpoll = 0;
                while (true) {
                    $result = $this->curl('getState', ['tzid' => $this->sessionId]);
                    if (self::isJson($result)) {
                        $result = Json::decode($result);
                        switch ($result['response']) {
                            case 'TZ_INPOOL':
                                if ($inpoll > 5) {
                                    throw new SmsException('Не нашло номер');
                                }
                                $inpoll++;
                                sleep(5);
                                break;
                            case 'TZ_NUM_PREPARE':
                                $this->number = str_pad($result['number'], 12, "+7", STR_PAD_LEFT);
                                return $this->number;
                            default:
                                throw new SmsException(Json::encode($result));
                        }
                    }
                }
            }
        }
        throw new SmsException($result);
    }

    /** @inheritdoc */
    public function setStatus($status = null)
    {
        $result = parent::setStatus($status);
        if (!is_null($result)) {
            if (self::isJson($result)) {
                $result = Json::decode($result);
                if (isset($result['responce']) && $result['responce'] == 1) {
                    return;
                }
            }
            throw new SmsException(Json::encode($result));
        }
    }

    /** @inheritdoc */
    public function getCode()
    {
        $time = time();
        while (true) {
            if (time() - $time > 60 * 9.5) {
                throw new SmsException('Превышенно время ожидания смс', 300);
            }
            $result = parent::getCode();
            if (self::isJson($result)) {
                $result = Json::decode($result);
                if (isset($result['responce'])) {
                    switch ($result['responce']) {
                        case 'TZ_NUM_ANSWER' :
                        case 'TZ_NUM_ANSWER2' :
                            return $result['sms'];
                        case 'TZ_NUM_WAIT':
                            sleep(10);
                            break;
                        default:
                            throw new SmsException(Json::encode($result));
                    }
                    continue;
                }
            }
            throw new SmsException(Json::encode($result));
        }
    }
}