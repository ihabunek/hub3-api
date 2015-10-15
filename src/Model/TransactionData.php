<?php

namespace BigFish\Hub3\Api\Model;

class TransactionData
{
    public $amount;
    public $purpose;
    public $desription;
    public $sender;
    public $receiver;

    public static function fromObject($object)
    {
        $instance = new self();

        $instance->amount = $object->amount;
        $instance->purpose = $object->purpose;
        $instance->description = $object->description;
        $instance->sender = SenderData::fromObject($object->sender);
        $instance->receiver = ReceiverData::fromObject($object->receiver);

        return $instance;
    }

    public function toString()
    {
        $amount = round($this->amount * 100);
        $amount = str_pad($amount, 15, '0', STR_PAD_LEFT);

        $parts = [];

        $parts[] = "HRVHUB30";
        $parts[] = "HRK";
        $parts[] = $amount;
        $parts[] = $this->sender->name;
        $parts[] = $this->sender->street;
        $parts[] = $this->sender->place;
        $parts[] = $this->receiver->name;
        $parts[] = $this->receiver->street;
        $parts[] = $this->receiver->place;
        $parts[] = $this->receiver->iban;
        $parts[] = "HR" . $this->receiver->model;
        $parts[] = $this->receiver->reference;
        $parts[] = $this->purpose;
        $parts[] = $this->description;

        return implode("\n", $parts) . "\n";
    }

    public function __toString()
    {
        return $this->toString();
    }
}
