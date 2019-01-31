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


use Exceptions\InvalidArgumentException;
use Exceptions\NotFoundException;
use Store\File\Sync\FileStore;

class Daemon
{

    private $pidFile        = '/tmp/pid/';  // Pid file dir path
    private $user           = 'www';
    private $fileName       = null;
    private $class          = null;         // Now run class
    private $action         = null;         // Now run action(method)
    private static $signal  = 0;            // Set current signal

    const   IS_DOWN         = 'The process is not running';
    const   IS_RUN          = 'The process is running';

    /**
     * Daemon constructor.
     * @param $class
     * @param $action
     * @param string $pidParams
     * @param null $user
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function __construct($class, $action, $pidParams = '', $user = null) {
        if(!is_object($class)) {
            throw new InvalidArgumentException('$class params type error, only for the object');
        }

        if(!method_exists($class, $action)) {
            throw new NotFoundException('$action on the $class');
        }

        if(!is_string($action)) {
            throw new InvalidArgumentException('$action params type error, only for the string');
        }

        $this->class    = $class;
        $this->action   = $action;

        // The pid file save path.
        $this->fileName = $this->pidFile . '/' . basename(get_class($class), '.php') . $pidParams . '.pid';
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
    public static function setSignal($signal){
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
     * Run current class
     */
    private function run(){

        while (true){
            $this->class->run();
        }
    }

    /**
     * Fork the user process become the daemon
     */
    private function daemonFork(){
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
    private function setUser(){
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
        FileStore::save($this->fileName, posix_getpid(), false, true);
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
    private function stop(){
        if (file_exists($this->fileName)) {
            $pid = FileStore::get($this->fileName);
            posix_kill($pid, SIGUSR1);
            return FileStore::deleteFile($this->fileName);
        }

        echo self::IS_DOWN . PHP_EOL;
        return false;
    }

    /**
     * Start the process
     */
    private function start(){
        if (file_exists($this->fileName)) {
            echo self::IS_RUN . PHP_EOL;
            exit(0);
        }

        $this->daemonFork();
        $this->createPidfile();
        $this->run();
    }

    /**
     * Restart the process
     */
    private function restart(){
        $this->stop();
        $this->start();
    }

    /**
     * Reload the process
     */
    private function reload(){
        if (file_exists($this->fileName)) {
            $pid = FileStore::get($this->fileName);
            posix_kill($pid, SIGHUP);
            return true;
        }

        echo self::IS_DOWN . PHP_EOL;
        return false;
    }

    /**
     * @param $action
     * @throws NotFoundException
     * @throws \Exceptions\UnExecutableException
     * @throws \Exceptions\UnReadableException
     * @throws \Exceptions\UnWritableException
     */
    public function main($action){
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
