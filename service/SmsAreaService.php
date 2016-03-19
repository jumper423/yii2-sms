<?php

namespace jumper423\sms\service;

use jumper423\sms\error\SmsException;

/**
 * http://sms-area.org/
 *
 * Class SmsAreaService
 * @package jumper423\sms\service
 */
class SmsAreaService extends SmsServiceBase
{
    protected $sites = [
        SmsSites::VKONTAKTE => [
            'name' => 'vk',
            'price' => 12,
        ],
        SmsSites::MAMBA => [
            'name' => 'mb',
            'price' => 5,
        ],
        SmsSites::ODNOKLASSNIKI => [
            'name' => 'ok',
            'price' => 6,
        ],
        SmsSites::GAME4 => [
            'name' => '4g',
            'price' => 5,
        ],
        SmsSites::FACEBOOK => [
            'name' => 'fb',
            'price' => 5,
        ],
        SmsSites::SEOSPRINT => [
            'name' => 'ss',
            'price' => 5,
        ],
        SmsSites::INSTAGRAM => [
            'name' => 'ig',
            'price' => 6,
        ],
        SmsSites::WEBTRANSFER_FINANCE => [
            'name' => 'wt',
            'price' => 5,
        ],
        SmsSites::TELEGRAM => [
            'name' => 'tg',
            'price' => 11,
        ],
        SmsSites::VIBER => [
            'name' => 'vr',
            'price' => 5,
        ],
        SmsSites::WHATSAPP => [
            'name' => 'wa',
            'price' => 6,
        ],
        SmsSites::WEBMONEY => [
            'name' => 'wm',
            'price' => 6,
        ],
        SmsSites::QIWI => [
            'name' => 'qm',
            'price' => 7,
        ],
        SmsSites::YANDEX => [
            'name' => 'ym',
            'price' => 5,
        ],
        SmsSites::GOOGLE => [
            'name' => 'gm',
            'price' => 5,
        ],
        SmsSites::CENOBOY => [
            'name' => 'cb',
        ],
        SmsSites::AVITO => [
            'name' => 'at',
            'price' => 6,
        ],
        SmsSites::OTHER => [
            'name' => 'or',
            'price' => 6,
        ],
    ];

    protected $href = 'http://sms-area.org/stubs/handler_api.php?action={method}';

    const API_KEY = 'api_key';
    const ID = 'id';
    const SITE = 'service';
    const NUMBER = 'number';

    public static $METHOD_GET_BALANCE = 'getBalance';
    public static $METHOD_GET_NUMBER = [
        'method' => 'getNumber',
        'country' => 'ru',
    ];
    public static $METHOD_READY = [
        'method' => 'setStatus',
        'status' => 1,
    ];
    public static $METHOD_CANCEL = [
        'method' => 'setStatus',
        'status' => -1,
    ];
    public static $METHOD_INVALID = 'getRepeat';
    public static $METHOD_COMPLETE = [
        'method' => 'setStatus',
        'status' => 6,
    ];
    public static $METHOD_USED = [
        'method' => 'setStatus',
        'status' => 10,
    ];
    public static $METHOD_GET_STATUS = 'getStatus';

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
            $result = explode(':', $result);
            $result[] = null;
            $result[] = null;
            list($message, $result) = $result;
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
    public function setStatus($status = null)
    {
        $result = parent::setStatus($status);
        $result = explode(':', $result);
        $result[] = null;
        $result[] = null;
        list($request, $id, $number) = $result;
        switch ($request) {
            case 'ACCESS_READY':
            case 'ACCESS_RETRY_GET':
            case 'ACCESS_ACTIVATION':
            case 'ACCESS_CANCEL':
            case 'ACCESS_ERROR_NUMBER_GET':
            case 'ACCESS_REPORT':
                break;
            case 'ACCESS_NUMBER':
                $this->sessionId = $id;
                $this->number = str_pad($number, 12, "+7", STR_PAD_LEFT);
                break;
            default:
                throw new SmsException($request, 707);
        }
    }

    /** @inheritdoc */
    public function getCode()
    {
        $time = time();
        while (true) {
            if (time() - $time > 60 * 15) {
                return [false, null];
                //throw new SmsException('Превышенно время ожидания смс', 300);
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
                    return [false, null];
                    //$this->setStatus($this::$METHOD_COMPLETE);
                    //return ['RETURN', null];
                case 'STATUS_OK':
                case 'STATUS_ACCESS':
                case 'STATUS_ACCESS_SCREEN':
                    return [true, $code];
                case 'STATUS_CANCEL':
                    return [false, null];
                default:
                    return [false, null];
                    //throw new SmsException($request);
            }
        }
    }
}
