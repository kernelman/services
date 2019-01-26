<?php
/**
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/12/19
 * Time:    5:56 PM
 */

require_once BASE_PATH . '/index.php';

use Services\Config;
use Services\WebSocket;
use Services\Event;

$option = Config::websocket()::got();

WebSocket::$event = new Event();
WebSocket::start($option);
