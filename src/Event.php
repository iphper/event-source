<?php

namespace EventSource;

use \Closure;
use EventSource\Exception\EventRetryException;

/**
 * @class Event
 * @desciption 事件类
 * 
 */
class Event
{
    /**
     * 事件名称
     */
    protected $name;
    
    /**
     * 客户端重连等待时间【毫秒】
     * @var $retry
     */
    protected $retry = 1000;

    /**
     * 事件ID
     * @var id
     */
    protected $id = null;

    /**
     * 数据
     * @var $data
     */
    protected $data;

    /**
     * 构造方法
     */
    public function __construct($id = null, $data = null, $name = 'message', $retry = 0)
    {
        is_null($id) or $this->setId($id);
        is_null($data) or $this->setData($data);
        is_null($name) or $this->setName($name);
        $retry == 0 or $this->setRetry($retry);
    }

    /**
     * 生成ID方法
     */
    public function makeId() : string
    {
        return uniqid();
    }

    // ====== setter methods ======
    /**
     * @method setId
     * @param $id
     * @return self
     */
    public function setId($id) : self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @method setData
     * @param $data
     * @return self
     */
    public function setData($data) : self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @method setName
     * @param $name
     * @return self
     */
    public function setName($name) : self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @method setRetry
     * @param $retry
     * @return self
     */
    public function setRetry($retry) : self
    {
        if ($retry < 0) {
            throw new EventRetryException('The retry time cannot be less than 0');
        }
        $this->retry = $retry;
        return $this;
    }

    // ====== magic methods ======
    /**
     * @method __toString
     * @return string
     */
    public function __toString() : string
    {
        foreach(get_object_vars($this) as $protry => $val) {
            if ($val instanceof Closure) {
                $this->$protry = call_user_func($val);
            }
        }

        if(is_null($this->id)) {
            $this->setId($this->makeId());
        }

        return sprintf("retry: %s\nid: %s\nevent: %s\ndata: %s\n\n", $this->retry, $this->id, $this->name, $this->data);
    }

    // ====== static methods ======
    /**
     * 创建事件对象
     */
    public static function make() : self
    {
        return new static(...func_get_args());
    }

}
