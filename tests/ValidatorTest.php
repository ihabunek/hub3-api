<?php

namespace BigFish\Hub3\Api\Tests;

use BigFish\Hub3\Api\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $request = '{
        "renderer": "image",
        "options": {
            "format": "png",
            "scale": 3,
            "ratio": 3,
            "color": "#2c3e50",
            "bgColor": "#eee",
            "padding": 20
        },
        "data": {
            "amount": 100000,
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
        }
    }';

    public function testValid()
    {
        $data = json_decode($this->request);

        $validator = new Validator();
        $errors = $validator->validate($data);

        $this->assertEmpty($errors);
    }

    public function testErrors()
    {
        $data = json_decode($this->request);
        $data->data->amount = "foo";

        $validator = new Validator();
        $errors = $validator->validate($data);

        $this->assertCount(1, $errors);
        $this->assertSame("data.amount: String value found, but a number is required", $errors[0]);
    }

    public function testErrorsEmpty()
    {
        $validator = new Validator();
        $errors = $validator->validate((object)[]);

        $this->assertCount(3, $errors);
        $this->assertEquals([
            "renderer: The property renderer is required",
            "options: The property options is required",
            "data: The property data is required",
        ], $errors);
    }
}
