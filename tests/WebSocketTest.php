<?php
/**
 * Class MemoryTableTest
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/14/19
 * Time:    11:05 AM
 */

use Services\WebSocket;

/**
 * Class MemoryTableTest
 */
class WebSocketTest extends \PHPUnit\Framework\TestCase
{

    const KEY   = 'kernel';

    /**
     * @throws \Exceptions\NotFoundException
     * @throws \Exceptions\RequiredException
     */
    public function testBeforeStart() {
        WebSocket::beforeStart();

        $value  = [ 'fd' => 1, 'uid' => 'pid' ];
        $set    = WebSocket::$memory->table->set(self::KEY, $value);

        $this->assertTrue($set);

        $data = WebSocket::$memory->get(self::KEY);
        WebSocket::$memory->table->del(self::KEY);
        WebSocket::$memory->table->destroy();

        $this->assertEquals($data, $value);
    }
}
