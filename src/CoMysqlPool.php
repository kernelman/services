<?php
/**
 * Class CoMysqlPool
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/22/19
 * Time:    5:30 PM
 */

namespace Services;


use Exceptions\NotFoundException;
use Exceptions\UnconnectedException;

class CoMysqlPool
{

    private $pool;                  // Connect pool
    public static $instance = null; // Instance

    /**
     * Initialize
     *
     * @param $config
     * @throws NotFoundException
     * @throws UnconnectedException
     */
    private function initialize($config) {
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        $this->pool = new \chan($config['maxSize']);  // Create container pool for channel.

        for ($i = 0; $i < $config['maxSize']; $i++) {
            $connect = new \Swoole\Coroutine\MySQL();
            $connect->connect($config);

            if (!$connect) {
                throw new UnconnectedException('Mysql server: ' . $config['host'] . ':' . $config['port']);
            }

            $this->recycle($connect);
        }
    }

    /**
     * Get pool instance
     *
     * @param $config
     * @return null|CoMysqlPool
     * @throws NotFoundException
     * @throws UnconnectedException
     */
    public static function getInstance($config) {
        if (self::$instance === null) {

            $pool = new CoMysqlPool();
            $pool->initialize($config);
            self::$instance = $pool;
        }

        return self::$instance;
    }

    /**
     * Get connect from chan
     *
     * @return mixed
     */
    public function get() {
        return $this->pool->pop();
    }

    /**
     * Recycle connect from chan
     *
     * @param $connect
     * @return mixed
     */
    public function recycle($connect) {
        return $this->pool->push($connect);
    }

    /**
     * Get pool size.
     *
     * @return mixed
     */
    public function poolSize() {
        return $this->pool->length();
    }
}
