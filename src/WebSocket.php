<?php
/**
 * Class WebSocket
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/11/19
 * Time:    5:47 PM
 */

namespace Services;

use Common\Property;
use Exceptions\InvalidArgumentException;
use Exceptions\NotFoundException;
use Exceptions\UnconnectedException;

/**
 * WebSocket services
 *
 * Class WebSocket
 * @package Services
 */
class WebSocket
{
    public static $event        = null;
    public static $memory       = null;

    private static $host        = null;
    private static $port        = null;
    private static $service      = null;
    private static $handshake   = null;

    /**
     * Initialize
     *
     * @param $option
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private static function initialize($option) {

        // Check swoole extension
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        if (Property::nonExistsReturnNull($option, 'set') == null) {
            throw new InvalidArgumentException("The set property not found");
        }

        self::$host     = Property::nonExistsReturnNull($option->set, 'host');
        if (self::$host == null) {
            throw new InvalidArgumentException("The WebSocket service host settings not found");
        }

        self::$port     = Property::nonExistsReturnZero($option->set, 'port');
        if (self::$port == 0) {
            throw new InvalidArgumentException("The WebSocket service host settings not found");
        }

        self::$handshake = Property::nonExistsReturnNull($option->set, 'handshake');
    }

    /**
     * Start WebSocket service
     *
     * @param $option
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnconnectedException
     * @throws \Exceptions\RequiredException
     */
    public static function start($option) {

        self::initialize($option);
        self::$service = self::server();
        self::listen($option);
        self::set($option);
        self::beforeStart();
        self::$service->start();
    }

    /**
     * Open server for WebSocket service
     *
     * @return \Swoole\WebSocket\Server
     * @throws UnconnectedException
     */
    private static function server() {
        $service = new \Swoole\WebSocket\Server(self::$host, self::$port);
        if ($service) {
            return $service;
        }

        throw new UnconnectedException('WebSocket Server start failed: ' . self::$host . ':'. self::$port );
    }

    /**
     * Set the WebSocket Server behavior.
     *
     * @param $option
     */
    private static function set($option) {
        $set = $option->set;

        self::$service->set(array(
            'worker_num'                => $set->workers,
            'daemonize'                 => $set->daemon,
            'open_eof_split'            => $set->eofSplit,
            'package_eof'               => $set->packageEof,
            'open_length_check'         => $set->lengthCheck,
            'package_max_length'        => $set->packageMaxLength,
            'package_length_type'       => $set->packageLengthType,
            'package_length_offset'     => $set->packageLengthOffset,
            'package_body_offset'       => $set->packageBodyOffset,
        ));
    }

    /**
     * Listen method for event object
     *
     * @param $option
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private static function listen($option) {
        if (!is_object(self::$event)) {
            throw new InvalidArgumentException('Event object not found.');
        }

        if (Property::nonExistsReturnNull($option, 'listen') == null) {
            throw new InvalidArgumentException("The listen property not found");
        }

        foreach ($option->listen as $key => $value) {
            $methodList = [ WebSocket::$event, $value ];

            if (is_callable($methodList)) {
                self::$service->on($key, $methodList);

            } else {
                throw new NotFoundException('Cannot call method: ' . $value . ' on class.');
            }
        }

        if (self::$handshake) {
            self::$service->on('handshake', [WebSocket::class, 'onWSHandShake']);
        }
    }

    /**
     * Before starting the WebSocket service to create memory table.
     *
     * @throws NotFoundException
     * @throws \Exceptions\RequiredException
     */
    public static function beforeStart() {

        $option = Config::memory();
        $table  = $option::get('table');
        $schema = $option::get('schema');

        self::$memory = new MemoryTable($table);

        foreach ($schema as $value) {
            self::$memory->column($value->name)->type($value->type)->size($value->size);
        }

        self::$memory->add();
    }
}
