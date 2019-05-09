<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/5/9/009
 * Time: 14:16
 */

namespace app\index\model;


use think\Model;

class FeedbackPictureRelation extends Model
{
    const TABLE_NAME = 'feedback_picture_relation';

    const COLUMN_IMPEACH_ID = 'feedback_id';
    const COLUMN_URL = 'url';
    const COLUMN_ORDER_NUMBER = 'order_number';
}