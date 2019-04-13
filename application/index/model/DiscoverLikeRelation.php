<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:58
 */

namespace app\index\model;


use think\Model;

class DiscoverLikeRelation extends Model
{
    const TABLE_NAME = 'discover_like_relation';
    const COLUMN_DISCOVER_ID = 'discover_id';
    const COLUMN_LIKER_UID = 'liker_uid';
}