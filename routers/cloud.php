<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/4
 * Time: 下午8:43
 */
use Symfony\Component\HttpFoundation\Request;

function pushCache($cache, $id) {
    $arr = $cache->get(Constant::CACHE_CLOUD_ATLAS_ALL);
    if (!$arr) {
        $arr = array();
    }
    $arr[$id] = 1;
    $cache->save(Constant::CACHE_CLOUD_ATLAS_ALL, $arr);
}

function deleteAll($cache) {
    $arr = $cache->get(Constant::CACHE_CLOUD_ATLAS_ALL);
    if ($arr) {
        foreach($arr as $key => $v) {
            $cache->delete($key);
        }
        $cache->delete(Constant::CACHE_CLOUD_ATLAS_ALL);
    }
}

function getBlackList($a) {
    $black = $a['cache']->get(Constant::CACHE_CLOUD_BLACK_ALL);
    if (!$black) {
        $black = $a['db']->fetchAll('select * from '.Constant::DB_CLOUD_BLACK.' order by id asc');
        $a['cache']->save(Constant::CACHE_CLOUD_BLACK_ALL, $black);
    }
    return $black;
}

function checkPackageInBlack($black, $package) {
    $res = false;
    foreach($black as $item) {
        if ($package == $item['package']) {
            $res = true;
            break;
        }
    }
    return $res;
}

$ad = $app['controllers_factory'];

$ad->get('/atlas/{package}/{channel}', function(Request $request, $package, $channel) use($app) {
    if (!checkPackageInBlack(getBlackList($app), $package)) {
        $lan = $request->get('lan');
        $cacheKey = Constant::CACHE_CLOUD_ATLAS_PRE.$package.'_'.$channel.'_'.$lan;
        $lans = empty($lan) ? '' : "','$lan";
        $res = $app['cache']->get($cacheKey);
        if (!$res) {
            $res = $app['db']->fetchAll('SELECT * FROM '.Constant::DB_CLOUD_ATLAS." WHERE channel = '$channel' and package <> '$package' and `status` = 0 and `language` in ('$lans') order by language desc,priority desc limit 30");
            $app['cache']->save($cacheKey, $res);
            pushCache($app['cache'], $cacheKey);
        }
        $count = count($res);
        if ($count > 0) {
            $array = array(
                $res[0]
            );
            $priority = $res[0]['priority'];
            $nowLang = $res[0]['language'];
            $tmp = 1;
            while($tmp < $count && $res[$tmp]['priority'] == $priority && $res[$tmp]['language'] == $nowLang) {
                array_push($array, $res[$tmp]);
                $tmp++;
            }
            $key = array_rand($array, 1);
            return $app->json($array[$key]);
        }
    }
    return '{}';
})->assert('package', '^[\w.]+$')
->assert('channel', '^[\w]+$');

$ad->post('/atlas/delete', function(Request $request) use($app, $root) {
    $res = array(
        'state' => 0,
        'msg' => '错误'
    );
    try {
        $id = (int)$request->get('id');
        if ($id > 0) {
            $data = $app['db']->fetchAll('SELECT * FROM '.Constant::DB_CLOUD_ATLAS." where id=$id order by id asc limit 1");
            if (count($data) > 0) {
                preg_match('/\/upload\/.*/', $data[0]['icon'], $icon);
                preg_match('/\/upload\/.*/', $data[0]['imageHorizontal'], $imageHorizontal);
                preg_match('/\/upload\/.*/', $data[0]['imageVertical'], $imageVertical);
                $app['db']->delete(Constant::DB_CLOUD_ATLAS, array('id' => $id));
                try {
                    if (count($icon) > 0 && !empty($icon[0])) unlink($root.$icon[0]);
                    if (count($imageHorizontal) > 0 && !empty($imageHorizontal[0])) unlink($root.$imageHorizontal[0]);
                    if (count($imageVertical) > 0 && !empty($imageVertical[0])) unlink($root.$imageVertical[0]);
                } catch (Exception $e){

                }
            }
            $res['state'] = 1;
            $res['msg'] = '删除成功';
            deleteAll($app['cache']);
        }
    } catch(Exception $e) {
        $res['msg'] = $e->getMessage();
    }

    return $app->json($res);
})->before($checkLoginApi);

