<?php

namespace EventSource;

use Closure;

/**
 * @class EventSource
 */
class EventSource
{
    /**
     * 是否完成事件
     * @var bool $done
     */
    protected bool $done = false;

    /**
     * @var Event $event 事件对象
     */
    protected $event = null;

    /**
     * 连接关闭后是否立即停止脚本运行
     * @var bool $ignoreUserAbort
     */
    protected $ignoreUserAbort = true;

    /**
     * @var array $header 响应头列表
     */
    protected $headers = [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ];

    /**
     * @method __construct
     * @desciption 构造方法
     */
    public function __construct()
    {
        $args = func_get_args();
        $argn = func_num_args();
        
        switch(true) {
            case $argn == 1 && $args[0] instanceof Event:
                $this->setEvent($args[0]);
                break;
            case $argn > 0:
                $this->setEvent(Event::make(...$args));
                break;
        }

        $this->initialize();
    }

    /**
     * @method initialize
     * @desciption 初始化方法
     */
    public function initialize()
    {
        $this->setIgnoreUserAbort($this->ignoreUserAbort);
        foreach($this->headers as $field => $value) {
            header($field .': '. $value);
        }
    }

    /**
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

        echo $str;

        ob_end_flush();
        flush();
        return $this;
    }

    /**
     * @method json
     * @param $data 数据
     * @param $event 事件名称
     * @param $id 事件id
     * @param $retry 断开重连间隔时间
     * @return self
     */
    public function json($data = null, ...$argv) : self
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        return $this->write($data, ...$argv);
    }

    /**
     * @method aborted
     * @desciption 是否已断开连接
     * @return bool
     */
    public function aborted() : bool
    {
        return $this->done || connection_aborted();
    }

    /**
     * @method setIgnoreUserAbort
     * @desciption 设置连接关闭后是否立即停止脚本
     * @param bool $ignoreUserAbort
     * @return self
     */
    public function setIgnoreUserAbort(bool $ignoreUserAbort) : self
    {
        $this->ignoreUserAbort = $ignoreUserAbort;
        ignore_user_abort($this->ignoreUserAbort);
        return $this;
    }

    /**
     * @method done
     * @desciption 完成
     * @return void
     */
    public function done()
    {
        $this->setDone(true);
    }

    // ====== setter methods ======
    /**
     * @method setEvent
     * @desciption 设置事件对象
     * @return self
     */
    public function setEvent(Event $event) : self
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @method setDone
     * @param bool $done
     * @return self
     */
    public function setDone(bool $done) : self
    {
        $this->done = $done;
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

    /**
     * @method handle静态处理操作
     * @param Closure $loopCallback 循环回调
     * @param int $uSleepTime 等待时间[微秒]
     * @param ?Closure $stopCallback 结束回调
     * @return void
     */
    public static function handle(Closure $loopCallback, int $uSleepTime, ?Closure $stopCallback) : void
    {
        $event = static::make();

        while(true) {
            try {
                // 调用回调函数
                $status = call_user_func($loopCallback, $event, $uSleepTime);
            } catch(\Exception $e) {
                $status = false;
            }

            // 结束判定
            if ($status === false || $event->aborted()) {
                call_user_func($stopCallback, $event, $uSleepTime);
                break;
            }

            // 毫秒级停顿
            usleep($uSleepTime);
        }

    }

}
