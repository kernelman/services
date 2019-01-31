<?php
/**
 * Class CommandLineApp
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/31/19
 * Time:    12:15 PM
 */

namespace Services;


use Common\Property;

/**
 * Class CommandLineApp
 * Command line application
 *
 * @package Services
 */
class CommandLineApp
{
    public $cli     = null;
    public $task    = null;
    public $action  = null;
    public $params  = null;

    /**
     * CommandLineApp constructor.
     * @param $arguments
     */
    public function __construct($arguments) {
        if (PHP_SAPI == 'cli') {
            set_time_limit(0);
        }

        foreach ($arguments as $key => $arg) {
            $this->getCli($key, $arg);
            $this->getTask($key, $arg);
            $this->getAction($key, $arg);
            $this->getParam($key, $arg);
        }

        $this->defined($arguments);
    }

    /**
     * Define global constants for the current task and action and cli.
     *
     * @param $arguments
     */
    public function defined($arguments) {
        define('CURRENT_CLI',    Property::notExistsReturnNull($arguments[0])); // Defined cli.php file
        define('CURRENT_TASK',   Property::notExistsReturnNull($arguments[1]));
        define('CURRENT_ACTION', Property::notExistsReturnNull($arguments[2]));
    }

    /**
     * @param $key
     * @param $arg
     */
    public function getCli($key, $arg) {
        if ($key == 0) {
            $this->cli = $arg;
        }
    }

    /**
     * @param $key
     * @param $arg
     */
    public function getTask($key, $arg) {
        if ($key == 1) {
            $this->task = $arg;
        }
    }

    /**
     * @param $key
     * @param $arg
     */
    public function getAction($key, $arg) {
        if ($key == 2) {
            $this->action = $arg;
        }
    }

    /**
     * @param $key
     * @param $arg
     */
    public function getParam($key, $arg) {
        if ($key >= 3) {
            $this->params = $arg;
        }
    }
}
