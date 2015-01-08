<?php

namespace BigFish\Hub3\Api;

use Exception;

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
    /**
     * Generates a barcode from URL encoded data from a GET request.
     *
     * @param  Application $app      Application object.
     * @param  Request     $request  The request.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function barcodeGetAction(Application $app, Request $request)
    {
        $array = $request->query->all();

        // A hacky, but very simple way to convert the array to an object
        // (as expected by the validator)
        $object = json_decode(json_encode($array));

        // Convert the amount to a float (as expected by the validator) since
        // all URL parameters are transferred as strings
        if (isset($object->data->amount)) {
            if (!is_numeric($object->data->amount)) {
                return $this->error(400, "Validation failed", ["data.amount must be numeric"]);
            }

            $object->data->amount = floatval($object->data->amount);
        }

        return $this->barcodeAction($app, $request, $object);
    }

    /**
     * Generates a barcode from JSON encoded data in the body of a POST request.
     *
     * @param  Application $app      Application object.
     * @param  Request     $request  The request.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function barcodePostAction(Application $app, Request $request)
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

        return $this->barcodeAction($app, $request, $body);
    }

    /**
     * Generates the barcode from given data.
     *
     * @param  Application $app  Application object.
     * @param  object      $data Request data.
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function barcodeAction(Application $app, Request $request, $data)
    {
        if (!isset($data->options)) {
            $data->options = new \stdClass();
        }

        // Validate
        $errors = $app['validator']->validate($data);
        if (!empty($errors)) {
            return $this->error(400, "Validation failed", $errors);
        }

        // Extract parameters
        $renderer = $data->renderer;
        $options = (array) $data->options;
        $data = $data->data;

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
            return $this->handleException($app, $request, $ex);
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
    private function handleException(Application $app, Request $request, Exception $ex)
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
            newrelic_notice_error("Unhandled exception: " . $ex->getMessage(), $ex);
        }

        $this->dumpError($request, $ex);

        return $this->error(500, $message, $errors);
    }

    /**
     * Saves error data to disk for debugging.
     */
    private function dumpError(Request $request, Exception $ex)
    {
        $query = $request->query->all();
        $query = json_encode($query, JSON_PRETTY_PRINT);

        $body = $request->getContent();

        $time = date('c');

        $path = __DIR__ . '/../var/dump-' . uniqid();
        $dump = "Time: $time\n\nQuery:\n$query\n\nBody:\n$body\n\nException: $ex\n";

        @file_put_contents($path, $dump);
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
