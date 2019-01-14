<?php
/**
 *
 * Author:  Kernel Huang
 * Mail:    kernelman79@gmail.com
 * Date:    1/11/19
 * Time:    5:09 PM
 */

return (object)[
    'table'     => (object)[

        'size'  => 4096,  // 单位:字节, 设置共享内存表最大行数, 可以在业务代码中自定义大小
    ],

    'schema'    => (object)[
        'fd'    => (object)[
            'name' => 'fd',
            'type' => 'int',
            'size' => 4,
        ],
        'uid'    => (object)[
            'name' => 'uid',
            'type' => 'string',
            'size' => 64,
        ],
    ]
];
