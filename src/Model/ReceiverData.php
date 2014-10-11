<?php

namespace BigFish\Hub3\Api\Model;

class ReceiverData
{
    public $name;
    public $street;
    public $place;
    public $iban;
    public $model;
    public $call_no;

    public static function fromObject($object)
    {
        $instance = new self();

        $instance->name = $object->name;
        $instance->street = $object->street;
        $instance->place = $object->place;
        $instance->iban = $object->iban;
        $instance->model = $object->model;
        $instance->call_no = $object->call_no;

        return $instance;
    }
}