<?php

namespace BigFish\Hub3\Api;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

use BigFish\PDF417\PDF417;
use BigFish\PDF417\Renderers\ImageRenderer;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $body = json_decode($request->getContent());
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            $this->app->abort(400, "Invalid JSON data: $error");
        }

        $response = $this->validate($body);
        if ($response !== null) {
            return $response;
        }

        return $this->render(
            $body->renderer,
            (array) $body->options,
            $body->data
        );
    }

    private function render($renderer, $options, $data)
    {
        // Convert JSON to a string by HUB3 standard
        $model = Model\TransactionData::fromObject($data);
        $string = $model->toString();

        // Encode barcode data
        $pdf417 = new PDF417();
        $barcodeData = $pdf417->encode($string);

        // Render
        $renderer = $this->getRenderer($renderer, $options);
        $image = $renderer->render($barcodeData);
        $contentType = $renderer->getContentType();

        // Form a response
        return new Response($image, 200, [
            "Content-Type" => $contentType
        ]);
    }

    private function getRenderer($renderer, array $options)
    {
        // Chose a renderer class
        switch ($renderer) {
            case "image":
                $rendererClass = ImageRenderer::class;
                break;
            default:
                throw new \InvalidArgumentException("Unknown renderer \"$renderer\".");
        }

        return new $rendererClass($options);
    }

    private function validate($body)
    {
        if (!isset($body->renderer)) {
            throw new \Exception("Missing required property \"renderer\".");
        }

        if (!isset($body->options)) {
            throw new \Exception("Missing required property \"options\".");
        }

        if (!isset($body->data)) {
            throw new \Exception("Missing required property \"data\".");
        }

        $this->validateData($body->data);
    }

    private function validateData($data)
    {
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
