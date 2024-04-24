# event-source
event-source 是简单的PHP服务端对于```SSE(Server-Sent Events)```的封装

##安装
```sh
composer require iphper/event-source
```

## 使用示例

### html
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>event-srouce-example</title>
</head>
<body>
    <button id="beginBtn">开始获取</button>
    <ul id="list"></ul>
</body>
</html>
```

### js
```js
let listDom = document.getElementById('list');
document.getElementById('beginBtn').addEventListener('click', () => {
    console.log("开始监听")
    const eventSource = new EventSource("example.php");
    // 1、默认形式监听message事件
    // eventSource.onmessage = function(event){
    //     console.log('onmessage', event)
    //     let item = document.createElement("li")
    //     let data = JSON.parse(event.data);
    //     item.innerHTML = data.message
    //     listDom.appendChild(item)
    // }

    // 2、自定义形式监听message事件
    eventSource.addEventListener('message', function(event) {
        console.log('onmessage', event)
        let item = document.createElement("li")
        let data = JSON.parse(event.data);
        item.innerHTML = data.message
        listDom.appendChild(item)
    });

    // 监听stop事件
    eventSource.addEventListener('stop', function(event) {
        console.log('服务端传来:', event.data)
        eventSource.close();
    });

    // 监听error事件
    eventSource.onerror = function(errs) {
        console.log('出错了')
        console.error(errs)
    }

    // ... 监听其它事件
});
```

### php-fpm
```php
use EventSource\EventSource;

$eventSource = new EventSource(3, '新消息123456789');
while(true) {
    // $eventSource->write(); // write与json方法仅对非string类型数据进行json格式化
    // 不传参数时，会直接发送构造方法中的'新消息123456789'; 若构造方法也没传则发送空串
    $eventSource->json([
        'message' => '消息'.random_int(1, 1000),
        'time' => time()
    ]);
    // 判断是否关闭连接
    if ($eventSource->reborted()) {
        break;
    }
    sleep(1);
}
```

### swoole
```php

use EventSource\SwooleEventSource;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Coroutine;


$http = new Server('0.0.0.0', 18088);
$http->on('request', function (Request $request, Response $response) {

    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->end();
        return;
    }
    echo 'path:', $request->server['request_uri'],PHP_EOL;

    if ($request->server['path_info'] == '/index.html') {
        $response->write(file_get_contents(__DIR__.'/index.html'));
        return;
    }

    // 使用SSE
    $sev = SwooleEventSource::make($response);

    while(true) {
        
        $sev->json([
            'message' => 'swoole消息'. rand(1, 100),
            'time' => time(),
        ]);
        $back = time() <= 1713952254;
        // 超过时间就done【代表发送完毕】
        $back or $sev->done();

        if ($sev->aborted()) {
            // 客户端应当对stop事件进行监听处理
            $sev->write('结束了', 'stop');
            break;
        }

        Coroutine::sleep(1);
    }
});
$http->start();
```