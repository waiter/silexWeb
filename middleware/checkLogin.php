<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/23
 * Time: 下午7:32
 */
$checkLogin = function () use($app) {
    $user = $app['session']->get('user');
    if (!$user) {
        return $app->redirect('/index.php/login');
    }
};

$checkLoginApi = function () use($app) {
    $user = $app['session']->get('user');
    if (!$user) {
        return $app['ARes'](0, '需要登录');
    }
};