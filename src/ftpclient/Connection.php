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

namespace think\ftpclient;

use think\ftpclient\utils\DirTool;

/**
 * 连接器类
 */
class Connection
{
    /**
     * 当前连接资源
     * @var resource
     */
    protected $linkID;

    /**
     * 重连次数
     * @var int
     */
    protected $reConnectTimes = 0;

    /**
     * 服务器断线标识字符
     * @var array
     */
    protected $breakMatchStr = [
        'supplied resource is not a valid FTP Buffer resource',
    ];

    /**
     * 连接参数配置
     * @var array
     */
    protected $config = [
        // 主机
        'host' => '',
        // 端口
        'port' => 21,
        // 用户名
        'username' => '',
        // 密码
        'password' => '',
        // 被动模式
        'passive' => false,
        // 根目录
        'root_path' => '',
        // 超时时间
        'timeout' => 5,
        // 是否需要断线重连
        'break_reconnect' => false,
        // 断线重连标识
        'break_match_str' => [],
        // 本地临时文件目录
        'temp_dir' => '',
    ];

    /**
     * FTP根目录
     * @var string
     */
    protected $rootPath = '';

    /**
     * FTP当前工作目录
     * @var string
     */
    protected $workingPath = '';

    /**
     * 架构方法
     * @access public
     * @param array $options 配置参数
     * @return void
     */
    public function __construct(array $options = [])
    {
        // 动态配置不为空
        if (!empty($options)) {
            // 合并配置
            $this->config = array_merge($this->config, $options);
        }
        // 设置根目录
        $this->rootPath = '/' . DirTool::trimSeparator($this->config['root_path']);
        // 如果指定了断线重连标识
        if(!empty($this->config['break_match_str'])){
            // 配置了断线标识字符串
            $this->breakMatchStr = array_merge($this->breakMatchStr, $this->config['break_match_str']);
        }
    }

    /**
     * 获取配置参数
     * @access public
     * @param string $name 配置名称
     * @return mixed
     */
    public function getConfig($name = '')
    {
        // 为空
        if ('' === $name) {
            // 返回全部配置
            return $this->config;
        }
        // 返回指定配置
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    /**
     * 切换到指定目录
     * @access public
     * @param string $path
     * @param bool $force
     * @return array
     */
    public function changeToDir($path, $force = false)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 过滤
        $path = DirTool::trimSeparator($path);
        // 如果不为空
        if($path !== ''){
            // 拼接分隔符
            $path = '/' . $path;
        }
        // 拼接根目录
        $path = $this->rootPath . $path;

        // 直接进入目录
        $chdirResult = @ftp_chdir($linkID, $path);
        // 如果成功
        if(true === $chdirResult){
            return ['success', null];
        }
        // 如果未开启强制创建目录
        if(!$force){
            return [null, new \Exception('指定的目录不存在: ' . $path)];
        }
        // 如果失败则尝试创建目录并进入
        // 目录列表
        $pathArray = explode('/', $path);
        // 已创建的目录
        $createdPath = '';
        
        try{
            // 遍历创建
            foreach($pathArray as $dirName){
                if($dirName !== ''){
                    // 构建要创建的路径
                    $createdPath .= '/' . $dirName;
                    //如果不存在这个目录则创建
                    @ftp_mkdir($linkID, $createdPath);
                }
            }
            // 再次进入
            ftp_chdir($linkID, $createdPath);
        } catch (\Exception $e){
            // 返回错误
            return [null, $e];
        }
        // 返回成功
        return ['success', null];
    }

