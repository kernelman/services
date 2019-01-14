<?php
/**
 * Class MemoryTableTest
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/14/19
 * Time:    11:05 AM
 */

use Services\MemoryTable;

/**
 * Class MemoryTableTest
 */
class MemoryTableTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @throws \Exceptions\NotFoundException
     * @throws \Exceptions\RequiredException
     */
    public function testCreateMemoryTable() {
        $option = require_once dirname(dirname(__FILE__)) . '/config/memory.php';

        $memory = new MemoryTable($option->table);
        $memory->column('id')->type('int')->columnSize(4);      // 整数形size: 1, 2, 4, 8, 设置为4字节
        $memory->column('name')->type('string')->columnSize(64);// 字符串size设置为64字节
        // $memory->column('id')->type('float')->columnSize(8);                // 浮点数形size: 8, 默认为8字节
        $add = $memory->table->create();
        $this->assertTrue($add);

        $value  = [ 'id' => 1, 'name' => 'pid' ];
        $set    = $memory->table->set('kernel', $value);
        $this->assertTrue($set);

        $data = $memory->table->get('kernel');
        $this->assertEquals($data, $value);

        $count = $memory->table->count();
        $this->assertEquals($count, 1);

        $memory->table->del('kernel');
        $memory->table->destroy();
    }
}
