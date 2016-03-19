<?php

namespace jumper423\sms\service;

use jumper423\sms\error\SmsException;
use yii\helpers\Json;

/**
 * http://simsms.org/
 *
 * Class SmsSimService
 * @package jumper423\sms\service
 */
class SmsSimService extends SmsServiceBase
{
    protected $sites = [
        SmsSites::GAME4 => [
            'name' => 'opt0',
            'price' => 1,
            'alias' => '4game',
        ],
        SmsSites::GOOGLE => [
            'name' => 'opt1',
            'price' => 3,
            'alias' => 'gmail',
        ],
        SmsSites::FACEBOOK => [
            'name' => 'opt2',
            'price' => 5,
            'alias' => 'fb',
        ],
        SmsSites::SPACES => [
            'name' => 'opt3',
            'price' => 3,
            'alias' => 'spaces',
        ],
        SmsSites::VKONTAKTE => [
            'name' => 'opt4',
            'price' => 8.9,
            'alias' => 'vk',
        ],
        SmsSites::ODNOKLASSNIKI => [
            'name' => 'opt5',
            'price' => 5.9,
            'alias' => 'ok',
        ],
        SmsSites::MAMBA => [
            'name' => 'opt7',
            'price' => 5,
            'alias' => 'mamba',
        ],
        SmsSites::LINKEDIN => [
            'name' => 'opt8',
            'price' => 1.9,
            'alias' => 'avito',
        ],
        SmsSites::VIBER => [
            'name' => 'opt11',
            'price' => 4,
            'alias' => 'viber',
        ],
        SmsSites::FOTOSTRANA => [
            'name' => 'opt13',
            'price' => 1,
            'alias' => 'fotostrana',
        ],
        SmsSites::MICROSOFT => [
            'name' => 'opt15',
            'price' => 1.5,
            'alias' => 'ms',
        ],
        SmsSites::INSTAGRAM => [
            'name' => 'opt16',
            'price' => 5.9,
            'alias' => 'instagram',
        ],
        SmsSites::QIWI => [
            'name' => 'opt18',
            'price' => 5.5,
            'alias' => 'qiwi',
        ],
        SmsSites::OTHER => [
            'name' => 'opt19',
            'price' => 1,
            'alias' => 'others',
        ],
        SmsSites::WHATSAPP => [
            'name' => 'opt20',
            'price' => 7.9,
            'alias' => 'whatsapp',
        ],
        SmsSites::WEBTRANSFER_FINANCE => [
            'name' => 'opt21',
            'price' => 4,
            'alias' => 'webtransfer',
        ],
        SmsSites::SEOSPRINT => [
            'name' => 'opt22',
            'price' => 1.5,
            'alias' => 'seosprint',
        ],
        SmsSites::YANDEX => [
            'name' => 'opt23',
            'price' => 1,
            'alias' => 'ya',
        ],
        SmsSites::WEBMONEY => [
            'name' => 'opt24',
            'price' => 7,
            'alias' => 'webmoney',
        ],
        SmsSites::NASIMKE => [
            'name' => 'opt25',
            'price' => 2,
            'alias' => 'nasimke',
        ],
        SmsSites::COM_NU => [
            'name' => 'opt26',
            'price' => 5,
            'alias' => 'com',
        ],
        SmsSites::DODOPIZZA => [
            'name' => 'opt27',
            'price' => 5,
            'alias' => 'dodopizza',
        ],
        SmsSites::TABOR => [
            'name' => 'opt28',
            'price' => 5,
            'alias' => 'tabor',
        ],
        SmsSites::TELEGRAM => [
            'name' => 'opt29',
            'price' => 29,
            'alias' => 'telegram',
        ],
        SmsSites::PROSTOKVASHINO => [
            'name' => 'opt30',
            'price' => 2,
            'alias' => 'prostock',
        ],
        SmsSites::DRUGVOKRUG => [
            'name' => 'opt31',
            'price' => 1,
            'alias' => 'drugvokrug',
        ],
        SmsSites::DROM => [
            'name' => 'opt32',
            'price' => 5,
            'alias' => 'drom',
        ],
        SmsSites::MAILRU => [
            'name' => 'opt33',
            'price' => 0.5,
            'alias' => 'mail',
        ],
        SmsSites::CENOBOY => [
            'name' => 'opt34',
            'price' => 5,
            'alias' => '',
        ],
        SmsSites::GETTAXI => [
            'name' => 'opt35',
            'price' => 5,
            'alias' => '',
        ],
        SmsSites::VK_SERFING => [
            'name' => 'opt37',
            'price' => 5,
            'alias' => '',
        ],
        SmsSites::AUTO_RU => [
            'name' => 'opt38',
            'price' => 5,
            'alias' => '',
        ],
        SmsSites::LIKE4U => [
            'name' => 'opt39',
            'price' => 6,
            'alias' => '',
        ],
        SmsSites::VOXOX => [
            'name' => 'opt40',
            'price' => 1,
            'alias' => '',
        ],
        SmsSites::TWITTER => [
            'name' => 'opt41',
            'price' => 40,
            'alias' => 'twitter',
        ],
        SmsSites::AVITO => [
            'name' => 'opt59',
            'price' => 5,
            'alias' => '',
        ],
        SmsSites::MASTERCARD => [
            'name' => 'opt71',
            'price' => 9.9,
            'alias' => '',
        ],
        SmsSites::PREMIA_RUNETA => [
            'name' => 'opt72',
            'price' => 2,
            'alias' => '',
        ],
    ];

    protected $href = 'http://simsms.org/priemnik.php?metod={method}';

    const API_KEY = 'apikey';
    const ID = 'id';
    const SITE = 'service';
    const NUMBER = 'number';
    const ALIAS = 'service_id';

    // todo проверить после пополнения баланса... потому что требует как смотрю везде передавать service
    public static $METHOD_GET_NUMBERS_STATUS = 'get_count';
    public static $METHOD_GET_BALANCE = [
        'method' => 'get_balance',
        'service' => 'opt0',
    ];
    public static $METHOD_GET_NUMBER = [
        'method' => 'get_number',
        'country' => 'ru',
        'id' => 1,
    ];
    public static $METHOD_CANCEL = 'denial';
    public static $METHOD_INVALID = 'get_proverka';
    public static $METHOD_USED = 'ban';
    public static $METHOD_GET_STATUS = 'get_sms';

    /** @inheritdoc */
    public function getNumbersStatus($site = null)
    {
        $result = parent::getNumbersStatus($site);
        if (self::isJson($result)) {
            $result = Json::decode($result);
            foreach ($result as $key => $value) {
                if (strpos($key, 'counts') !== false) {
                    return $value;
                }
            }
        }
        throw new SmsException(Json::encode($result));
    }

    /** @inheritdoc */
    public function getBalance()
    {
        if (is_null($this->balance)) {
            $result = parent::getBalance();
            if (self::isJson($result)) {
                $result = Json::decode($result);
                if (isset($result['balance'])) {
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
            if (isset($result['number']) && $result['number'] && isset($result['id']) && $result['id'] > 0) {
                $this->sessionId = $result['id'];
                $this->number = str_pad($result['number'], 12, "+7", STR_PAD_LEFT);
                return $this->number;
            }
        }
        throw new SmsException(Json::encode($result));
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
                if (isset($result['response'])) {
                    switch ($result['response']) {
                        case 1 :
                            return $result['sms'];
                        case 2:
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