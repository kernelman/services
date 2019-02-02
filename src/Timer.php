<?php
/**
 * Class Timer
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    2/2/19
 * Time:    10:49 AM
 */

namespace Services;


use Exceptions\InvalidArgumentException;

class Timer
{
    private $callback;

    /**
     * Timer constructor.
     * @param $callback
     */
    public function __construct($callback) {
        $this->callback = $callback;
    }

    /**
     * @param int $second
     * @throws InvalidArgumentException
     */
    public function second(int $second) {
        if ($second > 0 ) {
            sleep($second);
            call_user_func($this->callback);
        }

        throw new InvalidArgumentException('$second error: ' . $second);
    }

    /**
     * @param int $second
     */
    public function unlimited($second = 0) {
        if ($second == 0) {
            call_user_func($this->callback);
        }
    }
}
