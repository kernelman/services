<?php
/**
 * Class Context
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/25/19
 * Time:    3:31 PM
 */

namespace Anchorstats\statistics;


use Swoole\Coroutine;

class Context
{

    protected static $pool = [];

    /**
     * @param $key
     * @return null
     */
    public static function get($key) {
        $cid = Coroutine::getuid();
        if ($cid < 0) {
            return null;
        }

        if(isset(self::$pool[$cid][$key])) {
            return self::$pool[$cid][$key];
        }

        return null;
    }

    /**
     * @param $key
     * @param $item
     */
    public static function put($key, $item) {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $item;
        }
    }

    /**
     * @param null $key
     */
    public static function delete($key = null) {
        $cid = Coroutine::getuid();
        if ($cid > 0) {
            if($key){
                unset(self::$pool[$cid][$key]);

            } else{
                unset(self::$pool[$cid]);
            }
        }
    }
}
