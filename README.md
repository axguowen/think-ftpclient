# ThinkPHP FTP 客户端管理器

一个简单的 ThinkPHP FTP 客户端连接管理工具

## 特性

- 支持断线重连
- 支持单例模式
- 支持动态切换连接
- 支持设置根目录

## 安装
~~~
composer require axguowen/think-ftpclient
~~~

## 使用

首先配置config目录下的ftpclient.php配置文件，然后可以按照下面的用法使用。

### 简单使用
~~~php
use \think\facade\FtpClient;
// 传送指定内容到文件
$putObjectResult = FtpClient::putObject('/test/myfile.txt', 'file content');
// 成功
if(!is_null($putObjectResult[0])){
    echo '上传成功';
}else{
    echo $putObjectResult[1]->getMessage();
}
~~~

### 切换连接其它主机
~~~php
use \think\facade\FtpClient;
// 连接其它服务器
$ftpClientOther = FtpClient::connect('other');
// 传送指定内容到文件
$putObjectResult = $ftpClientOther->putObject('/test/myfile.txt', 'file content');
// 成功
if(!is_null($putObjectResult[0])){
    echo '上传成功';
}else{
    echo $putObjectResult[1]->getMessage();
}
~~~

### 动态传入连接的主机参数
~~~php
use \think\facade\FtpClient;
// 动态连接
$ftpClient = FtpClient::connect([
    // 主机
    'host' => 'xx.xx.xx.xx',
    // 端口
    'port' => 21,
    // 用户名
    'username' => 'username',
    // 密码
    'password' => 'password',
]);
// 传送指定内容到文件
$putObjectResult = $ftpClient->putObject('/test/myfile.txt', 'file content');
// 成功
if(!is_null($putObjectResult[0])){
    echo '上传成功';
}else{
    echo $putObjectResult[1]->getMessage();
}
~~~

### 其它方法
~~~php
use \think\facade\FtpClient;
// 传送指定内容到指定文件
$putObjectResult = FtpClient::putObject('/test/myfile.txt', 'file content');
// 上传本地文件到指定文件
$putFileResult = FtpClient::putFile('/test/myfile.txt', '/test/localfile.txt');
// 删除FTP上的文件
$deleteFileResult = FtpClient::deleteFile('/test/myfile.txt');
// 删除FTP上的目录
$deleteDirResult = FtpClient::deleteDir('/test/mydir');
// 重命名FTP上的文件
$renameFileResult = FtpClient::renameFile('/test/oldfilename.txt', '/test/newfilename.txt');
// 重命名FTP上的目录
$renameDirResult = FtpClient::renameDir('/test/olddirname', '/test/newdirname');
// 返回指定文件大小, 第二个参数传入单位, 支持b, kb, mb, gb;
$fileSizeResult = FtpClient::fileSize('/test/myfile.txt', 'kb');
// 返回文件最后修改时间, 第二个参数传入格式, 不传默认返回时间戳;
$fileUpdateTimeResult = FtpClient::fileUpdateTime('/test/myfile.txt', 'Y-m-d H:i:s');
// 返回指定目录下的文件列表
$fileListResult = FtpClient::fileList('/test/mydir');
~~~