<?php

namespace Config;



use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{

     public static function tmdb($getShared = true)
     {
         if ($getShared) {
              return static::getSharedInstance('tmdb');
         }

         return new \App\Libraries\DataAPI\Tmdb;
     }

    public static function omdb($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('omdb');
        }

        return new \App\Libraries\DataAPI\Omdb;
    }

    public static function data_api($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('data_api');
        }

        return new \App\Libraries\DataAPI\API;
    }

    public static function math_captcha($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('math_captcha');
        }

        return new \App\Libraries\Captcha\MathCaptcha;
    }

    public static function hash_ids($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('hash_ids');
        }

        return new \App\Libraries\HashIds\HashIds;
    }


    public static function auth($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('auth');
        }

        return new \App\Libraries\Authentication;

    }

    public static function watch_history($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('watch_history');
        }

        return new \App\Libraries\WatchHistory;
    }

    public static function recommend($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('recommend');
        }

        return new \App\Libraries\Recommend;
    }

    public static function bulk_import($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('bulk_import');
        }

        return new \App\Libraries\BulkImport;
    }

    public static function quick_insert($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('quick_insert');
        }
        return new \App\Libraries\QuickInserter;
    }




}
