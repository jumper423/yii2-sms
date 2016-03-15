<?php

namespace jumper423\sms\service;

use jumper423\sms\error\SmsException;
use yii\helpers\ArrayHelper;

class SmsActivateService extends SmsServiceBase
{
    protected $sites = [
        SmsSites::VKONTAKTE => [
            'name' => 'vk',
            'price' => 8,
        ],
        SmsSites::ODNOKLASSNIKI => [
            'name' => 'od',
            'price' => 6,
        ],
        SmsSites::WHATSAPP => [
            'name' => 'wa',
            'price' => 8,
        ],
        SmsSites::VIBER => [
            'name' => 'vi',
            'price' => 3,
        ],
        SmsSites::TELEGRAM => [
            'name' => 'tg',
            'price' => 14,
        ],
        SmsSites::PERISCOPE => [
            'name' => 'wb',
            'price' => 2,
        ],
        SmsSites::GOOGLE => [
            'name' => 'go',
            'price' => 2,
        ],
        SmsSites::AVITO => [
            'name' => 'av',
            'price' => 4,
        ],
        SmsSites::FACEBOOK => [
            'name' => 'fb',
            'price' => 2,
        ],
        SmsSites::TWITTER => [
            'name' => 'tw',
            'price' => 1,
        ],
        SmsSites::TAXI2412 => [
            'name' => 'ub',
            'price' => 2,
        ],
        SmsSites::QIWI => [
            'name' => 'qw',
            'price' => 6,
        ],
        SmsSites::GETT => [
            'name' => 'gt',
            'price' => 4,
        ],
        SmsSites::WEBMONEY => [
            'name' => 'sn',
            'price' => 4,
        ],
        SmsSites::INSTAGRAM => [
            'name' => 'ig',
            'price' => 6,
        ],
        SmsSites::SEOSPRINT => [
            'name' => 'ss',
            'price' => 2,
        ],
        SmsSites::SMART_CALL => [
            'name' => 'ym',
            'price' => 2,
        ],
        SmsSites::YANDEX => [
            'name' => 'ya',
            'price' => 1,
        ],
        SmsSites::PURINA_PRO_PLAN => [
            'name' => 'ma',
            'price' => 2,
        ],
        SmsSites::MICROSOFT => [
            'name' => 'mm',
            'price' => 1,
        ],
        SmsSites::TALK2 => [
            'name' => 'uk',
            'price' => 2,
        ],
        SmsSites::STEAM => [
            'name' => 'me',
            'price' => 2,
        ],
        SmsSites::YAHOO => [
            'name' => 'mb',
            'price' => 1,
        ],
        SmsSites::AOL => [
            'name' => 'we',
            'price' => 1,
        ],
        SmsSites::OTHER => [
            'name' => 'ot',
            'price' => 2,
        ],
    ];

    protected $href = 'http://sms-activate.ru/stubs/handler_api.php?action={method}';

    const API_KEY = 'api_key';
    const ID = 'id';
    const SITE = 'service';
    const NUMBER = 'number';

    const METHOD_GET_NUMBERS_STATUS = 'getNumbersStatus';
    const METHOD_GET_BALANCE = 'getBalance';
    const METHOD_GET_NUMBER = 'getNumber';
    const METHOD_READY = [
        'method' => 'setStatus',
        'status' => 1,
    ];
    const METHOD_CANCEL = [
        'method' => 'setStatus',
        'status' => -1,
    ];
    const METHOD_INVALID = [
        'method' => 'setStatus',
        'status' => 3,
    ];
    const METHOD_COMPLETE = [
        'method' => 'setStatus',
        'status' => 6,
    ];
    const METHOD_USED = [
        'method' => 'setStatus',
        'status' => 8,
    ];
    const METHOD_GET_STATUS = 'getStatus';

    /** @inheritdoc */
    public function getNumbersStatus($site = null)
    {
        $result = parent::getNumbersStatus($site);
        return ArrayHelper::getValue($result, "{$this->site['name']}_0", 0);
    }

    /** @inheritdoc */
    public function getBalance()
    {
        if (!is_null($this->balance)) {
            $result = parent::getBalance();
            list($message, $result) = explode(':', $result);
            switch ($message) {
                case 'ACCESS_BALANCE':
                    $this->balance = $result;
                    return $this->balance;
                default:
                    throw new SmsException($message);
            }
        }
        return $this->balance;
    }

    /** @inheritdoc */
    public function getNumber($site = null)
    {
        $result = parent::getNumber($site);
        $result = explode(':', $result);
        $result[] = null;
        $result[] = null;
        list($request, $id, $number) = $result;
        switch ($request) {
            case 'NO_NUMBERS':
                throw new SmsException($request, 404);
            case 'ACCESS_NUMBER':
                $this->sessionId = $id;
                $this->number = str_pad($number, 12, "+7", STR_PAD_LEFT);
                break;
            default:
                throw new SmsException($request);
        }
        return $this->number;
    }

    /** @inheritdoc */
    public function setStatus($status = self::METHOD_READY)
    {
        $result = parent::setStatus($status);
        switch ($result) {
            case 'ACCESS_READY':
            case 'ACCESS_RETRY_GET':
            case 'ACCESS_ACTIVATION':
            case 'ACCESS_CANCEL':
                break;
            default:
                throw new SmsException($result, 707);
        }
    }

    /** @inheritdoc */
    public function getCode()
    {
        $time = time();
        while (true) {
            if (time() - $time > 60 * 15) {
                throw new SmsException('Превышенно время ожидания смс', 300);
            }
            $result = parent::getCode();
            $result = explode(':', $result);
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
                    sleep(10);
                    break;
                case 'STATUS_WAIT_RESEND':
                    $this->setStatus(self::METHOD_COMPLETE);
                    return ['RETURN', null];
                case 'STATUS_OK':
                    return ['OK', $code];
                default:
                    throw new SmsException($request);
            }
        }
    }
}