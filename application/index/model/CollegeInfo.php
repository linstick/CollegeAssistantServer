<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:03
 */

namespace app\index\model;


use think\Model;

class CollegeInfo extends Model
{
    const TABLE_NAME = 'college_info';
    const COLUMN_UID = 'uid';
    const COLUMN_NAME = 'name';
    const COLUMN_DEPARTMENT = 'department';
    const COLUMN_MAJOR = 'major';
    const COLUMN_KLASS = 'klass';
}