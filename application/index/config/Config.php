<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/13/013
 * Time: 0:33
 */

namespace app\index\config;


class Config
{

    /**
     *  <item>@string/fm_message_str_activity_comment</item>
    <item>@string/fm_message_str_activity_collect</item>
    <item>@string/fm_message_str_topic_join</item>
    <item>@string/fm_message_str_discover_like</item>
    <item>@string/fm_message_str_discover_comment</item>
    <item>@string/fm_message_str_activity_push</item>
    <item>@string/fm_message_str_topic_push</item>
     */
    const MESSAGE_TYPE_ACTIVITY_COMMENT = 0;
    const MESSAGE_TYPE_ACTIVITY_COLLECT = 1;
    const MESSAGE_TYPE_TOPIC_JOIN = 2;
    const MESSAGE_TYPE_DISCOVER_LIKE = 3;
    const MESSAGE_TYPE_DISCOVER_COMMENT = 4;
    const MESSAGE_TYPE_ACTIVITY_PUSH = 5;
    const MESSAGE_TYPE_TOPIC_PUSH = 6;
}