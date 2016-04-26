<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/13
 * Time: 下午1:45
 */
use Symfony\Component\HttpFoundation\Request;

function getGames($a) {
    $data = $a['cache']->get(Constant::CACHE_RANK_GAME_ALL);
    if (!$data) {
        $tempData = $a['db']->fetchAll('select * from '.Constant::DB_RANK_GAME.' order by id asc');
        $data = array();
        foreach($tempData as $temp) {
            $data[$temp['id']] = $temp;
        }
        $a['cache']->save(Constant::CACHE_RANK_GAME_ALL, $data);
    }
    return $data;
}

function getRankList($a, $gameId) {
    $key = Constant::CACHE_RANK_LIST_ALL_PRE.$gameId;
    $data = $a['cache']->get($key);
    if (!$data) {
        $data = $a['db']->fetchAll('select * from '.Constant::DB_RANK_LIST." where gameId = '$gameId' order by id asc");
        $a['cache']->save($key, $data);
    }
    return $data;
}

function deleteListCache($a, $key) {
    $infoKey = Constant::CACHE_RANK_LIST_INFO_PRE.$key;
    $info = $a['cache']->get($infoKey);
    if ($info) {
        foreach($info['cache'] as $phaseKey => $v) {
            $a['cache']->delete($phaseKey);
        }
        $a['cache']->delete($infoKey);
    }
}

function getListInfoByKey($a, $key) {
    $infoKey = Constant::CACHE_RANK_LIST_INFO_PRE.$key;
    $info = $a['cache']->get($infoKey);
    if (!$info || !$info['info']) {
        $data = $a['db']->fetchAssoc('select * from '.Constant::DB_RANK_LIST." where `key` = '$key' limit 1");
        $info = array(
            'info' => $data,
            'cache' => array()
        );
        $a['cache']->save($infoKey, $info);
    }
    return $info;
}

function getPhase($data) {
    $p = 1;
    if ($data && $data['type'] && $data['type'] > 0) {
        switch($data['type']) {
            case 1: // day
                $p = (int)(time() / Constant::DAY_TIME) - (int)($data['created'] / Constant::DAY_TIME) + 1;
                break;
            case 2: // week
                $p = (int)(time() / Constant::WEEK_TIME) - (int)($data['created'] / Constant::WEEK_TIME) + 1;
                break;
            case 3: // month
                $now = getdate();
                $create = getdate($data['created']);
                $p = ($now['year'] - $create['year']) * 12 + $now['mon'] - $create['mon'] + 1;
                break;
        }
    }
    return $p;
}

function rankSaveFile($root, $id, $content) {
    $fileName = $root."/__rank__/$id.rank";
    $myfile = fopen($fileName, "w");
    fwrite($myfile, $content);
    fclose($myfile);
}

function rankReadFile($root, $id) {
    $fileName = $root."/__rank__/$id.rank";
    $myfile = fopen($fileName, "r");
    if (!$myfile ) {
        throw new Exception("无数据");
    }
    $content = fread($myfile,filesize($fileName));
    fclose($myfile);
    return $content;
}

$rank = $app['controllers_factory'];

