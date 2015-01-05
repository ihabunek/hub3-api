<?php

use BigFish\Hub3\Api\Controller;
use BigFish\Hub3\Api\Validator;
use BigFish\Hub3\Api\Worker;
use BigFish\PDF417\PDF417;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

// Enable debug mode if HUB3_DEBUG environment variable is set
if (getenv('HUB3_DEBUG')) {
    $app['debug'] = true;
}

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

$app->get('/development', function (Application $app) {
    return $app['twig']->render('pages/development.twig');
})
->bind("development");

$app->get('/api/v1/barcode', 'controller:barcodeGetAction')
    ->bind("barcode_get");

$app->post('/api/v1/barcode', 'controller:barcodePostAction')
    ->bind("barcode_post");

// -- New Relic ----------------------------------------------------------------

if (!$app['debug'] && extension_loaded('newrelic')) {
    newrelic_set_appname("HUB-3");

    $app->before(function (Request $request) use ($app) {
        newrelic_name_transaction($request->getPathInfo());
    });
}

// -- Go! ----------------------------------------------------------------------

$app->run();
