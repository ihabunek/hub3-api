<?php

namespace BigFish\Hub3\Api;

use BigFish\PDF417\PDF417;
use BigFish\PDF417\Renderers\ImageRenderer;

/**
 * Interfaces with PDF417 lib to create the barcode.
 */
class Worker
{
    private $renderers = [
        "image" => ImageRenderer::class
    ];

    public function render($renderer, $options, $data)
    {
        // Convert JSON to a string by HUB3 standard
        $model = Model\TransactionData::fromObject($data);
        $string = $model->toString();

        // Encode barcode data
        $pdf417 = new PDF417();
        $barcodeData = $pdf417->encode($string);

        // Render
        $renderer = $this->getRenderer($renderer, $options);
        $barcode = $renderer->render($barcodeData);
        $contentType = $renderer->getContentType();

        return [$barcode, $contentType];
    }

    public function rendererExists($name)
    {
        return isset($this->renderers);
    }

    protected function getRenderer($name, array $options = [])
    {
        if (!isset($this->renderers[$name])) {
            throw new \InvalidArgumentException("Unknown renderer \"$renderer\".");
        }

        $class = $this->renderers[$name];

        return new $class($options);
    }
}
