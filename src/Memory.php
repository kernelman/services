<?php
/**
 * Created by IntelliJ IDEA.
 * User: kernel Huang
 * Email: kernelman79@gmail.com
 * Date: 2018/11/22
 * Time: 9:50 AM
 */

namespace Services;

use Common\Bytes;

class Memory {

    private $start  = null;
    private $end    = null;
    private $use    = null;

    /**
     * 程序开始时的内存占用
     */
    public function start() {
        $this->start = memory_get_usage(true);
    }

    /**
     * 程序结束时的内存占用
     */
    public function end() {
        $this->end = memory_get_usage(true);
    }

    /**
     * 获取程序内存占用大小
     *
     * @return int|null
     */
    public function get() {
        if ($this->start != null && $this->end != null) {
            $this->use = $this->end - $this->start;

        } else {
            $this->use = 0;
        }

        return $this->use;
    }

    /**
     * 单位转化
     * @return mixed
     */
    public function toBytes() {
        return Bytes::Byte($this->use)::toByte();
    }
}
