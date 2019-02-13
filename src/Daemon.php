<?php
/**
 * Class Daemon
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/31/19
 * Time:    3:53 PM
 */

namespace Services;


use Common\Strings;
use Exceptions\InvalidArgumentException;
use Exceptions\NotFoundException;
use Store\File\Sync\FileStore;

class Daemon
{

    private $pidFile        = '/tmp/pid/';  // Pid file dir path
    private $user           = 'www';
    private $fileName       = null;
    private $class          = null;         // current run class
    private $daemonName     = null;         // current run class name
    private $timer          = null;         // run timer
    private $timerAction    = null;         // run timer action
    private $running        = null;         // running flag
    private static $signal  = 0;            // Set current signal

    const   IS_DOWN         = 'The process is not running';
    const   IS_RUN          = 'The process is running';
    const   IS_STOP         = 'The process has stoped';
    const   IS_START        = 'The process has started';
    const   IS_RELOAD       = 'The process has reloaded';
    const   IS_FAILED       = 'the process failed';

    /**
     * Daemon constructor.
     *
     * Daemon constructor.
     * @param null $timer
     * @param null $timerAction
     * @param bool $running
     * @param null $user
     * @throws NotFoundException
     */
    public function __construct($timer = null, $timerAction = null, $running = false, $user = null) {

        $this->running      = $running;
        $this->timer        = $timer;
        $this->timerAction  = $timerAction;
        $this->class        = $timer->callClass;

        if(!property_exists($timer, 'callClass')) {
            throw new NotFoundException('callClass property on the Timer');
        }

        $this->daemonName   = Strings::trimStrString(get_class($this->class), '\\');

        // The pid file save path.
        $this->fileName = $this->pidFile . $this->daemonName . '.pid';
        // Set exec users, default to www.
        if ($user != null) {
            $this->user = $user;
        }

        $this->signal();
    }

    /**
     * Set current signal
     *
     * @param $signal
     */
    public static function setSignal($signal) {
        self::$signal = $signal;
    }

    /**
     * Get current signal
     *
     * @return int
     */
    public static function getSignal() {
        return(self::$signal);
    }

    /**
     * Reset current signal
     */
    public static function resetSignal() {
        self::$signal = 0;
    }

    /**
     * Get current signal and reset
     *
     * @return bool
     */
    public static function isReload() {
        $flag = false;
        if(Daemon::getSignal() == SIGHUP) {

            Daemon::resetSignal();
            $flag = true;
        }

        return $flag;
    }

    /**
     * Capturing current signal
     */
    private function signal() {
        pcntl_signal(SIGHUP, function($signal) {
            Daemon::setSignal($signal);
        });
    }

    /**
     * Run timer tick
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private function run() {

        if (!$this->running) {
            $this->timerTick();

        } else {
            while (true) {
                $this->timerTick();
            }
        }
    }

    /**
     * Run timer tick action
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private function timerTick() {
        if ($this->timer != null && $this->timerAction != null) {

            if(!method_exists($this->timer, $this->timerAction)) {
                throw new NotFoundException('$timerAction on the $timer');
            }

            $this->timer->{$this->timerAction}();

        } else {
            throw new InvalidArgumentException('timer or timerAction required');
        }
    }

    /**
     * Fork the user process become the daemon
     */
    private function daemonFork() {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Fork(1) failed!' . PHP_EOL);

        } elseif ($pid > 0) {
            // Exit the user process
            exit(0);
        }

        // Created of a new session from the terminal, and exit with the terminal
        if(!$this->setUser()) {
            posix_setsid();
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Fork(2) failed!' . PHP_EOL);

        } elseif ($pid > 0) {
            // The parent process exits, and the remaining child processes become the daemon process.
            exit(0);
        }
    }

    /**
     * Set the run of the user
     *
     * @return bool
     */
    private function setUser() {
        $result = false;
        if ($this->user == null){
            return true;
        }

        // Get user information for linux
        $user = posix_getpwnam($this->user);

        // If the user exits, then set the user information
        if ($user) {
            $uid    = $user['uid'];
            $gid    = $user['gid'];
            $result = posix_setuid($uid);
            posix_setgid($gid);
        }

        return $result;
    }

    /**
     * Create pid file
     *
     * @throws InvalidArgumentException
     * @throws \Exceptions\AlreadyExistsException
     * @throws \Exceptions\UnExecutableException
     * @throws \Exceptions\UnReadableException
     * @throws \Exceptions\UnWritableException
     */
    private function createPidfile() {
        return FileStore::save($this->fileName, posix_getpid(), false, true);
    }

    /**
     * Stop the process
     *
     * @return bool
     * @throws NotFoundException
     * @throws \Exceptions\UnExecutableException
     * @throws \Exceptions\UnReadableException
     * @throws \Exceptions\UnWritableException
     */
    private function stop() {
        if (FileStore::checkFile($this->fileName)) {

            $pid = FileStore::get($this->fileName);
            posix_kill($pid, SIGUSR1);
            $del = FileStore::deleteFile($this->fileName);

            if ($del) {
                echo $this->daemonName . ': ' . self::IS_STOP . PHP_EOL;
                return true;

            } else {
                echo $this->daemonName . ': ' . 'Stop ' . self::IS_FAILED . PHP_EOL;
            }

            return false;
        }

        echo $this->daemonName . ': ' . self::IS_DOWN . PHP_EOL;
        return false;
    }

    /**
     * Start the process
     */
    private function start() {
        if (FileStore::checkFile($this->fileName)) {

            echo $this->daemonName . ': ' . self::IS_RUN . PHP_EOL;
            exit(0);
        }

        $this->daemonFork();
        $pid = $this->createPidfile();

        if ($pid) {
            $this->run();
            echo $this->daemonName . ': ' . self::IS_START . PHP_EOL;

        } else {
            echo $this->daemonName . ': ' . 'Start ' . self::IS_FAILED . PHP_EOL;
        }
    }

    /**
     * Restart the process
     */
    private function restart() {
        $this->stop();
        $this->start();
    }

    /**
     * Reload the process
     */
    private function reload() {
        if (FileStore::checkFile($this->fileName)) {

            $pid    = FileStore::get($this->fileName);
            $reload = posix_kill($pid, SIGHUP);

            if ($reload) {
                echo $this->daemonName . ': ' . self::IS_RELOAD. PHP_EOL;
                return true;

            } else {
                echo $this->daemonName . ': ' . 'Reload ' . self::IS_FAILED . PHP_EOL;
            }

            return false;
        }

        echo $this->daemonName . ': ' . self::IS_DOWN . PHP_EOL;
        return false;
    }

    /**
     * @param $action
     * @throws NotFoundException
     * @throws \Exceptions\UnExecutableException
     * @throws \Exceptions\UnReadableException
     * @throws \Exceptions\UnWritableException
     */
    public function call($action) {
        switch ($action){
            case 'start':
                $this->start();
                break;

            case 'stop':
                $this->stop();
                break;

            case 'restart':
                $this->restart();
                break;

            case 'reload':
                $this->reload();
                break;

            default :
                throw new NotFoundException('The action: ' . $action);
        }
    }
}
