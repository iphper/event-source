<?php

namespace EventSource;

use Swoole\Coroutine;
use Swoole\Http\Response;

class SwooleEventSource extends EventSource
{
    /**
     * @var Response $response
     */
    protected Response $response;

    public function __construct(Response $response, ...$args)
    {
        $this->response = $response;
        parent::__construct(...$args);
    }

    /**
     * @method initialize
     * @desciption 初始化方法
     */
    public function initialize()
    {
        foreach($this->headers as $field => $value) {
            $this->response->header($field, $value);
        }
    }

    /**
     * @overwite
     * @method write 写方法
     * @param $data 数据
     * @param $event 事件名称
     * @param $id 事件id
     * @param $retry 断开重连间隔时间
     * @return self
     */
    public function write($data = null, $event = null, $id = null, $retry = null) : self
    {
        if (! $this->event instanceof Event) {
            $this->setEvent(Event::make($id, $data, $event, $retry));
        } else {
            is_null($data) or $this->event->setData($data);
            is_null($event) or $this->event->setName($event);
            is_null($id) or $this->event->setId($id);
            is_null($retry) or $this->event->setRetry($retry);
        }
        $str = $this->event;

        $this->response->write($str);

        return $this;
    }

    // ====== static methods ======
    
    /**
     * @method make
     * @return self
     */
    public static function make() : self
    {
        return new static(...func_get_args());
    }

}
