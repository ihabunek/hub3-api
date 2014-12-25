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

    /**
     * Creates a HUB3 barcode from given data and options.
     *
     * @param  string $renderer Name of the renderer to use.
     * @param  array  $options  Renderer options array.
     * @param  object $data     HUB3 data.
     *
     * @return array An array with the barcode as the first item (type depends
     *               on the renderer), and the content type string as the
     *               second item.
     */
    public function render($renderer, $options, $data)
    {
        // Convert JSON to a string by HUB3 standard
        $model = Model\TransactionData::fromObject($data);
        $string = $model->toString();

        // Encode barcode data
        $pdf417 = new PDF417();

        // Settings required by HUB3 spec
        $pdf417->setSecurityLevel(4);
        $pdf417->setColumns(9);

        $barcodeData = $pdf417->encode($string);

        // Render
        $renderer = $this->getRenderer($renderer, $options);
        $barcode = $renderer->render($barcodeData);
        $contentType = $renderer->getContentType();

        return [$barcode, $contentType];
    }

    /**
     * Checks whether the given renderer name exists.
     *
     * @param  string   $name  Name of the renderer (from $this->renderers).
     * @return boolean
     */
    public function rendererExists($name)
    {
        return isset($this->renderers);
    }

    /**
     * Factory method, creates an instance of a renderer.
     *
     * @param  string $name     Name of the renderer (from $this->renderers).
     * @param  array  $options  Renderer options array.
     *
     * @return BigFish\PDF417\RendererInterface An instance of renderer.
     */
    protected function getRenderer($name, array $options = [])
    {
        if (!isset($this->renderers[$name])) {
            throw new \InvalidArgumentException("Unknown renderer \"$renderer\".");
        }

        $class = $this->renderers[$name];

        return new $class($options);
    }
}
