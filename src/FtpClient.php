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

use think\ftpclient\Connection;

/**
 * FTP操作客户端
 */
class FtpClient
{
    /**
     * 连接实例
     * @var array
     */
    protected $instance = [];

    /**
     * 配置
     * @var Config
     */
    protected $config;

    /**
     * 架构方法
     * @access public
     * @param Config $config 配置对象
     * @return void
     */
    public function __construct(Config $config)
    {
        // 记录配置对象
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
        // 配置名不为空
        if ('' !== $name) {
            // 返回对应配置
            return $this->config->get('ftpclient.' . $name, $default);
        }
        // 返回全部配置
        return $this->config->get('ftpclient', []);
    }

    /**
     * 创建/切换连接
     * @access public
     * @param string|array|null $connection 连接配置标识
     * @return Connection
     */
    public function connect($connection = null)
    {
        // 如果是数组
        if(is_array($connection)){
            // 连接参数
            $options = array_merge([
                'host' => '',
                'port' => '',
                'username' => '',
                'password' => '',
            ], $connection);
            // 连接标识
            $name = hash('md5', $options['host'] . '_' . $options['port'] . '_' . $options['username'] . '_' . $options['password']);
            // 连接不存在
            if (!isset($this->instance[$name])) {
                // 创建连接
                $this->instance[$name] = new Connection($options);
            }

            return $this->instance[$name];
        }
        
        // 标识为空
        if (empty($connection)) {
            $connection = $this->getConfig('default', 'localhost');
        }
        // 连接不存在
        if (!isset($this->instance[$connection])) {
            // 获取配置中的全部连接配置
            $connections = $this->getConfig('connections');
            // 配置不存在
            if (!isset($connections[$connection])) {
                throw new \Exception('Undefined ftpclient connections config:' . $connection);
            }
            // 创建链接
            $this->instance[$connection] = new Connection($connections[$connection]);
        }
        
        // 返回已存在连接实例
        return $this->instance[$connection];
    }
    
    /**
     * 获取所有连接实列
     * @access public
     * @return array
     */
    public function getInstance()
    {
        return $this->instance;
    }

    public function __call($method, array $args)
    {
        return call_user_func_array([$this->connect(), $method], $args);
    }
}
