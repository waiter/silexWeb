<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/14
 * Time: 下午5:47
 */

use Silex\Application;
use Silex\ServiceProviderInterface;

class ApiResponseProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['ARes'] = $app->protect(function($state, $msg) use($app) {
            return $app->json(array(
                'state' => $state,
                'msg' => $msg
            ));
        });
    }

    public function boot(Application $app)
    {

    }
}