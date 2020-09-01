<?php

//namespace console\modules\listen\controllers;

use common\helper\AliHelper;
use wind\acm\AcmHelper;
use Yii;
use yii\base\Exception;
use yii\console\Controller;

class CamListenController extends Controller
{
    
    /**
     * 监听配置变化，使用swoole开启定时器，可用supervisor守护此进程
     *
     * @throws \Aliyun_ACM_Exception
     * @throws \RequestCore_Exception
     */
    public function actionIndex()
    {
        $key_list = Yii::$app->params['ali_cloud']['acm_key_list'];
        foreach ($key_list as $key_name) {
            \Swoole\Timer::tick(60 * 1000, function () use ($key_name) {
                try {
                    //$content若加密过，则为配置中加密的原始内容
                    $content = Yii::$app->redis->get($key_name . '_old');
                    $ali_helper = new AcmHelper($key_name);
                    if ($ali_helper->listenConfig($content)) {
                        //请求接口获取最新配置并更新缓存
                        $result = $ali_helper->getConfig();
                        Yii::$app->redis->set($key_name, $result['content']);
                        Yii::$app->redis->set($key_name . '_old', $result['old_content']);
                    }
                } catch (Exception $exception) {
                    var_dump($exception->getMessage());
                    Yii::error($exception->getMessage());
                }
            });
        }
    }
}