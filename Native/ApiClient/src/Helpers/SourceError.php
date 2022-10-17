<?php


namespace Native\ApiClient\Helpers;

/**
 *
 */
class SourceError implements \Native\ApiClient\Interfaces\Internal\SourceError
{
    
    /**
     * @var string[]
     */
    protected static $errorList = [
        100   => "Access was made using an incorrect procedure",
        102   => "Sorry, the page you requested was not found",
        103   => "Invalid URL",
        104   => "Service is unavailable",
        // ... длинный список возможных ошибок
        21008 => "Can't allowed to access a adult auction.",
    ];
    
    /**
     * @param $sourceErrorCode
     *
     * @return string
     */
    public static function getByCode($sourceErrorCode)
    {
        return isset(self::$errorList[$sourceErrorCode]) ? self::$errorList[$sourceErrorCode] : '';
    }
    
}