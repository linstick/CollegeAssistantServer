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
}