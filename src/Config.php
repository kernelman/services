<?php
/**
 * Created by IntelliJ IDEA.
 * User: kernel Huang
 * Email: kernelman79@gmail.com
 * Date: 2018/11/21
 * Time: 3:01 PM
 */

namespace Services;

use Exceptions\InvalidArgumentException;
use Exceptions\NotFoundException;
use Files\Sync\FileStore;
use Common\Property;

/**
 * 获取配置文件
 *
 * Class Config
 * @package Processor
 */
class Config {

    const CONFIG                = ' config';
    const PROPERTY_AT           = ' property at ';

    private static $config      = null;
    private static $configName  = null;
    private static $parent      = null;

    /**
     * 通过配置名称加载配置文件
     *
     * @param $name // 配置文件前缀名
     * @param $arguments
     * @return string
     */
    public static function __callStatic($name, $arguments) {
        self::$configName  = null;
        self::$configName  = strtolower($name);

        try {

            $path = self::getDefinedPath();
            if (FileStore::checkFile($path)) {

                self::$config = include $path;
                if (!is_object(self::$config)) {
                    throw new InvalidArgumentException(self::$configName . ' value must be the object');
                }
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        return self::class;
    }

    /**
     * 获取配置文件的所有属性
     *
     * @return null
     * @throws NotFoundException
     */
    public static function got() {
        try {

            if (self::$config != null) {
                return self::$config;
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        throw new NotFoundException(self::$configName . self::CONFIG);
    }

    /**
     * 获取配置的某个属性值
     *
     * @param $property
     * @return mixed
     * @throws NotFoundException
     */
    public static function get($property) {
        try {

            return Property::reality(self::$config->{$property});

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        throw new NotFoundException($property . self::PROPERTY_AT . self::$configName . self::CONFIG);
    }

    /**
     * 获取配置属性的根字段, 并根据字段类型返回相应值.
     *
     * @param $property
     * @return string
     * @throws NotFoundException
     */
    public static function find($property) {
        try {

            $result = Property::reality(self::$config->{$property});
            switch ($result) {

                case is_object($result):
                    self::$parent = null;
                    self::$parent = $result;
                    return self::class;

                case is_array($result):
                    self::$parent = null;
                    self::$parent = (object)$result;
                    return self::class;

                default:
                    return $result;
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        throw new NotFoundException($property . self::PROPERTY_AT . self::$configName . self::CONFIG);
    }

    /**
     * 获取配置属性字段的子字段
     *
     * @param $property
     * @return string
     * @throws NotFoundException
     */
    public static function next($property) {
        try {

            $result = Property::reality(self::$parent->{$property});
            switch ($result) {

                case is_object($result):
                    self::$parent = null;
                    self::$parent = $result;
                    return self::class;

                case is_array($result):
                    self::$parent = null;
                    self::$parent = (object)$result;
                    return self::class;

                default:
                    return $result;
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        throw new NotFoundException($property . self::PROPERTY_AT . self::$configName . self::CONFIG);
    }

    /**
     * Get the configuration path is defined
     *
     * @return string
     */
    private static function getDefinedPath() {

        if (defined('CONFIG_PATH')) {
            return CONFIG_PATH . self::$configName . '.php';
        }

        return dirname(__DIR__) . '/config/' . self::$configName . '.php';
    }
}
