<?php
/**
 * Class Events
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/12/19
 * Time:    7:17 PM
 */

namespace Services;

class Event
{

    public static function onStart($frame) {
    }

    public static function onWorkerStart() {
    }

    public static function onWorkerStop() {
    }

    public static function onTask() {
    }

    public static function onFinish() {
    }

    public static function onPipeMessage() {
    }

    public static function onWorkerError() {
    }

    public static function onManagerStart() {
    }

    public static function onManagerStop() {
    }

    public static function onRequest() {
    }

    public static function onWSOpen() {
    }

    public static function onWSMessage($serv, $frame) {
    }

    public static function onWSClose($service, $frame) {
    }

    public static function onShutdown() {
    }
}
