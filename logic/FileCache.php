<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/8
 * Time: 下午2:09
 */
use Doctrine\Common\Cache\FilesystemCache;

class FileCache
{
    var $cache = null;
    function __construct($dir) {
        $this->cache = new FilesystemCache($dir);
    }

    public function get($key) {
        return $this->cache->fetch($key);
    }

    public function save($key, $data, $time = 0) {
        if ($data != null) {
            $this->cache->save($key, $data, $time);
        }
    }

    public function delete($key) {
        $this->cache->delete($key);
    }
}