<?php

use Symfony\Component\HttpFoundation\Request;

function vaild_check_realChcek($obj, $token, $ck) {
  if (strlen($ck) == 32) {
    $salt = substr($ck, 3, 1).substr($ck, 19, 1).substr($ck, 12, 1).substr($ck, 23, 1);
    $keys = array_keys($obj);
    // return $keys;
    sort($keys);
    $vStr = $salt;
    foreach ($keys as $key) {
      if ($key != 'ck') {
        $vStr .= $key.'&&'.$obj[$key];
      }
    }
    $vStr .= $token;
    $md5 = md5($vStr);
    $md5Arr = str_split($md5);
    $saltArr = str_split($salt);
    $md5Arr[3] = $saltArr[0];
    $md5Arr[19] = $saltArr[1];
    $md5Arr[12] = $saltArr[2];
    $md5Arr[23] = $saltArr[3];
    $newMd5 = join('', $md5Arr);
    if ($newMd5 == $ck) {
      return true;
    }
  }
  return false;
}

function vaild_check_LoginAfter($userId, $token, $app) {
  $data = array('token' => $token, 'expired' => time() + Constant::DAY_TIME);
  $app['cache']->save(Constant::CACHE_BOOKTALK_USER_TOKEN_PRE.$userId, $data);
}

$vaildCheckUserGet = function (Request $request, $userId) use($app) {
  $tokenData = $app['cache']->get(Constant::CACHE_BOOKTALK_USER_TOKEN_PRE.$userId);
  if ($tokenData['expired'] > time()) {
    $token = $tokenData['token'];
    $ck = $request->query->get('ck');
    if (!vaild_check_realChcek($request->query->all(), $token, $ck)) {
      return $app['ARes'](0, '非法请求');
    }
  } else {
    return $app['ARes'](0, 'token过期');
  }
};

$vaildCheckPost = function(Request $request) use($app) {
  $ck = $request->query->get('ck');
  $data = json_decode($request->getContent(), true);
  if (!vaild_check_realChcek($data, '', $ck)) {
    return $app['ARes'](0, '非法请求');
  }
};

$vaildCheckUserPost = function (Request $request, $userId) use($app) {
  $tokenData = $app['cache']->get(Constant::CACHE_BOOKTALK_USER_TOKEN_PRE.$userId);
  if ($tokenData['expired'] > time()) {
    $token = $tokenData['token'];
    $ck = $request->query->get('ck');
    $data = json_decode($request->getContent(), true);
    if (!vaild_check_realChcek($data, $token, $ck)) {
      return $app['ARes'](0, '非法请求');
    }
  } else {
    return $app['ARes'](0, 'token过期');
  }
};