$rank->get('/data/{key}/{phase}', function(Request $request, $key, $phase) use($app) {
    $msg = '错误';
    try {
        $phase = (int)$phase;
        $info = getListInfoByKey($app, $key);
        $rank = $info['info'];
        if (!$rank) {
            throw new Exception('未找到该排行榜');
        }
        $tmpPhase = getPhase($rank);
        if ($phase < 1 || $phase > $tmpPhase) {
            $phase = $tmpPhase;
        }
        $dataKey = Constant::CACHE_RANK_DATA_PRE.$key.'_'.$phase;
        $datas = $app['cache']->get($dataKey);
        if (!$datas) {
            $sql = 'select id,name,uuid,score from '.Constant::DB_RANK_DATA." where `key` = '$key' and phase = $phase";
            if ($rank['min'] >= 0) {
                $sql .= ' and score >= '.$rank['min'];
            }
            if ($rank['max'] >= $rank['min']) {
                $sql .= ' and score <= '.$rank['max'];
            }
            $sql .= ' order by score '.($rank['order'] == 1 ? 'asc' : 'desc').', updated asc';
            $sql .= ' limit '.$rank['length'];
            $datas = $app['db']->fetchAll($sql);

            $app['cache']->save($dataKey, $datas);
            $info['cache'][$dataKey] = 1;
            $app['cache']->save(Constant::CACHE_RANK_LIST_INFO_PRE.$key, $info);
        }
        $arr = array(
            'info' => $rank,
            'phase' => $phase,
            'data' => $datas
        );
        $uid = $request->get('uuid');
        if ($uid && !empty($uid)) {
            $user = false;
            foreach($datas as $i => $d) {
                if ($d['uuid'] == $uid) {
                    $user = array(
                        'rank' => (int)$i + 1,
                        'uuid' => $uid,
                        'id' => $d['id'],
                        'name' => $d['name'],
                        'score' => $d['score']
                    );
                    break;
                }
            }
            if (!$user) {
                $sql = 'select id,name,uuid,score,rank from (select @rownum:=@rownum+1 rank, name,uuid,score from (select @rownum:=0,name,uuid,score from '.Constant::DB_RANK_DATA." where `key` = '$key' and phase = $phase";
                if ($rank['min'] >= 0) {
                    $sql .= ' and score >= '.$rank['min'];
                }
                if ($rank['max'] >= $rank['min']) {
                    $sql .= ' and score <= '.$rank['max'];
                }
                $sql .= ' order by score '.($rank['order'] == 1 ? 'asc' : 'desc').", updated asc) t ) tt where uuid = '$uid'";
                $users = $app['db']->fetchAll($sql);
                if ($users && count($users) > 0) {
                    $user = $users[0];
                }
            }
            if ($user) {
                $arr['user'] = $user;
            }
        }

        return $app['ARes'](1, $arr);
    } catch(Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->value('phase', 0);

$rank->get('/extra/{id}', function(Request $request, $id) use($app, $root) {
    $msg = '错误';
    try {
        $content = rankReadFile($root, $id);
        return $app['ARes'](1, $content);
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
});

$rank->get('/deleteUnuse', function() use($app, $root) {
    $ids = $app['db']->fetchAll('select id from '.Constant::DB_RANK_DATA." order by id desc");
    $msg = '';
    if ($ids && count($ids) > 0) {
        $max = (int)$ids[0]['id'];
        $useIds = array();
        foreach($ids as $i => $d) {
            $useIds[$d] = 1;
        }
        for ($i = 1; $i < $max; $i++) {
            if (!$useIds[$i]) {
                try {
                    unlink($root."/__rank__/$i.rank");
                } catch (Exception $e) {
                    $msg .= $e->getMessage();
                }
            }
        }
    }
    return $app['ARes'](1, $msg);
})->before($checkLoginApi);

$rank->match('/upload/{key}', function(Request $request, $key) use($app, $root) {
    $msg = '错误';
    try {
        $info = getListInfoByKey($app, $key);
        $rank = $info['info'];
        if (!$rank) {
            throw new Exception('未找到该排行榜');
        }
        $score = (int)$request->get('score');
        $name = $request->get('name');
        $uuid = $request->get('uuid');
        $check = (int)$request->get('c');
        $force = (int)$request->get('force', '0');
        $extra = $request->get('extra');
        if (!$uuid || empty($uuid)) {
            throw new Exception('必须上传用户唯一id');
        }
        if (($rank['min'] >= 0 && $score < $rank['min']) || ($rank['max'] >= 0 && $rank['max'] >= $rank['min'] && $score > $rank['max']) ) {
            throw new Exception('不是合法的分数');
        }
        if ($check != (int)abs((int)($rank['check']) * sin($score) + (int)($score / $rank['check']))) {
            throw new Exception('数据不合法');
        }
        $phase = getPhase($rank);
        $time = time();
        $isInsert = false;
        $needCheck = true;
        if ($rank['unique'] == 1) {
            $users = $app['db']->fetchAll('select id,score from '.Constant::DB_RANK_DATA." where `key` = '$key' and phase = $phase and uuid = '$uuid' limit 1");
            if ($users && count($users) > 0) {
                $tempD = $users[0];
                if ($force == 1 || ($rank['order'] == 1 && $score < $tempD['score']) || ($rank['order'] == 0 && $score > $tempD['score'])) {
                    $ua = array(
                        'score' => $score,
                        'updated' => $time,
                    );
                    if ($name && !empty($name)) {
                        $ua['name'] = $name;
                    }
                    $app['db']->update(Constant::DB_RANK_DATA, $ua, array('id' => $tempD['id']));
                    if ($extra && !empty($extra)) {
                        rankSaveFile($root, $tempD['id'], $extra);
                    }
                } else {
                    $needCheck = false;
                }
            } else {
                $isInsert = true;
            }
        } else {
            $isInsert = true;
        }
        if ($isInsert) {
            $rn = ($name && !empty($name)) ? $name : 'NoName';
            $app['db']->insert(Constant::DB_RANK_DATA, array(
                '`key`' => $key,
                'phase' => $phase,
                'name' => $rn,
                'uuid' => $uuid,
                'score' => $score,
                'updated' => $time,
                'created' => $time
            ));
            if ($extra && !empty($extra)) {
                rankSaveFile($root, $app['db']->lastInsertId(), $extra);
            }
        }
        if ($needCheck) {
            $dataKey = Constant::CACHE_RANK_DATA_PRE.$key.'_'.$phase;
            $cd = $app['cache']->get($dataKey);
            if ($cd) {
                $count = count($cd);
                if ($count < $rank['length'] || ($rank['order'] == 1 && $score < $cd[$count-1]['score']) || ($rank['order'] == 0 && $score > $cd[$count-1]['score'])) {
                    $app['cache']->delete($dataKey);
                }
            }
        }


        return $app['ARes'](1, '上传成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->method('GET|POST');

$rank->get('/change/{key}', function(Request $request, $key) use($app) {
    $msg = '错误';
    try {
        $name = $request->get('name');
        $uuid = $request->get('uuid');
        if (!$uuid || empty($uuid)) {
            throw new Exception('必须上传用户唯一id');
        }
        if (!$name || empty($name)) {
            throw new Exception('必须上传用户名');
        }
        $time = time();
        $info = getListInfoByKey($app, $key);
        $gameId = $info['info']['gameId'];
        $lists = getRankList($app, $gameId);
        if ($lists && count($lists) > 0) {
            $sql = 'update '.Constant::DB_RANK_DATA." set name='$name',updated=$time where uuid = '$uuid' and `key` in ('{$lists[0]['key']}'";
            foreach($lists as $list) {
                $sql .= ",'{$list['key']}'";
                deleteListCache($app, $list['key']);
            }
            $sql .= ')';
            $app['db']->query($sql);
        }
        return $app['ARes'](1, '更名成功');
    } catch(Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
});

$rank->delete('/game/delete', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = $request->get('id');
        $app['db']->delete(Constant::DB_RANK_GAME, array('id' => $id));
        $lists = getRankList($app, $id);
        if ($lists && count($lists) > 0 ){
            $sql = 'delete from '.Constant::DB_RANK_DATA." where `key` in ('{$lists[0]['key']}'";
            foreach($lists as $list) {
                $sql .= ",'{$list['key']}'";
                deleteListCache($app, $list['key']);
            }
            $sql .= ')';
            $app['db']->delete(Constant::DB_RANK_LIST, array('gameId' => $id));
            $app['db']->query($sql);
        }
        $app['cache']->delete(Constant::CACHE_RANK_GAME_ALL);
        return $app['ARes'](1, '删除成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->post('/game/edit', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = (int)$request->get('id');
        $appName = $request->get('appName');
        $package = $request->get('package');
        $desc = $request->get('desc');
        $array = array(
            'appName' => $appName,
            'package' => $package,
            '`desc`' => $desc
        );

        if ($id > 0) {
            $array['updated'] = time();
            $app['db']->update(Constant::DB_RANK_GAME, $array, array('id' => $id));
        } else {
            $array['created'] = time();
            $array['updated'] = time();
            $app['db']->insert(Constant::DB_RANK_GAME, $array);
        }
        $app['cache']->delete(Constant::CACHE_RANK_GAME_ALL);
        return $app['ARes'](1, '保存成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->post('/list/edit', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $id = (int)$request->get('id');
        $gameId = (int)$request->get('gameId');
        if ($gameId < 1) {
            throw new Exception('未关联游戏');
        }
        $name = $request->get('name');
        $desc = $request->get('desc');
        $length = (int)$request->get('length');
        $min = (int)$request->get('min');
        $max = (int)$request->get('max');

        if ($id > 0) {
            $array = array(
                'name' => $name,
                '`desc`' => $desc,
                'length' => $length,
                'min' => $min,
                'max' => $max
            );

            $array['updated'] = time();
            $key = $request->get('key');
            deleteListCache($app, $key);
            $app['db']->update(Constant::DB_RANK_LIST, $array, array('id' => $id));
        } else {
            $check = (int)$request->get('check');
            if ($check < 1) {
                throw new Exception('校验值必须大于0');
            }
            $pre = $request->get('pre');
            $type = (int)$request->get('type');
            if ($type < 0 || $type > 3) {
                $type = 0;
            }
            $numMin = (int)$request->get('numMin');
            $numMax = (int)$request->get('numMax');
            $numMax = $numMax < $numMin ? $numMin : $numMax;
            if ($numMax - $numMin > 100) {
                throw new Exception('批量序列不要超过100');
            }
            $checkRank = $app['db']->fetchAll('select 1 from '.Constant::DB_RANK_LIST." where gameId = '$gameId' and pre = '$pre' and `type` = '$type' and num >= $numMin and num <= $numMax limit 1");
            if ($checkRank && count($checkRank) > 0) {
                throw new Exception('序列区间已存在排行榜，请更换批量序列区间');
            }
            $order = (int)$request->get('order');
            $unique = (int)$request->get('unique');
            $created = time();
            $updated = $created;
            $keyPre = Constant::$RankType[$type].'_'.$gameId.'_'.$pre.'_';
            $keyFirst = $keyPre.$numMin;
            $sql = 'insert into '.Constant::DB_RANK_LIST."(gameId, name, `desc`, `key`, `type`, `order`, length, min, max, `unique`, pre, num, `check`, updated, created) values ($gameId, '$name', '$desc', '$keyFirst', $type, $order, $length, $min, $max, $unique, '$pre', $numMin, $check, $updated, $created)";
            for($i = $numMin + 1; $i <= $numMax; $i++) {
                $tempKey = $keyPre.$i;
                $sql .= ",($gameId, '$name', '$desc', '$tempKey', $type, $order, $length, $min, $max, $unique, '$pre', $i, $check, $updated, $created)";
            }
            $app['db']->query($sql);
        }
        $app['cache']->delete(Constant::CACHE_RANK_LIST_ALL_PRE.$gameId);
        return $app['ARes'](1, '保存成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->delete('/list/delete', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $key = $request->get('key');
        $gameId = $request->get('gameId');
        $app['db']->delete(Constant::DB_RANK_LIST, array('`key`' => $key));
        $app['db']->delete(Constant::DB_RANK_DATA, array('`key`' => $key));
        $app['cache']->delete(Constant::CACHE_RANK_LIST_ALL_PRE.$gameId);
        deleteListCache($app, $key);
        return $app['ARes'](1, '删除成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->delete('/list/clear', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $key = $request->get('key');
        $app['db']->delete(Constant::DB_RANK_DATA, array('`key`' => $key));
        deleteListCache($app, $key);
        return $app['ARes'](1, '清除成功');
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->get('/list/info/{key}', function(Request $request) use($app) {
    $msg = '错误';
    try {
        $key = $request->get('key');
        $info = getListInfoByKey($app, $key);
        return $app['ARes'](1, $info['info']);
    } catch (Exception $e) {
        $msg = $e->getMessage();
    }
    return $app['ARes'](0, $msg);
})->before($checkLoginApi);

$rank->get('/', function(Request $request) use($app){
    $data = getGames($app);
    return $app['twig']->render('rank/manager.html', array(
        'games' => $data
    ));
})->before($checkLogin);

$rank->get('/{id}', function($id) use($app) {
    $data = getGames($app);
    if ($data && count($data) > 0 && $data[$id]) {
        $list = getRankList($app, $id);
        return $app['twig']->render('rank/rank.html', array(
            'game' => $data[$id],
            'list' => $list
        ));
    }
    return $app['twig']->render('404.html');
})->convert('id', function ($id) { return (int) $id; })->before($checkLogin);

return $rank;