<?php

namespace BigFish\Hub3\Api\Model;

class ReceiverData
{
    public $name;
    public $street;
    public $place;
    public $iban;
    public $model;
    public $reference;

    public static function fromObject($object)
    {
        $instance = new self();

        $instance->name = $object->name;
        $instance->street = $object->street;
        $instance->place = $object->place;
        $instance->iban = $object->iban;
        $instance->model = $object->model;
        $instance->reference = $object->reference;

        return $instance;
    }
}