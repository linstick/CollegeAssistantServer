<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/5/9/009
 * Time: 14:18
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\model\Feedback;
use app\index\model\FeedbackPictureRelation;
use app\index\model\Impeach;
use app\index\model\ImpeachPictureRelation;
use app\index\response\Response;
use think\Request;

class Improve
{
    function feedback() {
        $request = Request::instance();
        $publisher_uid = $request->post(Config::PARAM_KEY_UID);
        $feedback_json = $request->post(Config::PARAM_KEY_FEEDBACK);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($feedback_json == null) {
            return Response::newIllegalInstance();
        }
        $feedback = null;
        // 上传图片文件
        $images = array();
        if ($files) {
            foreach ($files as $file) {
                $imageUrl = Upload::uploadImage($file);
                if ($imageUrl == null) {
                    return Response::newErrorInstance(Config::STATUS_FEEDBACK_FAIL);
                }
                $images[] = $imageUrl;
            }
        }

        // 创建反馈信息
        $feedback_source = json_decode($feedback_json);
        $feedback = new Feedback();
        $feedback->type = $feedback_source->type;
        $feedback->description = $feedback_source->description;
        $feedback->publisher_uid = $publisher_uid;
        $feedback->save();
        // 保存活动图片
        foreach ($images as $key => $image) {
            $relation = new FeedbackPictureRelation();
            $relation->feedback_id = $feedback->id;
            $relation->url = $image;
            $relation->order_number = $key;
            $relation->save();
        }
        return Response::newSuccessInstance(null);
    }


    function impeach() {
        $request = Request::instance();
        $publisher_uid = $request->post(Config::PARAM_KEY_UID);
        $impeach_json = $request->post(Config::PARAM_KEY_IMPEACH);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($impeach_json == null) {
            return Response::newIllegalInstance();
        }
        $impeach = null;
        // 上传图片文件
        $images = array();
        if ($files) {
            foreach ($files as $file) {
                $imageUrl = Upload::uploadImage($file);
                if ($imageUrl == null) {
                    return Response::newErrorInstance(Config::STATUS_IMPEACH_FAIL);
                }
                $images[] = $imageUrl;
            }
        }

        // 创建举报信息
        $impeach_source = json_decode($impeach_json);
        $impeach = new Impeach();
        $impeach->reason_type = $impeach_source->reasonType;
        $impeach->description = $impeach_source->description;
        $impeach->target_type = $impeach_source->targetType;
        $impeach->target_id = $impeach_source->targetId;
        $impeach->publisher_uid = $publisher_uid;
        $impeach->save();
        // 保存活动图片
        foreach ($images as $key => $image) {
            $relation = new ImpeachPictureRelation();
            $relation->impeach_id = $impeach->id;
            $relation->url = $image;
            $relation->order_number = $key;
            $relation->save();
        }
        return Response::newSuccessInstance(null);
    }
}