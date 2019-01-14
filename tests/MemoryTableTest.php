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
use Services\Config;

/**
 * Class MemoryTableTest
 */
class MemoryTableTest extends \PHPUnit\Framework\TestCase
{

    public function testMemoryTableConfig() {
        $option = Config::memory()::get('table');
        $size   = Config::memory()::find('table')::next('size');
        $this->assertEquals($size, $option->size);

        $config = Config::memory()::find('schema')::next('id')::next('name');
        $this->assertEquals('fd', $config);
    }

    /**
     * @throws \Exceptions\NotFoundException
     * @throws \Exceptions\RequiredException
     */
    public function testCreateMemoryTable() {
        $option = Config::memory()::get('table');
        $memory = new MemoryTable($option);

        $memory->column('id')->type('int')->size(4);      // 整数形size: 1, 2, 4, 8, 设置为4字节
        $memory->column('name')->type('string')->size(64);// 字符串size设置为64字节
        // $memory->column('id')->type('float')->columnSize(8);          // 浮点数形size: 8, 默认为8字节
        $add = $memory->add();
        $this->assertTrue($add);

        $value  = [ 'id' => 1, 'uid' => 'uid' ];
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
