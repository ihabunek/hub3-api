<?php

namespace BigFish\Hub3\Api\Model;

class SenderData
{
    public $name;
    public $street;
    public $place;

    public static function fromObject($object)
    {
        $instance = new self();

        $instance->name = $object->name;
        $instance->street = $object->street;
        $instance->place = $object->place;

        return $instance;
    }
}