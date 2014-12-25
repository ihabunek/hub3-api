<?php

use BigFish\Hub3\Api\Controller;
use BigFish\Hub3\Api\Validator;
use BigFish\Hub3\Api\Worker;
use BigFish\PDF417\PDF417;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

// -- Providers ----------------------------------------------------------------

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// -- Templating ---------------------------------------------------------------

$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../templates'
]);

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter(new Twig_SimpleFilter('markdown', function ($text) {
        $parsedown = new Parsedown();
        return $parsedown->text($text);
    }, ['is_safe' => ['html']]));
    return $twig;
}));

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('current_path', $request->getPathInfo());
});

// -- Components ---------------------------------------------------------------

$app['controller'] = $app->share(function() use ($app) {
    return new Controller();
});

$app['validator'] = $app->share(function() use ($app) {
    return new Validator();
});

$app['worker'] = $app->share(function() use ($app) {
    return new Worker();
});

$app['pdf417'] = function() use ($app) {
    return new BigFish\PDF417\PDF417();
};

// -- Routing ------------------------------------------------------------------

$app->get('/', function (Application $app) {
    return $app['twig']->render('pages/about.twig');
})
->bind("about");

$app->get('/api/v1', function (Application $app) {
    return $app['twig']->render('pages/usage.twig');
})
->bind("usage");

$app->get('/api/v1/demo', function (Application $app) {
    return $app['twig']->render('pages/demo.twig');
})
->bind("demo");

$app->post('/api/v1/barcode', 'controller:barcodeAction')
    ->bind("barcode");

// -- New Relic ----------------------------------------------------------------

if (extension_loaded('newrelic')) {
    newrelic_set_appname("HUB-3");
}

// -- Go! ----------------------------------------------------------------------

$app->run();
