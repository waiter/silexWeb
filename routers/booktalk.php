<?php

use Symfony\Component\HttpFoundation\Request;

function booktalk_im_make_token() {
  $res = http_post_url(
    Constant::BOOKTALK_IM_BASE_URL.'/token',
    'Content-Type: application/json',
    json_encode(array(
      'grant_type'=>'client_credentials',
      'client_id'=>Constant::BOOKTALK_IM_CLENT_ID,
      'client_secret'=>Constant::BOOKTALK_IM_CLENT_SECRET
    ))
  );
  $data = json_decode($res, true);
  return array(
    'token' => $data['access_token'],
    'expired' => time() + $data['expires_in']
  );
}

function booktalk_im_get_token($a) {
  $token = false;
  $res = $a['cache']->get(Constant::CACHE_BOOKTALK_IM_TOKEN);
  if ($res && $res['expired'] >= time()) {
    $token = $res['token'];
  }
  if (!$token) {
    $res = booktalk_im_make_token();
    $token = $res['token'];
    $a['cache']->save(Constant::CACHE_BOOKTALK_IM_TOKEN, $res);
  }
  return $token;
}

function booktalk_im_create_user($a, $user, $psw, $name) {
  $token = booktalk_im_get_token($a);
  if ($token) {
    $create = http_post_url(
    Constant::BOOKTALK_IM_BASE_URL.'/users',
    "Content-Type: application/json\r\n"."Authorization: Bearer ".$token,
    json_encode(array(
      'username'=>$user,
      'password'=>$psw,
      'nickname'=>$name
    ))
    );
    if ($create) {
      return true;
    }
  }
  return false;
}

$booktalk = $app['controllers_factory'];

$booktalk->post('/register', function(Request $request) use($app) {
  $data = json_decode($request->getContent(), true);
  if (!preg_match('/^[a-zA-Z_0-9]{3,18}$/', $data['name'])) {
    return $app['ARes'](0, '用户名只能是3-18位的大小写英文字母以及下划线');
  }
  if (!preg_match('/^[a-zA-Z_0-9]{3,18}$/', $data['psw'])) {
    return $app['ARes'](0, '密码只能是3-18位的大小写英文字母以及下划线');
  }
  $gender = $data['gender'];
  $sql = "select * from ".Constant::DB_BOOKTALK_USER." where user = '".$data['name']."' limit 1";
  $res = $app['db']->fetchAll($sql);
  if (count($res) > 0) {
    return $app['ARes'](0, '该用户名已被使用');
  }
  $imKey = $data['name'].'_'.time();
  $imPsw = $data['psw'];
  $result = booktalk_im_create_user($app, $imKey, $imPsw, $data['name']);
  if (!$result) {
    return $app['ARes'](0, '创建失败');
  }
  $addArr = array(
    'user' => $data['name'],
    'psw' => $data['psw'],
    'name' => $data['name'],
    'gender' => $data['gender'],
    'money' => Constant::BOOKTALK_USER_BASE_MONEY,
    'imKey' => $imKey,
    'imPsw' => $imPsw,
    'created' => time()
  );
  $app['db']->insert(Constant::DB_BOOKTALK_USER, $addArr);
  return $app['ARes'](1, array(
    'imKey'=>$imKey,
    'imPsw'=>$imPsw
  ));
})->before($vaildCheckPost);

$booktalk->post('/login', function(Request $request) use($app) {
  $data = json_decode($request->getContent(), true);
  if (!preg_match('/^[a-zA-Z_0-9]{3,18}$/', $data['user'])) {
    return $app['ARes'](0, '用户名只能是3-18位的大小写英文字母以及下划线');
  }
  if (!preg_match('/^[a-zA-Z_0-9]{3,18}$/', $data['psw'])) {
    return $app['ARes'](0, '密码只能是3-18位的大小写英文字母以及下划线');
  }
  $sql = "select * from ".Constant::DB_BOOKTALK_USER." where user = '".$data['user']."' and psw = '".$data['psw']."' limit 1";
  $res = $app['db']->fetchAll($sql);
  if (count($res) < 1) {
    return $app['ARes'](0, '用户名或密码错误');
  }
  $user = $res[0];
  $now = time();
  $loginReward = 0;
  $updateArr = array(
    'last' => $now,
    'money' => $user['money'],
  );
  if ($user['last'] > 0 && $user['last'] - $now > Constant::DAY_TIME) {
    $loginReward = Constant::BOOKTALK_USER_MONEY_EVERY;
    $updateArr['money'] = $user['money'] + $loginReward;
  }
  $app['db']->update(Constant::DB_BOOKTALK_USER, $updateArr, array('id' => $user['id']));
  return $app['ARes'](1, array(
    'id' => $user['id'],
    'name' => $user['name'],
    'gender' => $user['gender'],
    'avatar' => $user['avatar'],
    'info' => $user['info'],
    'money' => $updateArr['money'],
    'imKey' => $user['imKey'],
    'imPsw' => $user['imPsw'],
    'reward' => $loginReward
  ));
})->before($vaildCheckPost);


return $booktalk;
