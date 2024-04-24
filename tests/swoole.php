<?php

require __DIR__ . '/../vendor/autoload.php';

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

