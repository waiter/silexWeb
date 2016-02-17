<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/17
 * Time: 下午10:38
 */

$c = $app['controllers_factory'];

$c->get('/{key}', function($key) use($app) {
    return $app['ARes'](1, $app['cache']->get($key));
});

$c->get('/delete/{key}', function($key) use($app) {
    $app['cache']->delete($key);
    return $app['ARes'](1, $app['cache']->get($key));
});

return $c;