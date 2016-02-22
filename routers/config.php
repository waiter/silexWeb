<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/20
 * Time: 下午7:45
 */
use Symfony\Component\HttpFoundation\Request;

$config = $app['controllers_factory'];

function getConfigGames($a) {
    $data = $a['cache']->get(Constant::CACHE_CONFIG_GAME_ALL);
    if (!$data) {
        $tempData = $a['db']->fetchAll('select * from '.Constant::DB_CONFIG_GAME.' order by id asc');
        $data = array();
        foreach($tempData as $temp) {
            $data[$temp['package']] = $temp;
        }
        $a['cache']->save(Constant::CACHE_CONFIG_GAME_ALL, $data);
    }
    return $data;
}

function getConfigData($a, $package) {
    $key = Constant::CACHE_CONFIG_DATA_PRE.$package;
    $data = $a['cache']->get($key);
    if (!$data) {
        $data = $a['db']->fetchAll('select * from '.Constant::DB_CONFIG_DATA." where package = '$package' order by id asc");
        $a['cache']->save($key, $data);
    }
    return $data;
}

function getConfigFormatData($a, $package) {
    $data = getConfigData($a, $package);
    $arr = array();
    if ($data && count($data) > 0) {
        foreach($data as $item) {
            $arr[$item['key']] = $item['value'];
        }
    }
    return $arr;
}

$config->get('/{package}', function($package) use($app) {
    $msg = '错误';
    try {
        $data = getConfigFormatData($app, $package);
        return $app['ARes'](1, $data);
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
});

$config->delete('/game/delete', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $package = $request->get('package');
        $app['db']->delete(Constant::DB_CONFIG_GAME, array('package' => $package));
        $app['db']->delete(Constant::DB_CONFIG_DATA, array('package' => $package));
        $app['cache']->delete(Constant::CACHE_CONFIG_GAME_ALL);
        $app['cache']->delete(Constant::CACHE_CONFIG_DATA_PRE_.$package);
        return $app['ARes'](1, '删除成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$config->post('/game/edit', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = (int)$request->get('id');
        $appName = $request->get('appName');
        $desc = $request->get('desc');
        $array = array(
            'appName' => $appName,
            '`desc`' => $desc
        );

        if ($id > 0) {
            $array['updated'] = time();
            $app['db']->update(Constant::DB_CONFIG_GAME, $array, array('id' => $id));
        } else {
            $data = getConfigGames($app);
            $package = $request->get('package');
            if (!$package || empty($package)) {
                throw new Exception('必须填写包名');
            }
            if ($data && $data[$package]) {
                throw new Exception('该包名已经存在');
            }
            $array['package'] = $package;
            $array['created'] = time();
            $array['updated'] = time();
            $app['db']->insert(Constant::DB_CONFIG_GAME, $array);
        }
        $app['cache']->delete(Constant::CACHE_CONFIG_GAME_ALL);
        return $app['ARes'](1, '保存成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$config->post('/data/edit', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = (int)$request->get('id');
        $value = $request->get('value');
        $package = $request->get('package');
        if (!$package || empty($package)) {
            throw new Exception('出错，无包名信息');
        }
        $desc = $request->get('desc');
        $array = array(
            '`value`' => $value,
            '`desc`' => $desc
        );

        if ($id > 0) {
            $array['updated'] = time();
            $app['db']->update(Constant::DB_CONFIG_DATA, $array, array('id' => $id));
        } else {
            $key = $request->get('key');
            if (!$key || empty($key)) {
                throw new Exception('参数名必填');
            }
            $cons = getConfigFormatData($app, $package);
            if ($cons[$key]) {
                throw new Exception('该参数已经存在');
            }
            $array['`key`'] = $key;
            $array['package'] = $package;
            $array['created'] = time();
            $array['updated'] = time();
            $app['db']->insert(Constant::DB_CONFIG_DATA, $array);
        }
        $app['cache']->delete(Constant::CACHE_CONFIG_DATA_PRE.$package);
        return $app['ARes'](1, '保存成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$config->delete('/data/delete', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = $request->get('id');
        $package = $request->get('package');
        $app['db']->delete(Constant::DB_CONFIG_DATA, array('id' => $id));
        $app['cache']->delete(Constant::CACHE_CONFIG_DATA_PRE.$package);
        return $app['ARes'](1, '删除成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$config->get('/', function() use($app){
    $data = getConfigGames($app);
    return $app['twig']->render('config/manager.html', array(
        'games' => $data
    ));
})->before($checkLogin);

$config->get('/manager/{package}', function($package) use($app){
    $data = getConfigGames($app);
    if ($data && count($data) > 0 && $data[$package]) {
        $list = getConfigData($app, $package);
        return $app['twig']->render('config/config.html', array(
            'game' => $data[$package],
            'list' => $list
        ));
    }
    return $app['twig']->render('404.html');
})->before($checkLogin);

return $config;