<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), [
    'translator.domains' => []
]);

// -- Templating ---------------------------------------------------------------

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../templates'
]);

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addExtension(new Twig_Extension_Debug($app));
    $twig->addFilter(new Twig_SimpleFilter('markdown', function ($text) {
        $parsedown = new Parsedown();
        return $parsedown->text($text);
    }, ['is_safe' => ['html']]));
    return $twig;
}));

// -- Library ------------------------------------------------------------------

$app['pdf417'] = function() use ($app) {
    return new BigFish\PDF417\PDF417();
};

// -- Controllers --------------------------------------------------------------

use BigFish\Hub3\Api\ApiController;
use BigFish\Hub3\Api\FrontpageController;

$app['frontpage_controller'] = $app->share(function() use ($app) {
    return new FrontpageController($app);
});

$app['api_controller'] = $app->share(function() use ($app) {
    return new ApiController($app);
});

// -- Routing ------------------------------------------------------------------

$app->get('/', "frontpage_controller:indexAction");
$app->post('/barcode', "api_controller:getBarcodeAction");


// -- Go! ----------------------------------------------------------------------

$app->run();
