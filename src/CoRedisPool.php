<?php
/**
 * Class CoRedisPoole
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/22/19
 * Time:    5:30 PM
 */

namespace Services;


use Common\Property;
use Exceptions\AuthorizationException;
use Exceptions\InvalidArgumentException;
use Exceptions\NotFoundException;
use Exceptions\UnconnectedException;
use Exceptions\UnSelectedException;

class CoRedisPoole
{

    private $pool;                      // Connect pool
    private static $_db         = null;
    private static $host        = null;
    private static $port        = null;
    private static $maxSize     = null;
    private static $password    = null;
    private static $lifetime    = 0;    // 缓存Key过期时间: 默认0为不设置Key过期时间, 单位为秒
    private static $persistent  = true; // 默认开启长链接
    private static $serializer  = null; // 使用php的序列化和反序列化进行缓存数据处理

    public  static $prefix      = null; // Key默认前缀名
    public  static $config      = null; // 不为空使用自定义配置文件对象, 为空则使用第三方框架配置: Laravel
    public  static $instance    = null; // Instance

    /**
     * Initialize
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnSelectedException
     * @throws UnconnectedException
     */
    private function initialize() {
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        if (!extension_loaded('redis')) {
            throw new NotFoundException('The Redis extension can not loaded.');
        }

        if (self::$config != null) {
            self::useConfig();

        } else {
            self::useApiConfig();
        }

        $this->pool = new \chan(self::$maxSize);  // Create container pool for channel.

        for ($i = 0; $i < self::$maxSize; $i++) {

            $redis = new \Swoole\Coroutine\Redis();
            $redis->connect(self::$host, self::$port);

            if (!$redis) {
                throw new UnconnectedException('Redis server: ' . self::$host . ':' . self::$port);
            }

            // 设置Key前缀
            $success = $redis->setOption(\Redis::OPT_PREFIX, self::$prefix);
            if (!$success) {
                throw new InvalidArgumentException('Name: ' . \Redis::OPT_PREFIX . 'Value: ' . self::$prefix);
            }

            // 开启缓存数据的序列化及反序列化
            $success = $redis->setOption(\Redis::OPT_SERIALIZER, self::$serializer);
            if (!$success) {
                throw new InvalidArgumentException('Name: ' . \Redis::OPT_SERIALIZER . 'Value: ' . self::$serializer);
            }

            // 开启Scan多次扫描
            $success = $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
            if (!$success) {
                throw new InvalidArgumentException('Name: ' . \Redis::OPT_SERIALIZER . 'Value: ' . self::$serializer);
            }

            // 验证密码
            $success = $redis->auth(self::$password);
            if (!$success) {
                throw new AuthorizationException('With the Redis server');
            }

            // 选择Redis数据库(0 - 16)
            $success = $redis->select(self::$_db);
            if (!$success) {
                throw new UnSelectedException('Redis server selected database failed, the db name: ' . self::$_db);
            }

            $this->recycle($redis);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function useApiConfig() {

        // Default the DB host.
        self::$host     = env('REDIS_HOST', null);
        if (self::$host == null) {
            throw new InvalidArgumentException("The Redis host settings not found");
        }

        // Default the DB port.
        self::$port     = (int)env('REDIS_PORT', null);
        if (self::$port == 0) {
            throw new InvalidArgumentException("The Redis port settings not found");
        }

        self::$password     = env('REDIS_PASSWORD', null);
        if (self::$password == null) {
            throw new InvalidArgumentException("The Redis password settings not found");
        }

        // Default the key lifetime for DB.
        self::$lifetime     = (int)env('REDIS_LIFETIME', self::$lifetime);
        if (self::$lifetime == 0) {
            throw new InvalidArgumentException("The Redis lifetime settings must and must not be 0");
        }

        // 使用php的序列化和反序列化进行缓存数据处理
        self::$serializer  = \Redis::SERIALIZER_PHP;

        // Use default DB.
        self::$_db          = (int)env('REDIS_DB', 0);

        // False is disable pConnect, true is enable pConnect, default enable pConnect.
        self::$persistent   = env('REDIS_PERSISTENT', self::$persistent);

        self::$prefix       = env('REDIS_PREFIX', self::$prefix);
        self::$maxSize      = (int)env('MAX_SIZE', 0);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function useConfig() {

        // Default the DB host.
        self::$host     = Property::nonExistsReturnNull(self::$config, 'REDIS_HOST');
        if (self::$host == null) {
            throw new InvalidArgumentException("The Redis host settings not found");
        }

        // Default the DB port.
        self::$port     = (int)Property::nonExistsReturnZero(self::$config, 'REDIS_PORT');
        if (self::$port == 0) {
            throw new InvalidArgumentException("The Redis port settings not found");
        }

        // Access DB password.
        self::$password     = Property::nonExistsReturnNull(self::$config, 'REDIS_PASSWORD');
        if (self::$password == null) {
            throw new InvalidArgumentException("The Redis password settings not found");
        }

        // Default the key lifetime for DB.
        self::$lifetime     = (int)Property::nonExistsReturnZero(self::$config, 'REDIS_LIFETIME');
        if (self::$lifetime == 0) {
            throw new InvalidArgumentException("The Redis lifetime settings must and must not be 0");
        }

        // 使用php的序列化和反序列化进行缓存数据处理
        self::$serializer  = \Redis::SERIALIZER_PHP;

        // Use default DB.
        self::$_db          = (int)Property::nonExistsReturnZero(self::$config, 'REDIS_DB');

        // False is disable pConnect, true is enable pConnect, default enable pConnect.
        self::$persistent   = Property::isExists(self::$config, 'REDIS_PERSISTENT', self::$persistent);

        self::$prefix       = Property::nonExistsReturnNull(self::$config, 'REDIS_PREFIX');
        self::$maxSize      = (int)Property::nonExistsReturnZero(self::$config, 'MAX_SIZE');
    }

    /**
     * Get pool instance
     *
     * @return null|CoRedisPoole
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnSelectedException
     * @throws UnconnectedException
     */
    public static function getInstance() {
        if (self::$instance === null) {

            $pool = new CoRedisPoole();
            $pool->initialize();
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
     */
    public function recycle($connect) {
        $this->pool->push($connect);
    }
}
