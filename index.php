<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/constant/Constant.php';
require_once __DIR__.'/logic/FileCacheProvider.php';

use Symfony\Component\HttpFoundation\Request;

$root = __DIR__;

$app = new Silex\Application();

$app['config'] = array(
    'title'=> '门前雪瓦上霜'
);

$checkLogin = function () use($app) {
    $user = $app['session']->get('user');
    if (!$user) {
        return $app->redirect('/index.php/login');
    }
};

$checkLoginApi = function () use($app) {
    $user = $app['session']->get('user');
    if (!$user) {
        return $app->json(array(
            'state' => 0,
            'msg' => '需要登录'
        ));
    }
};

$app['debug'] = true;

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

$app->get('/hello/{name}', function ($name) {
    return "Hello $name!";
});

$app->mount('/cloud', include 'routers/cloud.php');
$app->mount('/login', include 'routers/login.php');

$app->get('/', function (Request $request) use($app){
    preg_match('/upload\/.*/','http://localhost:8084/upload/cloud/icon_1454773492.png', $res);
    return $app['twig']->render('home.html', array(
        'names' => array('big', 'small', $request->getSchemeAndHttpHost()),$res[0]
    ));
//    return '网站建设中...';
})->before($checkLogin);

$app->run();