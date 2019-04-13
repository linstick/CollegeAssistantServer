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
    // 响应状态码
    const CODE_OK = 0;
    const CODE_OK_BUT_EMPTY = 1;
    const CODE_ILLEGAL_ACCESS = -1;
    const CODE_UNKOWN_ERROR = -3;
    // 响应状态描述
    const STATUS_OK = 'OK';
    const STATUS_OK_BUT_EMPTY = '无相关数据';
    const STATUS_ERROR_ILLEGAL_ACCESS = '非法访问';
    const STATUS_ERROR_UNKONW = '发生未知错误';

    // 消息类型
    const MESSAGE_TYPE_ACTIVITY_COMMENT = 0;
    const MESSAGE_TYPE_ACTIVITY_COLLECT = 1;
    const MESSAGE_TYPE_TOPIC_JOIN = 2;
    const MESSAGE_TYPE_DISCOVER_LIKE = 3;
    const MESSAGE_TYPE_DISCOVER_COMMENT = 4;
    const MESSAGE_TYPE_ACTIVITY_PUSH = 5;
    const MESSAGE_TYPE_TOPIC_PUSH = 6;

    // 可拉列表页面ID定义
    // 需要全局缓存的页面数据
    const PAGE_ID_ACTIVITY_ALL = 0;
    const PAGE_ID_ACTIVITY_ONE_KIND = 8;
    const PAGE_ID_ACTIVITY_SELF = 9;
    const PAGE_ID_ACTIVITY_SELF_COLLECT = 10;
    const PAGE_ID_TOPIC_ALL = 11;
    const PAGE_ID_TOPIC_SELF = 12;
    const PAGE_ID_DISCOVER_ALL = 13;
    const PAGE_ID_DISCOVER_SELF = 14;
    const PAGE_ID_MESSAGE = 15;
    // 不需要全局缓存的页面数据
    const PAGE_ID_ACTIVITY_OTHER_USER = 16;
    const PAGE_ID_ACTIVITY_SEARCH = 17;
    const PAGE_ID_TOPIC_OTHER_USER = 18;
    const PAGE_ID_TOPIC_SEARCH = 19;
    const PAGE_ID_DISCOVER_OTHER_USER = 20;
    const PAGE_ID_DISCOVER_TOPIC_HOT = 21;
    const PAGE_ID_DISCOVER_TOPIC_LASTED = 22;
    const PAGE_ID_DISCOVER_SEARCH = 23;
    const PAGE_ID_ACTIVITY_COMMENT = 24;
    const PAGE_ID_ACTIVITY_ADDITION = 25;
    const PAGE_ID_DISCOVER_COMMENT = 26;

    // 定义可拉加载页面的数据加载类型
    const PULL_TYPE_REFRESH = 0;
    const PULL_TYPE_LOAD_MORE = 1;

    // 请求参数字段定义
    const PARAM_KEY_PAGE_ID = "page_id";    // 页面ID
    const PARAM_KEY_PULL_TYPE = "pull_type";    // 列表请求类型，刷新/加载更多
    const PARAM_KEY_REQUEST_COUNT= "request_count"; // 请求数量
    const PARAM_KEY_TYPE = "type"; // 活动类型，这个参数在其他请求中无效
    const PARAM_KEY_UID = "uid";   // 当前用户的uid，默认为-1
    const PARAM_KEY_OTHER_UID = "other_uid"; // 请求其他用户数据时的用户uid
    const PARAM_KEY_KEYWORD = "keyword";   // 搜索页请求提供关键字参数
    const PARAM_KEY_TIME_STAMP = "time_stamp"; // 列表中第一或最后一个item的时间戳
    const PARAM_KEY_TOPIC_ID = "topic_id"; // 话题页中的话题id
    const PARAM_KEY_ACTIVITY_ID = "activity_id";
    const PARAM_KEY_DISCOVER_ID = "discover_id";


    const WORD_LIKE = 'like';
    const WORD_AND = 'and';
    const WORD_OR = 'or';
    const WORD_ASC = 'asc';
    const WORD_DESC = 'desc';
}