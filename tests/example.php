<?php

require __DIR__ . '/../vendor/autoload.php';

use EventSource\EventSource;

# 对象调用方法
$eventSource = new EventSource(3, '新消息'.time());
while(true) {
    $eventSource->json([
        'message' => '消息'.random_int(1, 1000),
        'time' => time()
    ]);
    if ($eventSource->reborted()) {
        break;
    }
    sleep(1);
}
