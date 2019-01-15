<?php
/**
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/11/19
 * Time:    5:09 PM
 */

return (object)[

    'set' => (object)[
        'host'                      => '0.0.0.0',
        'port'                      => 12345,
        'handshake'                 => false,               // 自定义WebSocket握手协议
        'workers'                   => 6,                   // 开启进程数量
        'daemon'                    => false,               // 是否开启守护进程
        'socketType'                => SWOOLE_SOCK_TCP,     // sock连接类型
        'syncType'                  => SWOOLE_SOCK_ASYNC,   // SWOOLE_SOCK_SYNC/SWOOLE_SOCK_ASYNC, 同步/异步，默认异步
        'timeout'                   => 0.1,                 // 超时值默认为0.1s，即100毫秒
        'flag'                      => 0,                   // UDP连接类型专用标志，1启用udp_connect，0不启用
        'eofSplit'                  => true,                // 开启Length/EOF的协议处理方式
        'packageEof'                => PHP_EOL,             // 包结束符
        'lengthCheck'               => true,                // 打开包长检测特性
        'packageMaxLength'          => 4000000,             // 设置最大数据包尺寸为1M
        'packageLengthType'         => 'n',                 // 长度值的类型
        'packageLengthOffset'       => 0,
        'packageBodyOffset'         => 2,
        'socketBufferSize'          => 1024 * 1024 * 1024,  // Socket缓存区尺寸
    ],

    // 监听方法
    'listen' => (object)[
        'start'         => 'onStart',
        'workerStart'   => 'onWorkerStart',
        'task'          => 'onTask',
        'finish'        => 'onFinish',
        'pipeMessage'   => 'onPipeMessage',
        'workerError'   => 'onWorkerError',
        'managerStart'  => 'onManagerStart',
        'managerStop'   => 'onManagerStop',
        'request'       => 'onRequest',
        'open'          => 'onWSOpen',
        'message'       => 'onWSMessage',
        'close'         => 'onWSClose',
        'shutdown'      => 'onShutdown',
    ]
];
