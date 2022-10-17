<?php


namespace Native\ApiClient\Interfaces\Internal;

/**
 * @package Native\ApiClient\Interfaces\Internal
 */
interface SourceError
{
    
    /**
     * @param $sourceErrorCode
     *
     * @return mixed
     */
    public static function getByCode($sourceErrorCode);
    
}