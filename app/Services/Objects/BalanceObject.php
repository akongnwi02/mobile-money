<?php


namespace App\Services\Objects;


class BalanceObject
{
    public $current;
    public $previous;
    public $time;
    public $service_code;

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @param mixed $current
     */
    public function setCurrent($current): void
    {
        $this->current = $current;
    }

    /**
     * @return mixed
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * @param mixed $previous
     */
    public function setPrevious($previous): void
    {
        $this->previous = $previous;
    }

    /**
     * @return mixed
     */
    public function getServiceCode()
    {
        return $this->service_code;
    }

    /**
     * @param mixed $service_code
     */
    public function setServiceCode($service_code): void
    {
        $this->service_code = $service_code;
    }

}