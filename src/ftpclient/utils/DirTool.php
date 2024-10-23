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

namespace think\ftpclient\utils;

class DirTool
{
    /**
     * 过滤多余的分隔符
     * @access public
     * @param string $path
     * @return string
     */
    public static function filterRedundantSeparator($path)
    {
        return preg_replace('/\/+/', '/', str_replace('\\', '/', $path));
    }

    /**
     * 裁剪两边的分隔符
     * @access public
     * @param string $path
     * @return string
     */
    public static function trimSeparator($path)
    {
        return trim(static::filterRedundantSeparator($path), '/');
    }

    /**
     * 排序
     * @access public
     * @param array $items
     * @param string $field
     * @param string $order
     * @return string
     */
    public static function sort($items, $field, $order = 'asc')
    {
        // 排序
        uasort($items, function ($a, $b) use ($field, $order) {
            $fieldA = $a[$field] ?? null;
            $fieldB = $b[$field] ?? null;
            return 'desc' == strtolower($order) ? intval($fieldB > $fieldA) : intval($fieldA > $fieldB);
        });
        // 返回
        return $items;
    }
}