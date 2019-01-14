<?php
/**
 * Class MemoryTable
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/13/19
 * Time:    7:43 PM
 */

namespace Services;


use Common\Property;
use Exceptions\NotFoundException;
use Exceptions\RequiredException;

/**
 * Class MemoryTable
 * @package Services
 */
class MemoryTable
{
    public $table   = null;
    private $atomic = null;
    private $size   = 2048;
    private $name   = null;
    private $type   = null;
    private $types  = [
        'int'       => \swoole_table::TYPE_INT,
        'float'     => \swoole_table::TYPE_FLOAT,
        'string'    => \swoole_table::TYPE_STRING
    ];

    /**
     * MemoryTable constructor.
     *
     * @param null $option, $option->size Defined the max size by table
     * @throws NotFoundException
     */
    public function __construct($option = null) {
        // Check swoole extension
        if (!extension_loaded('swoole')) {
            throw new NotFoundException('The swoole extension can not loaded.');
        }

        if ($option != null && is_object($option) && Property::reality($option->size)) {
            $this->size = $option->size;
        }

        $this->table = new \swoole_table($this->size);
    }

    /**
     * Set column size.
     *
     * @param null $size
     * @throws RequiredException
     */
    public function size($size = null) {
        if ($this->type == null) {
           throw new RequiredException('$this->type.');
        }

        $this->table->column($this->name, $this->type, $size);
    }

    /**
     * Create memory table
     *
     * @return mixed
     */
    public function add() {
        return $this->table->create();
    }

    /**
     * Set key and value to memory table
     *
     * @param $key
     * @param array $value
     * @return mixed
     */
    public function set($key, array $value) {
        return $this->table->set($key, $value);
    }

    /**
     * Get memory table data
     *
     * @param $key
     * @return mixed
     */
    public function get($key) {
        return $this->table->get($key);
    }

    /**
     * Number of rows for memory table
     *
     * @return mixed
     */
    public function count() {
        return $this->table->count();
    }

    /**
     * Set type of field for memory table
     *
     * @param $type
     * @return $this
     */
    public function type($type) {
        $this->type = Property::nonExistsReturnNull((object)$this->types, $type);
        return $this;
    }

    /**
     * Set the column name
     *
     * @param $name
     * @return $this
     */
    public function column($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Add atomic
     *
     * @param $value
     * @return $this
     */
    public function addAtomic($value) {
        $this->atomic = new \swoole_atomic($value);
        return $this;
    }
}
