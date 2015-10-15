<?php

namespace BigFish\Hub3\Api\Tests;

use BigFish\Hub3\Api\Model\TransactionData;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testAmounts()
    {
        $dataProto = json_decode('{
            "amount": 69.10,
            "sender": {
                "name": "Ivan Habunek",
                "street": "Savska cesta 13",
                "place": "10000 Zagreb"
            },
            "receiver": {
                "name": "Big Fish Software d.o.o.",
                "street": "Savska cesta 13",
                "place": "10000 Zagreb",
                "iban": "HR6623400091110651272",
                "model": "00",
                "reference": "123-456-789"
            },
            "purpose": "ANTS",
            "description": "Developing a HUB-3 API"
        }');

        for ($i = 1; $i < 10000; $i++) {
            $amount = $i / 100;
            $strAmount = strval($amount);

            $data = clone $dataProto;
            $data->amount = $amount;

            $model = TransactionData::fromObject($data);
            $string = $model->toString();
            $parts = explode("\n", $string);

            $actual = $parts[2];
            $expected = str_pad($i, 15, "0", STR_PAD_LEFT);

            $this->assertSame($expected, $actual);
        }
    }
}