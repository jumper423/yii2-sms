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
        ],
        SmsSites::GOOGLE => [
            'name' => 'opt1',
            'price' => 3,
        ],
        SmsSites::FACEBOOK => [
            'name' => 'opt2',
            'price' => 5,
        ],
        SmsSites::SPACES => [
            'name' => 'opt3',
            'price' => 3,
        ],
        SmsSites::VKONTAKTE => [
            'name' => 'opt4',
            'price' => 8.9,
        ],
        SmsSites::ODNOKLASSNIKI => [
            'name' => 'opt5',
            'price' => 5.9,
        ],
        SmsSites::MAMBA => [
            'name' => 'opt7',
            'price' => 5,
        ],
        SmsSites::LINKEDIN => [
            'name' => 'opt8',
            'price' => 1.9,
        ],
        SmsSites::VIBER => [
            'name' => 'opt11',
            'price' => 4,
        ],
        SmsSites::FOTOSTRANA => [
            'name' => 'opt13',
            'price' => 1,
        ],
        SmsSites::MICROSOFT => [
            'name' => 'opt15',
            'price' => 1.5,
        ],
        SmsSites::INSTAGRAM => [
            'name' => 'opt16',
            'price' => 5.9,
        ],
        SmsSites::QIWI => [
            'name' => 'opt18',
            'price' => 5.5,
        ],
        SmsSites::OTHER => [
            'name' => 'opt19',
            'price' => 1,
        ],
        SmsSites::WHATSAPP => [
            'name' => 'opt20',
            'price' => 7.9,
        ],
        SmsSites::WEBTRANSFER_FINANCE => [
            'name' => 'opt21',
            'price' => 4,
        ],
        SmsSites::SEOSPRINT => [
            'name' => 'opt22',
            'price' => 1.5,
        ],
        SmsSites::YANDEX => [
            'name' => 'opt23',
            'price' => 1,
        ],
        SmsSites::WEBMONEY => [
            'name' => 'opt24',
            'price' => 7,
        ],
        SmsSites::NASIMKE => [
            'name' => 'opt25',
            'price' => 2,
        ],
        SmsSites::COM_NU => [
            'name' => 'opt26',
            'price' => 5,
        ],
        SmsSites::DODOPIZZA => [
            'name' => 'opt27',
            'price' => 5,
        ],
        SmsSites::TABOR => [
            'name' => 'opt28',
            'price' => 5,
        ],
        SmsSites::TELEGRAM => [
            'name' => 'opt29',
            'price' => 29,
        ],
        SmsSites::PROSTOKVASHINO => [
            'name' => 'opt30',
            'price' => 2,
        ],
        SmsSites::DRUGVOKRUG => [
            'name' => 'opt31',
            'price' => 1,
        ],
        SmsSites::DROM => [
            'name' => 'opt32',
            'price' => 5,
        ],
        SmsSites::MAILRU => [
            'name' => 'opt33',
            'price' => 0.5,
        ],
        SmsSites::CENOBOY => [
            'name' => 'opt34',
            'price' => 5,
        ],
        SmsSites::GETTAXI => [
            'name' => 'opt35',
            'price' => 5,
        ],
        SmsSites::VK_SERFING => [
            'name' => 'opt37',
            'price' => 5,
        ],
        SmsSites::AUTO_RU => [
            'name' => 'opt38',
            'price' => 5,
        ],
        SmsSites::LIKE4U => [
            'name' => 'opt39',
            'price' => 6,
        ],
        SmsSites::VOXOX => [
            'name' => 'opt40',
            'price' => 1,
        ],
        SmsSites::TWITTER => [
            'name' => 'opt41',
            'price' => 40,
        ],
        SmsSites::AVITO => [
            'name' => 'opt59',
            'price' => 5,
        ],
        SmsSites::MASTERCARD => [
            'name' => 'opt71',
            'price' => 9.9,
        ],
        SmsSites::PREMIA_RUNETA => [
            'name' => 'opt72',
            'price' => 2,
        ],
    ];

    protected $href = 'http://simsms.org/priemnik.php?metod={method}';

    const API_KEY = 'apikey';
    const ID = 'id';
    const SITE = 'service';
    const NUMBER = 'number';

    // todo проверить после пополнения баланса... потому что требует как смотрю везде передавать service
    const METHOD_GET_NUMBERS_STATUS = 'get_count';
    const METHOD_GET_BALANCE = [
        'method' => 'get_balance',
        'service' => 'opt4',
    ];
    const METHOD_GET_NUMBER = [
        'method' => 'get_number',
        'country' => 'ru',
    ];
    const METHOD_CANCEL = 'denial';
    const METHOD_INVALID = 'get_proverka';
    const METHOD_USED = 'ban';
    const METHOD_GET_STATUS = 'get_sms';

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
    public function setStatus($status = self::METHOD_READY)
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