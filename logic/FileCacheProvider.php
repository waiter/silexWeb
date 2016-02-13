<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/8
 * Time: 下午2:32
 */
require_once __DIR__.'/FileCache.php';

use Silex\Application;
use Silex\ServiceProviderInterface;

class FileCacheProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

    }

    public function boot(Application $app)
    {
        $app['cache'] = new FileCache($app['cache.dir']);
    }
}