    /**
     * 上传内容到远程FTP
     * @access public
     * @param string $key
     * @param string $body
     * @return array
     */
    public function putObject($key, $body)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始文件名
        $originalKey = $key;
        // 过滤目录分隔符
        $key = DirTool::trimSeparator($key);
        // 如果为空
        if($key === ''){
            return [null, new \Exception('文件名不合法: ' . $originalKey)];
        }
        // 读取文件pathinfo信息
        $pathinfo = pathinfo($key);
        // 目录名
        $dirname = '';
        if(isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '' && $pathinfo['dirname'] !== '.'){
            $dirname = $pathinfo['dirname'];
        }
        // 完整文件名
        $basename = $pathinfo['basename'];
        // 扩展名
        $extension = '';
        if(isset($pathinfo['extension'])){
            $extension = (string) $pathinfo['extension'];
        }
        // 进入目录
        $changeToDirResult = $this->changeToDir($dirname, true);
        // 失败
        if(is_null($changeToDirResult[0])){
            return $changeToDirResult;
        }
        // 临时文件目录
        $tempDir = $this->getTempDir();
        // 构造本地文件名
        $localFile = $tempDir . DIRECTORY_SEPARATOR . 'temp_' . hash('md5', $key . microtime() . mt_rand(10000, 99999));
        // 扩展名不为空
        if($extension !== ''){
            $localFile .= '.' . $extension;
        }
        // 写入本地文件
        $fileputResult = file_put_contents($localFile, $body);
        // 失败
        if (!$fileputResult) {
            return [null, new \Exception('写入临时文件失败, 请检查目录权限: ' . $tempDir)];
        }
        // 上传文件
        try{
            $result = ftp_put($linkID, $basename, $localFile);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        } finally {
            // 删除临时文件
            unlink($localFile);
        }
        // 返回结果
        return ['success', null];
    }

    /**
     * 上传本地文件到远程FTP
     * @access public
     * @param string $key
     * @param string $localFile
     * @return array
     */
    public function putFile($key, $localFile)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 过滤本地文件路径
        $localFile = DirTool::filterRedundantSeparator($localFile);
        // 过滤目录分隔符
        $localFile = str_replace('/', DIRECTORY_SEPARATOR, $localFile);
        // 本地文件不存在
        if(!is_file($localFile)){
            return [null, new \Exception('本地文件不存在: ' . $localFile)];
        }
        // 记录原始文件名
        $originalKey = $key;
        // 过滤目录分隔符
        $key = DirTool::trimSeparator($key);
        // 如果key为空
        if($key === ''){
            return [null, new \Exception('文件名不合法: ' . $originalKey)];
        }
        // 读取文件pathinfo信息
        $pathinfo = pathinfo($key);
        // 目录名
        $dirname = '';
        if(isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '' && $pathinfo['dirname'] !== '.'){
            $dirname = $pathinfo['dirname'];
        }
        // 完整文件名
        $basename = $pathinfo['basename'];
        // 进入目录
        $changeToDirResult = $this->changeToDir($dirname, true);
        // 失败
        if(is_null($changeToDirResult[0])){
            return $changeToDirResult;
        }
        // 上传文件
        try{
            $result = ftp_put($linkID, $basename, $localFile);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        }
        // 返回结果
        return ['success', null];
    }

    /**
     * 删除文件
     * @access public
     * @param string $key
     * @return array
     */
    public function deleteFile($key)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始文件名
        $originalKey = $key;
        // 过滤目录分隔符
        $key = DirTool::trimSeparator($key);
        // 如果为空
        if($key === ''){
            return [null, new \Exception('文件名不合法: ' . $originalKey)];
        }
        // 拼接根目录
        $key = $this->rootPath . $key;
        // 删除文件
        try{
            $result = ftp_delete($linkID, $key);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        }
        // 返回结果
        return ['success', null];
    }

    /**
     * 删除目录
     * @access public
     * @param string $dirname
     * @return array
     */
    public function deleteDir($dirname)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始目录
        $originalDirname = $dirname;
        // 过滤
        $dirname = DirTool::trimSeparator($dirname);
        // 如果不为空
        if($dirname === ''){
            // 返回
            return [null, new \Exception('目录不合法: ' . $originalDirname)];
        }
        // 拼接分隔符
        $dirname = $this->rootPath . $dirname;
        // 列出目录中的全部子文件或目录
        $childrenFiles = ftp_nlist($linkID, $dirname);
        // 失败
        if(!is_array($childrenFiles)){
            return [null, new \Exception('目录不存在: ' . $originalDirname)];
        }
        // 获取目录列表
        $childrenFiles = $this->parseFileList($childrenFiles);
        try{
            // 遍历删除
            foreach ($childrenFiles as $child){
                // 如果是文件
                if($child['type'] === 'file'){
                    // 删除文件
                    ftp_delete($linkID, $child['name']);
                }
                // 如果是目录
                if($child['type'] === 'dir'){
                    // 清空目录下的内容
                    $this->clearDir($child['name']);
                    // 删除目录
                    ftp_rmdir($linkID, $child['name']);
                }
            }
            // 删除目录
            ftp_rmdir($linkID, $dirname);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        }
        // 返回成功
        return ['success', null];
    }

    /**
     * 重命名文件
     * @access public
     * @param string $from
     * @param string $to
     * @return array
     */
    public function renameFile($from, $to)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始原文件名
        $originalFrom = $from;
        // 过滤目录分隔符
        $from = DirTool::trimSeparator($from);
        // 如果为空
        if($from === ''){
            return [null, new \Exception('原文件名不合法: ' . $originalFrom)];
        }
        // 拼接根目录
        $from = $this->rootPath . $from;

        // 获取原文件大小
        $fileSize = ftp_size($linkID, $from);
        // 错误
        if($fileSize < 0){
            return [null, new \Exception('原文件不存在: ' . $originalFrom)];
        }
        
        // 记录原始新文件名
        $originalTo = $to;
        // 过滤目录分隔符
        $to = DirTool::trimSeparator($to);
        // 如果为空
        if($to === ''){
            return [null, new \Exception('新文件名不合法: ' . $originalTo)];
        }
        // 读取新文件pathinfo信息
        $pathinfo = pathinfo($to);
        // 目录名
        $toDirname = '';
        if(isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '' && $pathinfo['dirname'] !== '.'){
            $toDirname = $pathinfo['dirname'];
        }
        // 进入目录
        $changeToDirResult = $this->changeToDir($toDirname, true);
        // 失败
        if(is_null($changeToDirResult[0])){
            return $changeToDirResult;
        }

        // 拼接根目录
        $to = $this->rootPath . $to;
        
        try{
            $result = ftp_rename($linkID, $from, $to);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        }
        // 返回成功
        return ['success', null];
    }

    /**
     * 重命名目录
     * @access public
     * @param string $from
     * @param string $to
     * @return array
     */
    public function renameDir($from, $to)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始原目录
        $originalFrom = $from;
        // 过滤目录分隔符
        $from = DirTool::trimSeparator($from);
        // 如果为空
        if($from === ''){
            return [null, new \Exception('原目录不合法: ' . $originalFrom)];
        }
        // 拼接根目录
        $from = $this->rootPath . $from;
        // 进入目录判断目录是否存在
        $chdirResult = @ftp_chdir($linkID, $from);
        // 失败
        if(false === $chdirResult){
            return [null, new \Exception('原目录不存在: ' . $originalFrom)];
        }

        // 记录原始新目录
        $originalTo = $to;
        // 过滤目录分隔符
        $to = DirTool::trimSeparator($to);
        // 如果为空
        if($to === ''){
            return [null, new \Exception('新目录不合法: ' . $originalTo)];
        }
        // 进入目录
        $changeToDirResult = $this->changeToDir($to, true);
        // 失败
        if(is_null($changeToDirResult[0])){
            return $changeToDirResult;
        }
        // 拼接根目录
        $to = $this->rootPath . $to;
        
        try{
            $result = ftp_rename($linkID, $from, $to);
        } catch (\Exception $e){
            // 关闭连接
            $this->close();
            // 返回
            return [null, $e];
        }
        // 返回成功
        return ['success', null];
    }

    /**
     * 获取文件大小
     * @access public
     * @param string $key
     * @param string $unit
     * @return array
     */
    public function fileSize($key, $unit = 'b')
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始文件名
        $originalKey = $key;
        // 过滤目录分隔符
        $key = DirTool::trimSeparator($key);
        // 如果为空
        if($key === ''){
            return [null, new \Exception('文件名不合法: ' . $originalKey)];
        }
        // 拼接根目录
        $key = $this->rootPath . $key;

        // 获取文件大小
        $fileSize = ftp_size($linkID, $key);
        // 错误
        if($fileSize < 0){
            return [null, new \Exception('文件不存在: ' . $originalKey)];
        }
        // 单位名称
        $unitName = strtolower($unit);
        // 除数
        $unitValue = 1;
        switch ($unitName) {
            case 'b':
                $unitValue = 1;
                break;
            case 'kb':
                $unitValue = 1024;
                break;
            case 'mb':
                $unitValue = 1024 * 1024;
                break;
            case 'gb':
                $unitValue = 1024 * 1024 * 1024;
                break;
            default:
                $unitValue = 1;
                $unitName = 'b';
                break;
        }

        $fileSize = number_format($fileSize / $unitValue, 2, '.', '') . ' ' . $unitName;
        // 返回成功
        return [$fileSize, null];
    }

    /**
     * 获取文件最后修改时间
     * @access public
     * @param string $key
     * @param string $format
     * @return array
     */
    public function fileUpdateTime($key, $format = '')
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始文件名
        $originalKey = $key;
        // 过滤目录分隔符
        $key = DirTool::trimSeparator($key);
        // 如果为空
        if($key === ''){
            return [null, new \Exception('文件名不合法: ' . $originalKey)];
        }
        // 拼接根目录
        $key = $this->rootPath . $key;

        // 获取文件最后修改时间
        $fileUpdateTime = ftp_mdtm($linkID, $key);
        // 错误
        if($fileUpdateTime < 0){
            return [null, new \Exception('文件不存在' . $originalKey)];
        }
        // 如果格式存在
        if(!empty($format)){
            $fileUpdateTime = date($format, $fileUpdateTime);
        }
        // 返回成功
        return [$fileUpdateTime, null];
    }

    /**
     * 返回指定目录所有文件
     * @access public
     * @param string $dirname
     * @return array
     */
    public function fileList($dirname)
    {
        // 获取连接资源
        $linkID = $this->getConnection();
        // 记录原始目录
        $originalDirname = $dirname;
        // 过滤目录分隔符
        $dirname = DirTool::trimSeparator($dirname);
        // 进入目录
        $changeToDirResult = $this->changeToDir($dirname);
        // 失败
        if(is_null($changeToDirResult[0])){
            return $changeToDirResult;
        }
        // 目标目录
        $targetDirname = $this->rootPath;
        // 如果不为空
        if($dirname !== ''){
            $targetDirname .= $dirname;
        }
        // 列出目录中的全部子文件或目录
        $childrenFiles = ftp_nlist($linkID, $targetDirname);
        // 目录不存在
        if(!is_array($childrenFiles)){
            return [null, new \Exception('获取文件列表出错: ' . $originalDirname)];;
        }
        // 获取目录列表
        $childrenFiles = $this->parseFileList($childrenFiles);
        // 返回成功
        return [$childrenFiles, null];
    }

    /**
     * 连接方法
     * @access protected
     * @return mixed
     * @throws \Exception
     */
    protected function connect()
    {
        // 存在连接
        if (!empty($this->linkID)) {
            return $this->linkID;
        }
        // 连接参数
        $options = $this->config;
        // 创建连接
        $linkID = ftp_connect($options['host'], $options['port'], $options['timeout']);
        // 如果失败
        if(false === $linkID){
            throw new \Exception("connect to ftp server [{$options['host']}:{$options['port']}] failed", 500);
        }
        // 登录FTP
        $loginResult = ftp_login($linkID, $options['username'], $options['password']);
        // 如果失败
        if(false === $loginResult){
            throw new \Exception("login to ftp server [{$options['host']}:{$options['port']}] failed", 500);
        }
        // 如果是被动模式
        if(!empty($options['passive'])){
            ftp_set_option($linkID, FTP_USEPASVADDRESS, false);
            ftp_pasv($linkID, true);
        }
        // 返回
        return $linkID;
    }

    /**
     * 获取连接对象实例
     * @access protected
     * @return resource
     * @throws \Exception
     */
    protected function getConnection()
    {
        try {
            // 连接不存在
            if (is_null($this->linkID)) {
                // 重新链接
                $this->linkID = $this->connect();
                // 进入根目录
                $changeToDirResult = $this->changeToDir('/', true);
                // 失败
                if(is_null($changeToDirResult[0])){
                    // 关闭连接
                    $this->close();
                    // 抛出错误
                    throw $changeToDirResult[1];
                }
            }
            // 测试连接是否可用
            $this->workingPath = ftp_pwd($this->linkID);
            // 重置重新链接次数
            $this->reConnectTimes = 0;
            // 返回
            return $this->linkID;
        } catch (\Exception $e) {
            // 如果重连次数小于2且属于连接断开错误
            if ($this->reConnectTimes < 2 && $this->isBreak($e)) {
                // 重试次数自增
                ++$this->reConnectTimes;
                // 关闭连接
                $this->close();
                // 重置链接ID
                $this->linkID = null;
                // 重试连接
                return $this->getConnection();
            }
            throw $e;
        }
    }

    /**
     * 是否断线
     * @access protected
     * @param \Exception $e 异常对象
     * @return bool
     */
    protected function isBreak($e)
    {
        // 未开启断线重连
        if (!$this->getConfig('break_reconnect')) {
            return false;
        }
        // 获取错误信息
        $error = $e->getMessage();
        // 断线错误信息
        foreach ($this->breakMatchStr as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        // 未匹配到断线错误信息
        return false;
    }

    /**
     * 获取当前临时目录
     * @access protected
     * @return string
     */
    protected function getTempDir()
    {
        // 获取临时目录
        $tempDir = $this->getConfig('temp_dir');
        // 如果为空
        if(empty($tempDir)){
            // 目录分隔符
            $depr = DIRECTORY_SEPARATOR;
            // 构造临时目录
            $tempDir = realpath(dirname(__FILE__, 6)) . $depr . 'runtime' . $depr . 'temp' . $depr . 'ftpclient';
        }
        // 临时目录不存在
        if (!is_dir($tempDir)) {
            // 创建临时目录
            mkdir($tempDir, 0755, true);
        }
        // 返回
        return $tempDir;
    }

    /**
     * 删除子目录内的文件
     * @access protected
     * @param string $dirname
     * @return void
     * @throws \Exception
     */
    protected function clearDir($dirname)
    {
        // 列出目录中的全部子文件或目录
        $childrenFiles = ftp_nlist($this->linkID, $dirname);
        // 目录不存在
        if(!is_array($childrenFiles)){
            return;
        }
        // 获取目录列表
        $childrenFiles = $this->parseFileList($childrenFiles);
        // 遍历删除
        foreach ($childrenFiles as $child){
            // 如果是文件
            if($child['type'] === 'file'){
                // 删除文件
                ftp_delete($this->linkID, $child['name']);
            }
            // 如果是目录
            if($child['type'] === 'dir'){
                // 清空目录下的内容
                $this->clearDir($child['name']);
                // 删除目录
                ftp_rmdir($this->linkID, $child['name']);
            }
        }
    }

    /**
     * 解析目录内的文件列表
     * @access protected
     * @param array $files
     * @return array
     */
    protected function parseFileList(array $files)
    {
        // 文件列表
        $fileList = [];
        // 目录列表
        $dirList = [];
        // 遍历文件列表
        foreach ($files as $file){
            // 如果是特殊目录
            if(preg_match('#((\/\.)|(\/\.\.))$#', $file)){
                continue;
            }
            // 获取大小
            $size = ftp_size($this->linkID, $file);
            // 如果是目录
            if(-1 === $size){
                // 记录目录
                $dirList[] = [
                    'name' => $file,
                    'type' => 'dir',
                ];
                continue;
            }
            // 记录
            $fileList[] = [
                'name' => $file,
                'type' => 'file',
            ];
        }
        // 目录排序
        $dirList = DirTool::sort($dirList, 'name');
        // 文件排序
        $fileList = DirTool::sort($fileList, 'name');
        // 返回合并结果
        return array_merge($dirList, $fileList);
    }

    /**
     * 关闭连接
     * @access public
     * @return $this
     */
    public function close()
    {
        // 关闭连接
        @ftp_close($this->linkID);
        // 返回自身
        return $this;
    }

    /**
     * 析构方法
     * @access public
     * @return void
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}
