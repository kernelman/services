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
    public $asyncTimer = true;
    public $coroutine  = true;

    /**
     * Timer constructor.
     *
     * Timer constructor.
     * @param $callClass
     * @param $callback
     * @param int $times
     * @param int $min
     * @param int $sec
     * @throws NotFoundException
     */
    public function __construct($callClass, $callback, $times = 0, $min = 0, $sec = 0) {
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        $this->min      = $min;
        $this->sec      = $sec;
        $this->times    = $times;
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

        if ($this->coroutine) {

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
                $this->runTick($this->call(), $this->times);

            } else {
                $this->runTick($this->once(), $this->times);
            }
        }
    }

    /**
     * Scheduled every hour.
     *
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
            $this->call();
        }
    }

    /**
     * Scheduled every hour.
     *
     */
    public function hourlies() {
        $this->runTick($this->onHourlies());
    }

    /**
     * Scheduled any hour.
     *
     */
    private function onHourlyAny() {
        $now    = TimeRelated::current();
        $hourly = TimeRelated::hourlyAny($this->times, $this->min, $this->sec);

        if ($now == $hourly) {
            $this->call();
        }
    }

    /**
     * Scheduled any hour.
     *
     */
    public function hourlyAny() {
        $this->runTick($this->onHourlyAny());
    }

    /**
     * Scheduled any point.
     *
     */
    private function onPoint() {
        $now = TimeRelated::current();

        if ($this->times == $now) {
            $this->call();
        }
    }

    /**
     * Scheduled any point.
     *
     */
    public function point() {
        $this->runTick($this->onPoint());
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
     */
    private function onWeek() {
        $now = TimeRelated::current();
        $this->getTick($now, TimeRelated::weekFirst(), TimeRelated::weekLast());
    }

    /**
     * Scheduled every week.
     *
     */
    public function weekly() {
        $this->runTick($this->onWeek());
    }

    /**
     * Scheduled any time in a week.
     *
     */
    public function weeklyAny() {
        $weekTime = 7 * 24 * 60 * 60;
        $this->runTick($this->call(), $weekTime);
    }

    /**
     * Scheduled every month.
     *
     */
    private function onMonth() {
        $now = TimeRelated::current();
        $this->getTick($now, TimeRelated::monthFirst(), TimeRelated::monthLast());
    }

    /**
     * Scheduled every month.
     *
     */
    public function monthly() {
        $this->runTick($this->onMonth());
    }

    /**
     * Scheduled every year.
     *
     */
    private function onYear() {
        $now = TimeRelated::current();
        $this->getTick($now, TimeRelated::yearFirst(), TimeRelated::yearLast());
    }

    /**
     * Scheduled every year.
     *
     */
    public function yearly() {
        $this->runTick($this->onYear());
    }

    /**
     * Get Early time
     *
     * @param $first
     * @return bool
     */
    private function isFirst($first) {

        if ($this->times == 0) {
            return $first();
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
            return $last();
        }

        return false;
    }

    /**
     * Get tick
     *
     * @param $now
     * @param $first
     * @param $last
     */
    private function getTick($now, $first, $last) {
        $tick = $this->isFirst($first) ?? $this->isLast($last);

        if ($now == $tick) {
            $this->call();
        }
    }

    /**
     * Run tick
     *
     * @param $callback
     * @param int $stopwatch
     */
    public function runTick($callback, $stopwatch = 1) {
        if ($this->asyncTimer) {

            \swoole_timer_tick($stopwatch * 1000, function() use($callback) {
                $callback();
            });

        } else {

            while (true) {
                sleep($stopwatch);
                $callback();
            }
        }
    }
}
