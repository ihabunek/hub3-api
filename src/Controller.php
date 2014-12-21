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

class Controller
{
    public function indexAction(Application $app, Request $request)
    {
        return $app['twig']->render('frontpage.twig');
    }

    public function barcodeAction(Application $app, Request $request)
    {
        $ct = $request->headers->get('Content-Type');
        if (0 !== strpos($ct, 'application/json')) {
            return $this->error(400, "Invalid content type: $ct");
        }

        $body = json_decode($request->getContent());
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = json_last_error_msg();
            return $this->error(400, "Invalid JSON data: $message");
        }

        $errors = $app['validator']->validate($body);
        if (!empty($errors)) {
            return $this->error(400, "Validation failed", $errors);
        }

        $renderer = $body->renderer;
        $options = (array) $body->options;
        $data = $body->data;

        if (!$app['worker']->rendererExists($renderer)) {
            return $this->error(400, "Renderer \"$renderer\" does not exist");
        }

        try {
            $result = $app['worker']->render($renderer, $options, $data);
        } catch (\InvalidArgumentException $ex) {
            return $this->error(400, $ex->getMessage());
        } catch (\Exception $ex) {
            return $this->error(500, "An unexpected error has occured.");
        }

        list($barcode, $contentType) = $result;
        return new Response($barcode, 200, [
            "Content-Type" => $contentType
        ]);
    }

    /**
     * Creates an JsonResponse with the given status code and data.
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
