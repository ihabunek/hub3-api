<?php

namespace BigFish\Hub3\Api;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

use BigFish\PDF417\PDF417;
use BigFish\PDF417\Renderers\ImageRenderer;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Processes the barcode request and forms a response.
 */
class Controller
{
    public function barcodeAction(Application $app, Request $request)
    {
        // Check content type header
        $ct = $request->headers->get('Content-Type');
        if (0 !== strpos($ct, 'application/json')) {
            return $this->error(400, "Invalid content type: $ct");
        }

        // Decode the body data
        $body = json_decode($request->getContent());
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = json_last_error_msg();
            return $this->error(400, "Invalid JSON data: $message");
        }

        // Validate
        $errors = $app['validator']->validate($body);
        if (!empty($errors)) {
            return $this->error(400, "Validation failed", $errors);
        }

        // Extract parameters
        $renderer = $body->renderer;
        $options = (array) $body->options;
        $data = $body->data;

        // Check the renderer exists
        if (!$app['worker']->rendererExists($renderer)) {
            return $this->error(400, "Renderer \"$renderer\" does not exist");
        }

        // Render the barcode
        try {
            $result = $app['worker']->render($renderer, $options, $data);
        } catch (\InvalidArgumentException $ex) {
            return $this->error(400, $ex->getMessage());
        } catch (\Exception $ex) {
            return $this->handleException($app, $ex);
        }

        // Return the response
        list($barcode, $contentType) = $result;
        return new Response($barcode, 200, [
            "Content-Type" => $contentType
        ]);
    }

    /**
     * Processes an unexpected exception and forms a response.
     *
     * @param  Application $app  Application object.
     * @param  Exception   $ex   The exception.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    private function handleException(Application $app, Exception $ex)
    {
        // In debug mode, include exception details, otherwise just give a
        // generic error message.
        if ($app['debug']) {
            $message = $ex->getMessage();
            $errors = explode("\n", $ex->getTraceAsString());
        } else {
            $message = "An unexpected error has occured.";
            $errors = null;
        }

        // Report to new relic (only in production)
        if (!$app['debug'] && extension_loaded('newrelic')) {
            newrelic_notice_error("Caught an unhandled exception", $ex);
        }

        return $this->error(500, $message, $errors);
    }

    /**
     * Creates an error JsonResponse with the given status code and data.
     */
    private function error($statusCode, $message, array $errors = null)
    {
        $return = [
            "message" => $message
        ];

        if (isset($errors)) {
            $return['errors'] = $errors;
        }

        return new JsonResponse($return, $statusCode);
    }
}
