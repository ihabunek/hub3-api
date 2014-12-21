<?php

namespace BigFish\Hub3\Api;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator AS SchemaValidator;

class Validator
{
    const SCHEMA_PATH = '/../web/static/hub3-schema.json';

    /**
     * Validates the whole request body, including HUB3 data.
     */
    public function validate(\stdClass $body)
    {
        $errors = [];

        if (!isset($body->renderer)) {
            $errors[] = "Missing required property \"renderer\".";
        }

        if (!isset($body->options)) {
            $errors[] = "Missing required property \"options\".";
        }

        if (!isset($body->data)) {
            $errors[] = "Missing required property \"data\".";
        }

        if (!empty($errors)) {
            return $errors;
        }

        return $this->validateData($body->data);
    }

    /**
     * Validates HUB3 data from request body.
     */
    public function validateData($data)
    {
        $schemaPath = __DIR__ . self::SCHEMA_PATH;

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve("file://" . $schemaPath);

        $validator = new SchemaValidator();
        $validator->check($data, $schema);

        $errors = [];
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $property = $error['property'];
                $message = $error['message'];

                if (!empty($property)) {
                    $message = "$property: $message";
                }

                $errors[] = $message;
            }
        }

        return $errors;
    }
}
