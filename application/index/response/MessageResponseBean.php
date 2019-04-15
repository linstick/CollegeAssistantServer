<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/14/014
 * Time: 13:43
 */

namespace app\index\response;


class MessageResponseBean
{
    public $uid;
    public $nickname;
    public $avatarUrl;
    public $publishTime;

    public $content;
    public $type;

    public $targetId;
    public $targetCoverUrl;
    public $targetTitle;
    public $targetContent;
}