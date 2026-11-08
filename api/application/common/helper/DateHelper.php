<?php
namespace app\common\helper;

/**
 * 日期时间处理辅助类
 */
class DateHelper
{
    /**
     * 将时间戳转换为MySQL datetime格式
     * 
     * @param int|string $timestamp 时间戳
     * @return string 格式化的日期时间
     */
    public static function timestampToDatetime($timestamp = null)
    {
        // 如果未提供时间戳，则使用当前时间
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // 如果是字符串数字，转为整数
        if (is_string($timestamp) && is_numeric($timestamp)) {
            $timestamp = (int) $timestamp;
        }
        
        // 确保是有效的时间戳
        if (!is_int($timestamp) || $timestamp <= 0) {
            return date('Y-m-d H:i:s');
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * 获取当前日期时间（MySQL格式）
     * 
     * @return string 当前日期时间
     */
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * 将MySQL datetime格式转换为时间戳
     * 
     * @param string $datetime MySQL格式的日期时间
     * @return int 时间戳
     */
    public static function datetimeToTimestamp($datetime)
    {
        if (empty($datetime)) {
            return strtotime(date('Y-m-d H:i:s'));
        }
        
        return strtotime($datetime);
    }
    
    /**
     * 格式化显示时间
     * 
     * @param string|int $time 时间戳或MySQL日期时间
     * @param string $format 格式
     * @return string 格式化的时间
     */
    public static function formatTime($time, $format = 'Y-m-d H:i:s')
    {
        if (is_numeric($time)) {
            // 时间戳
            return date($format, (int)$time);
        } else {
            // 日期时间字符串
            return date($format, strtotime($time));
        }
    }
}