<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/constant/Constant.php';
require_once __DIR__.'/logic/FileCacheProvider.php';
require_once __DIR__.'/logic/ApiResponseProvider.php';
require_once __DIR__.'/config/Idontknow.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$root = __DIR__;

$app = new Silex\Application();

// config
$app['config'] = array(
    'title'=> '门前雪瓦上霜'
);

// middleware
include_once 'middleware/checkLogin.php';
include_once 'middleware/vaildCheck.php';
include_once 'middleware/httpUtil.php';

// providers
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => include 'config/config.php'
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new FileCacheProvider(), array(
    'cache.dir' => __DIR__.'/cache'
));

$app->register(new \Silex\Provider\SessionServiceProvider());

$app->register(new ApiResponseProvider());


// routers
$app->mount('/cloud', include 'routers/cloud.php');
$app->mount('/login', include 'routers/login.php');
$app->mount('/rank', include 'routers/rank.php');
$app->mount('/c', include 'routers/c.php');
$app->mount('/config', include 'routers/config.php');
$app->mount('/booktalk', include 'routers/booktalk.php');

$app->get('/', function (Request $request) use($app){
    preg_match('/upload\/.*/','http://localhost:8084/upload/cloud/icon_1454773492.png', $res);
    return $app['twig']->render('home.html', array(
        'names' => array('big', 'small', $request->getSchemeAndHttpHost()),$res[0]
    ));
})->before($checkLogin);


// error handler
$app->error(function (\Exception $e, $code) use($app) {
    $message = '';
    switch ($code) {
        case 404:
            return $app['twig']->render('404.html');
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message);
});

$app->run();
