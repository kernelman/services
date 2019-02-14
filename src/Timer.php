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


use Common\TimeRelated;
use Exceptions\NotFoundException;

class Timer
{

    private $callback;
    private $times;
    private $min;
    private $sec;

    public $callClass;
    public $after       = null;
    public $asyncTimer  = true;
    public $coRoutine   = true;

    /**
     * Timer constructor.
     *
     * Timer constructor.
     * @param $after
     * @param $callClass
     * @param $callback
     * @param int $times
     * @param int $min
     * @param int $sec
     * @throws NotFoundException
     */
    public function __construct($after, $callClass, $callback, $times = 0, $min = 0, $sec = 0) {
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        $this->min      = $min;
        $this->sec      = $sec;
        $this->times    = $times;
        $this->after    = $after;
        $this->callback = $callback;
        $this->callClass= $callClass;
    }

    /**
     * Call callback
     *
     */
    private function call() {
        $this->callClass->{$this->callback}();
    }

    /**
     * Only run once.
     *
     */
    public function once() {

        if ($this->coRoutine) {

            go(function () {
                $this->call();
            });

        } else {
            $this->call();
        }
    }

    /**
     * Run to scheduled the time values.
     *
     */
    public function sleeps() {
        if ($this->times > 0 ) {

            if ($this->asyncTimer) {
                $this->trigger(null, 'call', $this->times);

            } else {
                $this->runTick('once', $this->times);
            }
        }
    }

    /**
     * Scheduled every hour.
     *
     * @return bool
     */
    private function onHourlies() {
        $now = TimeRelated::current();

        if ($this->times == 0) {
            // Early hour time.
            $hourly = TimeRelated::hourlyFirst();

        } elseif ($this->times == 59) {
            // End hour time.
            $hourly = TimeRelated::hourlyLast();

        } else {
            $hourly = TimeRelated::hourly($this->min, $this->sec);
        }

        if (array_search($now, $hourly) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Scheduled every hour.
     *
     */
    public function hourlies() {
        $this->trigger('onHourlies', 'call');
    }

    /**
     * Scheduled any hour.
     *
     * @return bool
     */
    private function onHourlyAny() {
        $now    = TimeRelated::current();
        $hourly = TimeRelated::hourlyAny($this->times, $this->min, $this->sec);

        if ($now == $hourly) {
            return true;
        }

        return false;
    }

    /**
     * Scheduled any hour.
     *
     */
    public function hourlyAny() {
        $this->trigger('onHourlyAny', 'call');
    }

    /**
     * Scheduled any point.
     *
     * @return bool
     */
    private function onPoint() {
        $now = TimeRelated::current();

        if ($this->times == $now) {
            return true;
        }

        return false;
    }

    /**
     * Scheduled any point.
     *
     */
    public function point() {
        $this->trigger('onPoint', 'call');
    }

    /**
     * Scheduled every point.
     *
     */
    public function todayPoint() {
        $point = TimeRelated::todayAny($this->times, $this->min, $this->sec);
        $this->times = $point;
        $this->point();
    }

    /**
     * Scheduled every week.
     *
     * @return bool
     */
    private function onWeek() {
        $now = TimeRelated::current();
        return $this->getTick($now, TimeRelated::weekFirst(), TimeRelated::weekLast());
    }

    /**
     * Scheduled every week.
     *
     */
    public function weekly() {
        $this->trigger('onWeek', 'call');
    }

    /**
     * Scheduled any time in a week.
     *
     */
    public function weeklyAny() {
        $weekTime = 7 * 24 * 60 * 60;
        $this->runTick('call', $weekTime);
    }

    /**
     * Scheduled every month.
     *
     * @return bool
     */
    private function onMonth() {
        $now = TimeRelated::current();
        return $this->getTick($now, TimeRelated::monthFirst(), TimeRelated::monthLast());
    }

    /**
     * Scheduled every month.
     *
     */
    public function monthly() {
        $this->trigger('onMonth', 'call');
    }

    /**
     * Scheduled every year.
     *
     * @return bool
     */
    private function onYear() {
        $now = TimeRelated::current();
        return $this->getTick($now, TimeRelated::yearFirst(), TimeRelated::yearLast());
    }

    /**
     * Scheduled every year.
     *
     */
    public function yearly() {
        $this->trigger('onYear', 'call');
    }

    /**
     * Get Early time
     *
     * @param $first
     * @return bool
     */
    private function isFirst($first) {

        if ($this->times == 0) {
            return $first;
        }

        return false;
    }

    /**
     * Get End time
     *
     * @param $last
     * @return bool
     */
    private function isLast($last) {
        if ($this->times != 0) {
            return $last;
        }

        return false;
    }

    /**
     * Get tick
     *
     * @param $now
     * @param $first
     * @param $last
     * @return bool
     */
    private function getTick($now, $first, $last) {
        $tick = $this->isFirst($first) ?? $this->isLast($last);

        if ($now == $tick) {
            return true;
        }

        return false;
    }

    /**
     * Run tick
     *
     * @param string $call Callback name
     * @param int $stopwatch
     */
    private function runTick(string $call, $stopwatch) {
        if ($this->asyncTimer) {

            \swoole_timer_tick($stopwatch * 1000, function() use ($call) {
                $this->{$call}();
            });

        } else {

            while (true) {
                sleep($stopwatch);
                $this->{$call}();
            }
        }
    }

    /**
     * @param $callFirst
     * @param $callLast
     * @param int $stopwatch
     */
    private function trigger(string $callFirst = null, string $callLast, $stopwatch = 1) {

        if ($this->asyncTimer) {

            if ($this->after === null) {
                \swoole_timer_tick($stopwatch * 1000, function() use ($callFirst, $callLast) {

                    if ($callFirst == null) {
                        $this->{$callLast}();

                    } else {

                        if($this->{$callFirst}()) {
                            $this->{$callLast}();
                        }
                    }
                });

            }  else {
                $this->triggerAfter($callFirst, $callLast, $stopwatch);
            }

        } else {

            while (true) {
                sleep($stopwatch);

                if ($callFirst == null) {
                    $this->{$callLast}();

                } else {

                    if($this->{$callFirst}()) {
                        $this->{$callLast}();
                    }
                }
            }
        }
    }

    /**
     * @param string|null $callFirst
     * @param string $callLast
     * @param int $stopwatch
     */
    private function triggerAfter(string $callFirst = null, string $callLast, $stopwatch = 1) {
        if ($callFirst == null) {

            \swoole_timer_after($this->after * 1000, function() use ($callLast) {
                $this->{$callLast}();
            });

            \swoole_timer_tick($stopwatch * 1000, function() use ($callLast) {
                $this->{$callLast}();
            });

        } else {

            \swoole_timer_after($this->after * 1000, function() use ($callLast) {
                $this->{$callLast}();
            });

            \swoole_timer_tick($stopwatch * 1000, function() use ($callFirst, $callLast) {

                if($this->{$callFirst}()) {
                    $this->{$callLast}();
                }
            });
        }
    }
}
