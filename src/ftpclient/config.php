<?php
// +----------------------------------------------------------------------
// | ThinkPHP FtpClient [Simple FTP Client For ThinkPHP]
// +----------------------------------------------------------------------
// | ThinkPHP FTP客户端
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

return [
    // 默认连接, 默认为连接配置里面的localhost
    'default' => 'localhost',
    // 连接配置
    'connections' => [
        // 本机连接参数
        'localhost' => [
            // 主机
            'host'              => '127.0.0.1',
            // 端口
            'port'              => 21,
            // 用户名
            'username'          => '',
            // 密码
            'password'          => '',
            // 被动模式
            'passive'           => false,
            // 根目录
            'root_path'         => '',
            // 超时时间
            'timeout'           => 5,
            // 是否需要断线重连
            'break_reconnect'   => false,
            // 断线重连标识
            'break_match_str'   => [],
            // 本地临时文件目录
            'temp_dir'          => '',
        ],
        // 其它主机连接参数
        'other' => [
            // 主机
            'host'      => '192.168.0.2',
            // 端口
            'port'      => 21,
            // 用户名
            'username'  => '',
            // 密码
            'password'  => '',
            // 超时时间
            'timeout'   => 5
        ],
    ]
];
