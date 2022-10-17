<?php


namespace Native\ApiClient\Helpers;

/**
 * CSRF Token Generator
 */
class CsrfTokenGenerator
{
    
    /**
     * @param $length
     *
     * @return string
     */
    public function generate($length)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str      = '';
        $max      = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[rand(0, $max)];
        }
        
        return $str;
    }
    
}