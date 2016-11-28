<?php

function http_post_url($url, $header, $data) {
  $opts = array('http' =>
    array(
      'method'  => 'POST',
      'header'  => $header,
      'content' => $data
    ),
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
  );
  $context = stream_context_create($opts);
  $result = file_get_contents($url, false, $context);
  return $result;
}
