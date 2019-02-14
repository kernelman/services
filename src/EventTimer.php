<?php
/**
 * Class EventTimer
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    2/14/19
 * Time:    8:29 PM
 */

namespace Services;


use Exceptions\NotFoundException;

class EventTimer
{

    public $after = 0;

    public function __construct() {
        if (!extension_loaded('ev')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }
    }

    public function after(string $callFirst = null, string $callLast, $stopwatch = 1) {
        if ($callFirst === null) {

            $event = new \EvTimer($this->after, $stopwatch, function() use ($callLast) {
               $callLast();
            });

            \Ev::run();

        } else {

            if($callFirst()) {
                $event = new \EvTimer($this->after, $stopwatch, function() use ($callFirst, $callLast) {
                    if ($callFirst()) {
                        $callLast();
                    }
                });

                \Ev::run();
            }
        }
    }
}
