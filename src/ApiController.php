<?php

namespace BigFish\Hub3\Api;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;

class ApiController
{
    const SCHEMA_PATH = '/../web/static/hub3-schema.json';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getBarcodeAction(Request $request)
    {
        $ct = $request->headers->get('Content-Type');
        if (0 !== strpos($ct, 'application/json')) {
            $this->app->abort(400, "Invalid content type: $ct");
        }

        $data = json_decode($request->getContent());
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            $this->app->abort(400, "Invalid JSON data: $error");
        }

        $response = $this->validate($data);
        if ($response !== null) {
            return $response;
        }

        return "123";
    }

    private function validate($data)
    {
        // print_r($data); die;
        $schemaPath = __DIR__ . self::SCHEMA_PATH;

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve("file://" . $schemaPath);

        $validator = new Validator();
        $validator->check($data, $schema);

        if (!$validator->isValid()) {
            $return = [
                'errors' => []
            ];
            foreach ($validator->getErrors() as $error) {
                $return['errors'][$error['property']][] = $error['message'];
            }

            return $this->app->json($return, 400);
        }

        return null;
    }
}
