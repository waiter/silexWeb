<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/9
 * Time: 下午4:11
 */

use Symfony\Component\HttpFoundation\Request;

$login = $app['controllers_factory'];

$login->get('/', function() use($app) {
    return $app['twig']->render('login.html');
});

$login->post('/in', function(Request $request) use($app) {
    $res = array(
        'state' => 0,
        'msg' => '登录失败'
    );
    $user = $request->get('username');
    $pass = $request->get('password');
    $res = $app['db']->fetchAll("select * from ".Constant::DB_USER." where user = '$user' and password = '$pass' limit 1");
    if (count($res) > 0) {
        $app['session']->set('user', array('username' => $res[0]['name']));
        $res = array(
            'state' => 1,
            'msg' => '登录成功'
        );
    }
    return $app->json($res);
});

$login->get('/out', function() use($app){
    $res = array(
        'state' => 1,
        'msg' => '登出成功'
    );
    $app['session']->remove('user');
    return $app->json($res);
});

return $login;