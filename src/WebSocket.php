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
    private static $host        = null;
    private static $port        = null;
    private static $server      = null;
    private static $handshake   = null;
    public static $event        = null;

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
            throw new InvalidArgumentException("The WebSocket server host settings not found");
        }

        self::$port     = Property::nonExistsReturnZero($option->set, 'port');
        if (self::$port == 0) {
            throw new InvalidArgumentException("The WebSocket server host settings not found");
        }

        self::$handshake = Property::nonExistsReturnNull($option->set, 'handshake');
    }

    /**
     * Start WebSocket server
     *
     * @param $option
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnconnectedException
     */
    public static function start($option) {

        self::initialize($option);
        self::$server = self::Service();
        self::listen($option);
        self::set($option);
        self::$server->start();
    }

    /**
     * Open service for WebSocket server
     *
     * @return \Swoole\WebSocket\Server
     * @throws UnconnectedException
     */
    private static function Service() {
        $server = new \Swoole\WebSocket\Server(self::$host, self::$port);
        if ($server) {
            return $server;
        }

        throw new UnconnectedException('WebSocket Server: ' . self::$host . ':'. self::$port );
    }

    /**
     * Set the WebSocket Server behavior.
     *
     * @param $option
     */
    private static function set($option) {
        $set = $option->set;

        self::$server->set(array(
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
            $methodList = [WebSocket::$event, $value];

            if (is_callable($methodList)) {
                self::$server->on($key, $methodList);

            } else {
                throw new NotFoundException('Cannot call method: ' . $value . ' on class.');
            }
        }

        if (self::$handshake) {
            self::$server->on('handshake', [WebSocket::class, 'onWSHandShake']);
        }
    }
}
