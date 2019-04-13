<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 18:49
 */

namespace app\index\model;


use think\Model;

class User extends Model
{
    const TABLE_NAME = 'user';
    const COLUMN_ID = 'id';
    const COLUMN_UID = 'uid';
    const COLUMN_NICKNAME = 'nickname';
    const COLUMN_AVATAR = 'avatar';
    const COLUMN_GENDER = 'gender';
    const COLUMN_AGE = 'age';
    const COLUMN_CELL_NUMBER = 'cell_number';
    const COLUMN_EMAIL = 'email';
    const COLUMN_DESCRIPTION = 'description';
    const COLUMN_SIGN_TIME = 'sign_time';
}