$ad->post('/atlas/edit', function(Request $request) use($app, $root) {
    $res = array(
        'state' => 0,
        'msg' => '错误'
    );
    try {
        $id = (int)$request->get('id');
        $appName = $request->get('appName');
        $package = $request->get('package');
        $language = $request->get('language');
        $priority = $request->get('priority');
        $priority = empty($priority) ? 0 : (int)$priority;
        $type = $request->get('type');
        $desc = $request->get('desc');
        $download = $request->get('download');
        $channel = $request->get('channel');
        $status = $request->get('status');
        $iconPath = '';
        $imageHPath = '';
        $imageVPath = '';

        $uploadPath = $root.'/upload/cloud/';
        $urlPath = $request->getSchemeAndHttpHost().'/upload/cloud/';
        $icon = $request->files->get('icon');
        if ($icon && $icon->getSize() > 0) {
            $name = 'icon_'.time().'.'.$icon->guessExtension();
            $iconPath = $urlPath.$name;
            $icon->move($uploadPath, $name);
        }

        $imageH = $request->files->get('imageHorizontal');
        if ($imageH && $imageH->getSize() > 0) {
            $name = 'imageH_'.time().'.'.$imageH->guessExtension();
            $imageHPath = $urlPath.$name;
            $imageH->move($uploadPath, $name);
        }

        $imageV = $request->files->get('imageVertical');
        if ($imageV && $imageV->getSize() > 0) {
            $name = 'imageV_'.time().'.'.$imageV->guessExtension();
            $imageVPath = $urlPath.$name;
            $imageV->move($uploadPath, $name);
        }

        $array = array(
            'appName' => $appName,
            'package' => $package,
            'language' => $language,
            'priority' => $priority,
            '`type`' => $type,
            '`desc`' => $desc,
            'download' => $download,
            'channel' => $channel,
            '`status`' => $status
        );


        if ($id > 0) {
            if (!empty($iconPath)) {
                $array['icon'] = $iconPath;
            }
            if (!empty($imageHPath)) {
                $array['imageHorizontal'] = $imageHPath;
            }
            if (!empty($imageVPath)) {
                $array['imageVertical'] = $imageVPath;
            }
            $array['updated'] = time();
            $app['db']->update(Constant::DB_CLOUD_ATLAS, $array, array('id' => $id));
        } else {
            $array['icon'] = $iconPath;
            $array['imageHorizontal'] = $imageHPath;
            $array['imageVertical'] = $imageVPath;
            $array['created'] = time();
            $array['updated'] = time();
            $app['db']->insert(Constant::DB_CLOUD_ATLAS, $array);
        }

        $res['state'] = 1;
        $res['msg'] = '保存成功';
        deleteAll($app['cache']);
    }catch (Exception $e) {
        $res['msg'] = $e->getMessage();
    }
    return $app->json($res);
})->before($checkLoginApi);

$ad->get('/', function () use($app){
    $data = $app['cache']->get(Constant::CACHE_CLOUD_ATLAS_LIST);
    if (!$data) {
        $data = $app['db']->fetchAll('SELECT * FROM '.Constant::DB_CLOUD_ATLAS.' order by id asc');
        $app['cache']->save(Constant::CACHE_CLOUD_ATLAS_LIST, $data);
        pushCache($app['cache'], Constant::CACHE_CLOUD_ATLAS_LIST);
    }
    $black = getBlackList($app);
    return $app['twig']->render('cloud/atlas.html', array(
        'list' => $data,
        'black' => $black
    ));
})->before($checkLogin);

$ad->delete('/black/delete/{id}', function($id) use($app) {
    $app['db']->delete(Constant::DB_CLOUD_BLACK, array('id'=> $id));
    $app['cache']->delete(Constant::CACHE_CLOUD_BLACK_ALL);
    return $app->json(array(
        'state' => 1,
        'msg' => '删除成功'
    ));
})->assert('id', '^[1-9][0-9]*$')
->before($checkLoginApi);

$ad->post('/black/add', function(Request $request) use($app) {
    $res = array(
        'state' => 0,
        'msg' => '错误'
    );
    $package = $request->get('package');
    if ($package && !empty($package)) {
        $black = getBlackList($app);
        if (!checkPackageInBlack($black, $package)) {
            $app['db']->insert(Constant::DB_CLOUD_BLACK, array('package' => $package));
            $app['cache']->delete(Constant::CACHE_CLOUD_BLACK_ALL);
            $res = array(
                'state' => 1,
                'msg' => '添加成功'
            );
        } else {
            $res['msg'] = '重复添加';
        }
    }
    return $app->json($res);
});

return $ad;
