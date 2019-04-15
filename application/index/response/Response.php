<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/13/013
 * Time: 15:25
 */

namespace app\index\response;


use app\index\config\Config;

class Response
{
    public $code;
    public $status;
    public $data;

    public static function newSuccessInstance($data) {
        $response = new Response();
        $response->code = Config::CODE_OK;
        $response->status = Config::STATUS_OK;
        $response->data = $data;
        return $response;
    }

    public static function newIllegalInstance() {
        $response = new Response();
        $response->code = Config::CODE_ILLEGAL_ACCESS;
        $response->status = Config::STATUS_ERROR_ILLEGAL_ACCESS;
        return $response;
    }

    public static function newEmptyInstance() {
        $response = new Response();
        $response->code = Config::CODE_OK_BUT_EMPTY;
        $response->status = Config::STATUS_OK_BUT_EMPTY;
        return $response;
    }

    public static function newNoDataInstance() {
        $response = new Response();
        $response->code = Config::CODE_NO_DATA;
        $response->status = Config::STATUS_NO_DATA;
        return $response;
    }

    public static function newSignOutFailInstance() {
        $response = new Response();
        $response->code = Config::CODE_ERROR;
        $response->status = Config::STATUS_SIGN_OUT_FAIL;
        return $response;
    }

    public static function newAccountExistsInstance() {
        $response = new Response();
        $response->code = Config::CODE_ERROR;
        $response->status = Config::STATUS_ACCOUNT_EXISTS;
        return $response;
    }

    public static function newTopicNameExistsInstance() {
        $response = new Response();
        $response->code = Config::CODE_ERROR;
        $response->status = Config::STATUS_TOPIC_NAME_EXISTS;
        return $response;
    }
}