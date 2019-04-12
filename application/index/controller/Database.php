<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 21:41
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\model\Activity;
use app\index\model\ActivityCollectRelation;
use app\index\model\ActivityComment;
use app\index\model\ActivityPictureRelation;
use app\index\model\CollegeInfo;
use app\index\model\Discover;
use app\index\model\DiscoverComment;
use app\index\model\DiscoverLikeRelation;
use app\index\model\DiscoverPictureRelation;
use app\index\model\Message;
use app\index\model\Topic;
use app\index\model\TopicJoinRelation;
use app\index\model\TopicVisitRelation;
use app\index\model\User;

class Database
{
    public function createVirtualData() {
//        Database::createUsers();
//        Database::createCollegeInfo();
//        Database::createTopicData();
//        Database::createActivityData();
//        Database::createDiscoverData();
//        Database::createActivityPictureData();
//        Database::createDiscoverPictureData();
//        Database::createActivityCommentData();
//        Database::createDiscoverCommentData();
//        Database::createActivityCollectData();
//        Database::createDiscoverLikeData();
//        Database::createTopicVisitData();

        return 'success';
    }

    private function  createUsers() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $user = new User();
            $user->id = 'user'.$i;
            $user->nickname = "昵称".$i;
            $user->avatar = rand(1, 10).'.jpg';
            $user->gender = rand(0, 1) == 0 ? '男' : '女';
            $user->age = rand(10, 40);
            $user->password = 'user';
            $user->description = rand(0, 1) == 0 ? '我是新用户，请多多指教' : '';
            $user->save();
        }
        return 'success';
    }

    private function createCollegeInfo() {
       for ($i = 1; $i < Database::$user_count; $i++) {
           $college = new CollegeInfo();
           $college->uid = $i;
           $college->name = Database::$college_name[rand(0, count(Database::$college_name) - 1)];
           $college->department = Database::$department_name[rand(0, count(Database::$department_name) - 1)];
           $college->klass = Database::$class_name[rand(0, count(Database::$class_name) - 1)];
           $college->save();
       }
    }

    private function createTopicData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $topic = new Topic();
            $topic->name = '话题'.$i;
            $topic->description = '这是话题描述，一般是话题发布者对这个话题的一些看法，编号：'.$i;
            if (rand(0, 2) != 2) {
                $topic->cover = rand(1, 10).'.jpeg';
            }
            $topic->publisher_uid = $i;
            $topic->save();
        }
    }

    private function createTopicVisitData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $count = rand(-10, 40);
            $visitor_uid = rand(1, 10);
            for ($j = 0; $j < $count; $j++) {
                $visitRelation = new TopicVisitRelation();
                $visitRelation->topic_id = $i;
                $visitRelation->visitor_uid = $visitor_uid;
                $visitRelation->save();
                $visitor_uid += rand(15, 30);
            }
        }
    }

    private function createActivityData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $activity = new Activity();
            $activity->type = rand(1, 7);
            $activity->title = '这是活动标题，编号：'.$i;
            $activity->content = '这是活动的详细内容，编号：'.$i;
            $activity->host = '主办方'.$i;
            $activity->time = "2019-05-".rand(1, 30)." ".rand(10, 22).":00";
            $activity->address = Database::$college_name[rand(0, count(Database::$college_name) - 1)];
            $activity->remark = "这是备注信息，发布者可以在这里添加一些必要信息";
            if (rand(0, 1) != 1) {
                $activity->location = Database::$location[rand(0, count(Database::$location) - 1)];
            }
            if (rand(0, 1) != 1) {
                $activity->related_topic_id = rand(1, Database::$user_count - 1);
            }
            $activity->publisher_uid = rand(1, Database::$user_count - 1);
            $activity->save();
        }
    }

    private function createActivityPictureData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $count = rand(-2, 9);
            for ($j = 0; $j < $count; $j++) {
                $relation = new ActivityPictureRelation();
                $relation->activity_id = $i;
                $relation->url = rand(1, 20).'.jpg';
                $relation->order_number = $j;
                $relation->save();
            }
        }
    }

    private function createActivityCommentData() {
        $temp = 1;
        for ($i = 1; $i < Database::$user_count; $i++) {
            // 产生的评论数量
            $count = rand(-10, 20);
            // 需要获取标题和内容
            $activity = Activity::get($i);
            // 需要获取第一张图片
            $pictureRelation = ActivityPictureRelation::get([
                'activity_id' => $i,
                'order_number' => 0,
            ]);
            for ($j = 0; $j < $count; $j++) {
                // 创建活动数据
                $comment = new ActivityComment();
                $comment->content = "这是活动评论内容".($temp++);
                $comment->activity_id = $i;
                $comment->publisher_uid = rand(1, Database::$user_count - 1);
                $comment->save();
                // 同时产生相应的消息
                $message = new Message();
                $message->type = Config::MESSAGE_TYPE_ACTIVITY_COMMENT;
                $message->content = $comment->content;
                $message->target_id = $i;
                $message->target_title = $activity->title;
                $message->target_content = $activity->content;
                $message->related_uid = $activity->publisher_uid;
                if ($pictureRelation != null) {
                    $message->target_cover = $pictureRelation->url;
                }
                $message->save();
            }
        }
    }

    private function createActivityCollectData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $count = rand(-10, 30);
            $activity = Activity::get($i);
            // 需要获取第一张图片
            $pictureRelation = ActivityPictureRelation::get([
                'activity_id' => $i,
                'order_number' => 0,
            ]);
            $collector_uid = rand(1, 10);
            for ($j = 0; $j < $count; $j++) {
                $collectRelation = new ActivityCollectRelation();
                $collectRelation->activity_id = $i;
                $collectRelation->collector_uid = $collector_uid;
                $collectRelation->save();
                $collector_uid += rand(20, 30);

                // 产生收藏消息
                $message = new Message();
                $message->type = Config::MESSAGE_TYPE_ACTIVITY_COLLECT;
                $message->content = 'TA收藏了这个活动';
                $message->target_id = $i;
                $message->target_title = $activity->title;
                $message->target_content = $activity->content;
                $message->related_uid = $activity->publisher_uid;
                if ($pictureRelation != null) {
                    $message->target_cover = $pictureRelation->url;
                }
                $message->save();
            }
        }
    }


    private function createDiscoverData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $hasRelateTopic = false;
            $discover = new Discover();
            $discover->content = '这是动态的内容信息，可能是很长很长的一些句子，编号：'.$i;
            if (rand(0, 1) != 1) {
                $discover->location = Database::$location[rand(0, count(Database::$location) - 1)];
            }
            if (rand(0, 1) != 1) {
                $discover->related_topic_id = rand(1, Database::$user_count - 1);
                $hasRelateTopic = true;
            }
            $discover->publisher_uid = $i;
            $discover->save();
            if ($hasRelateTopic) {
                $topic = Topic::get($discover->related_topic_id);
                $message = new Message();
                $message->type = Config::MESSAGE_TYPE_TOPIC_JOIN;
                $message->content = 'TA参与了这个活动';
                $message->target_id = $topic->id;
                $topic_name = $topic->name;
                $message->target_title = '#'.$topic_name.'#';
                $message->target_content = $topic->description;
                $message->related_uid = $topic->publisher_uid;
                $message->target_cover = $topic->cover;
                $message->save();

                $joinRelation = new TopicJoinRelation();
                $joinRelation->topic_id = $topic->id;
                $joinRelation->discover_id = $discover->id;
                $joinRelation->save();
            }
        }
    }

    private function createDiscoverPictureData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $count = rand(-2, 9);
            for ($j = 0; $j < $count; $j++) {
                $relation = new DiscoverPictureRelation();
                $relation->discover_id = $i;
                $relation->url = rand(1, 20).'.jpg';
                $relation->order_number = $j;
                $relation->save();
            }
        }
    }

    private function createDiscoverCommentData() {
        $temp = 1;
        for ($i = 1; $i < Database::$user_count; $i++) {
            // 产生的评论数量
            $count = rand(-5, 20);
            // 需要获取标题和内容
            $discover = Discover::get($i);
            // 需要获取第一张图片
            $pictureRelation = DiscoverPictureRelation::get([
                'discover_id' => $i,
                'order_number' => 0
            ]);
            for ($j = 0; $j < $count; $j++) {
                // 创建评论数据
                $comment = new DiscoverComment();
                $comment->content = "这是动态评论内容".($temp++);
                $comment->discover_id = $i;
                $comment->publisher_uid = rand(1, Database::$user_count - 1);
                $comment->save();
                // 同时产生相应的消息
                $message = new Message();
                $message->type = Config::MESSAGE_TYPE_DISCOVER_COMMENT;
                $message->content = $comment->content;
                $message->target_id = $i;
                $message->target_content = $discover->content;
                $message->related_uid = $discover->publisher_uid;
                if ($pictureRelation != null) {
                    $message->target_cover = $pictureRelation->url;
                }
                $message->save();
            }
        }
    }

    private function createDiscoverLikeData() {
        for ($i = 1; $i < Database::$user_count; $i++) {
            $count = rand(-10, 30);
            $liker_uid = rand(1, 10);
            $discover = Discover::get($i);
            // 需要获取第一张图片
            $pictureRelation = DiscoverPictureRelation::get([
                'discover_id' => $i,
                'order_number' => 0
            ]);
            for ($j = 0; $j < $count; $j++) {
                $likeRelation = new DiscoverLikeRelation();
                $likeRelation->discover_id = $discover->id;
                $likeRelation->liker_uid = $liker_uid;
                $likeRelation->save();
                $liker_uid += rand(15, 30);

                // 产生点赞消息
                $message = new Message();
                $message->type = Config::MESSAGE_TYPE_DISCOVER_LIKE;
                $message->content = 'TA赞了这个动态';
                $message->target_id = $i;
                $message->target_content = $discover->content;
                $message->related_uid = $discover->publisher_uid;
                if ($pictureRelation != null) {
                    $message->target_cover = $pictureRelation->url;
                }
                $message->save();
            }
        }
    }

    private static $user_count = 1000;

    private static $college_name = array(
        '广东工业大学',
        '华南理工大学',
        '广州大学',
        '中山大学',
        '广州美术学院',
        '广州星海音乐学院',
        '华南师范大学',
        '广东药科大学'
    );
    private static $department_name = array(
        '信息工程学院',
        '计算机学院',
        '机械制造及其自动化学院',
        '外国语学院',
        '自动化学院',
        '机器人学院',
        '土木工程学院'
    );

    private static $class_name = array(
        '12级',
        '13级',
        "14级",
        '15级',
        '16级',
        '17级',
        '18级',
    );

    private static $location = array(
        '广州·正佳广场',
        '广州·广州大学城',
        '广州·广东工业大学(大学城校区)',
        '广州·天河城',
        '深圳·腾讯大厦',
        '深圳·马峦山',
        '深圳·东部华侨城',
        '深圳·深圳大学',
        '深圳·大梅沙',
        '广州·长隆欢乐世界'
    );
}