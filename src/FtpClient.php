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

namespace think;

use axguowen\FtpClient as BaseClient;

/**
 * FTP操作客户端
 */
class FtpClient extends BaseClient
{
    /**
     * make方法
     * @param Config $config 配置对象
     * @return FtpClient
     */
    public static function __make(Config $config)
    {
        $client = new static();
        $client->setConfig($config);
        return $client;
    }

    /**
     * 设置配置对象
     * @access public
     * @param Config $config 配置对象
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * 获取配置参数
     * @access public
     * @param string $name 配置参数
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig($name = '', $default = null)
    {
        if ('' !== $name) {
            return $this->config->get('ftpclient.' . $name, $default);
        }

        return $this->config->get('ftpclient', []);
    }
}
