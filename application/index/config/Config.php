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
    const IMAGE_PREFIX_URL = 'http://112.74.13.186/CollegeAssistantServer/public/uploads/';
    // 响应状态码
    const CODE_OK = 0;
    const CODE_OK_BUT_EMPTY = 1;
    const CODE_ERROR = -1;
    const CODE_NO_DATA = -2;
    const CODE_ILLEGAL_ACCESS = -3;
    const CODE_UNKOWN_ERROR = -3;
    // 响应状态描述
    const STATUS_OK = 'OK';
    const STATUS_OK_BUT_EMPTY = '暂无更多相关数据';
    const STATUS_NO_DATA = '无相关数据';
    const STATUS_ERROR_ILLEGAL_ACCESS = '非法访问';
    const STATUS_ERROR_UNKONW = '发生未知错误';
    const STATUS_LOGIN_FAIL = '登录失败，账号或密码错误';
    const STATUS_ACCOUNT_EXISTS = '账号已存在，请重新修改';
    const STATUS_TOPIC_NAME_EXISTS = '话题已存在';
    const STATUS_PASSWORD_NOT_MATCH = '原密码错误，请检查';
    const STATUS_SIGN_OUT_FAIL = '注销失败，用户不存在';
    const STATUS_NO_SEARCH_RESULT = '没有搜索到相关数据';
    const STATUS_SIGN_UP_FAIL = '网络异常，注册失败';
    const STATUS_MODIFY_PROFILE_FAIL = '网络异常，修改用户资料失败';
    const STATUS_CREATE_TOPIC_FAIL = '网络异常，创建话题失败';
    const STATUS_CREATE_ACTIVITY_FAIL = '网络异常，创建活动失败';
    const STATUS_CREATE_DISCOVER_FAIL = '网络异常，创建动态失败';
    const STATUS_IMPEACH_FAIL = '网络异常，举报失败';
    const STATUS_FEEDBACK_FAIL = '网络异常，反馈失败';

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
    const PAGE_ID_ACTIVITY_SELF = 12;
    const PAGE_ID_ACTIVITY_SELF_COLLECT = 13;
    const PAGE_ID_TOPIC_ALL = 9;
    const PAGE_ID_TOPIC_SELF = 14;
    const PAGE_ID_DISCOVER_ALL = 10;
    const PAGE_ID_DISCOVER_SELF = 15;
    const PAGE_ID_MESSAGE = 11;
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
    const PAGE_ID_USER_SEARCH = 27;

    // 定义可拉加载页面的数据加载类型
    const PULL_TYPE_REFRESH = 0;
    const PULL_TYPE_LOAD_MORE = 1;

    // 定义评论类型
    const COMMENT_TYPE_ACTIVITY = 0;
    const COMMENT_TYPE_DISCOVER = 1;

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
    const PARAM_KEY_TOPIC_NAME = "topic_name"; // 话题页中的话题id
    const PARAM_KEY_ACTIVITY_ID = "activity_id";
    const PARAM_KEY_DISCOVER_ID = "discover_id";
    const PARAM_KEY_MESSAGE_ID = "message_id";
    const PARAM_KEY_ACCOUNT = "account";
    const PARAM_KEY_NICKNAME = "nickname";
    const PARAM_KEY_PASSWORD = "password";
    const PARAM_KEY_NEW_PASSWORD = "new_password";
    const PARAM_KEY_OFFSET = "offset";
    const PARAM_KEY_USER = "user";
    const PARAM_KEY_ACTIVITY = "activity";
    const PARAM_KEY_TOPIC = "topic";
    const PARAM_KEY_FEEDBACK = "feedback";
    const PARAM_KEY_IMPEACH = "impeach";
    const PARAM_KEY_DISCOVER = "discover";
    const PARAM_KEY_POSITIVE = "positive";
    const PARAM_KEY_COMMENT = "comment";
    const PARAM_KEY_COMMENT_TYPE = "comment_type";
    const PARAM_KEY_COMMENT_ID = "comment_id";
    const PARAM_KEY_ADDITION = "addition";
    const PARAM_KEY_ADDITION_ID = "addition_id";


    const PARAM_KEY_FILE = "file";
    const PARAM_KEY_IMAGE = "image";


    const WORD_LIKE = 'like';
    const WORD_AND = 'and';
    const WORD_OR = 'or';
    const WORD_IN = 'in';
    const WORD_LEFT = 'left';
    const WORD_ASC = 'asc';
    const WORD_DESC = 'desc';

    const MAX_REQUEST_COUNT = 30;
    // 默认列表更新请求时提供的时间
    const DEFAULT_TIME_STAMP = "1970-1-1 00:00:00";

    const MESSAGE_CONTENT_ACTIVITY_COLLECT = 'TA收藏了这个活动';
    const MESSAGE_CONTENT_DISCOVER_LIKE = 'TA赞了这个动态';
    const MESSAGE_CONTENT_JOIN_TOPIC = 'TA参与了这个话题';

    const aaa = '
    
    {   
        "code": 0,
        "status":"OK",
        "data":"..."
    }
    
    
    ';
}