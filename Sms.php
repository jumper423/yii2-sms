<?php

namespace jumper423\sms;

use jumper423\sms\error\SmsException;
use jumper423\sms\service\SmsServiceBase;
use jumper423\sms\service\SmsSites;
use yii\base\Component;
use yii\base\Exception;

class Sms extends Component
{
    /** @var SmsServiceBase */
    private $service;

    private $site = SmsSites::OTHER;

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

    /** @var SmsServiceBase[] */
    public $services = [];

    public function init()
    {
        parent::init();
        $services = [];
        foreach ($this->services as $key => $service) {
            if (!is_object($service)) {
                $service = \Yii::createObject($service);
                $balance = $service->getBalance();
                if (is_null($balance) || $balance > 0) {
                    $services[$key] = $service;
                }
            }
        }
        $this->services = $services;
        $this->setSite($this->site);
    }

    /**
     * @param null|array $site
     */
    public function setSite($site = null)
    {
        $this->site = $site;
        if (!is_null($site)) {
            $prices = [];
            foreach ($this->services as $key => $service) {
                $service->setSite($site);
                $prices[$key] = $service->getPrice();
            }
            asort($prices);
            $services = [];
            foreach ($prices as $key => $price) {
                $services[$key] = $this->services[$key];
            }
            $this->services = $services;
        }
    }

    /**
     * Доступное количество номеров
     * @param null|array $site
     * @return integer
     * @throws Exception
     */
    public function getNumbersStatus($site = null)
    {
        $this->setSite($site);
        $count = 0;
        foreach ($this->services as $key => $service) {
            try {
                $count += $service->getNumbersStatus();
            } catch (SmsException $e) {
                unset($this->services[$key]);
            }
        }
        return $count;
    }

    /**
     * Баланс
     * @return integer
     * @throws Exception
     */
    public function getBalance()
    {
        $balance = 0;
        foreach ($this->services as $service) {
            $balance += $service->getBalance();
        }
        return $balance;
    }

    /**
     * Получить номер
     * @param null|array $site
     * @return string
     * @throws SmsException
     */
    public function getNumber($site = null)
    {
        $this->setSite($site);
        foreach ($this->services as $service) {
            try {
                $number = $service->getNumber();
                $this->service = $service;
                return $number;
            } catch (SmsException $e) {
            }
        }
        throw new SmsException('Не нашло номер');
    }

    /**
     * Задаём статус
     * @param int $status
     * @throws SmsException
     */
    public function setStatus($status = self::STATUS_READY)
    {
        /** @var SmsServiceBase $service */
        $service = $this->service;
        switch ($status) {
            case self::STATUS_CANCEL:
                $this->service->setStatus($service::$METHOD_CANCEL);
                break;
            case self::STATUS_COMPLETE:
                $this->service->setStatus($service::$METHOD_COMPLETE);
                break;
            case self::STATUS_READY:
                $this->service->setStatus($service::$METHOD_READY);
                break;
            case self::STATUS_INVALID:
                $this->service->setStatus($service::$METHOD_INVALID);
                break;
            case self::STATUS_USED:
                $this->service->setStatus($service::$METHOD_USED);
                break;
            default:
                throw new SmsException('Нет такого статуса');
        }
    }

    /**
     * Получаем код
     * @return string
     * @throws SmsException
     */
    public function getCode()
    {
        return $this->service->getCode();
    }
}