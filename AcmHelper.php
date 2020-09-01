<?php

namespace wind\acm;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Aliyun_ACM_Client;
use Yii;
use yii\base\Exception;

require_once 'sdk/Aliyun/ACM/Autoload.php';

/**
 * Class AliHelper
 *
 * @package common\helper
 */
class AcmHelper
{
    
    private $access_key_id;
    private $access_key_secret;
    private $namespace;
    private $app_name;
    private $data_id;
    private $group;
    private $end_point;
    private $port;
    private $kms_region_id;
    private $kms_host;
    /**
     * @var int 数据是否加密 1KMS加密2不加密
     */
    private $is_encrypt;
    
    /**
     * @param string $acm_data_name
     */
    public function __construct($acm_data_name)
    {
        $access_params = Yii::$app->params['ali_cloud']['access_key'];
        $this->access_key_id = $access_params['access_key_id'];
        $this->access_key_secret = $access_params['access_key_secret'];
        $acm_info = Yii::$app->params['ali_cloud'][$acm_data_name];
        foreach ($acm_info as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * 获取配置
     *
     * @return array|string
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \Aliyun_ACM_Exception
     */
    public function getConfig()
    {
        $client = new Aliyun_ACM_Client($this->end_point, $this->port);
        $client->refreshServerList();
        $client->setNameSpace($this->namespace);
        $client->setAccessKey($this->access_key_id);
        $client->setSecretKey($this->access_key_secret);
        $client->setAppName($this->app_name);
        $res = $client->getConfig($this->data_id, $this->group);
        $result['content'] = $result['old_content'] = $res;
        if ($this->is_encrypt == 1) {
            $decrypt_content = $this->decrypt($res);
            if ( !isset($decrypt_content['Plaintext'])) {
                Yii::error($decrypt_content);
                throw new Exception('KMS请求异常！');
            }
            $result['content'] = $decrypt_content['Plaintext'];
            $result['old_content'] = $res;
        }
        
        return $result;
    }
    
    /**
     * KMS解密配置
     *
     * Download：https://github.com/aliyun/openapi-sdk-php
     * Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md
     *
     * @param $value
     *
     * @return array|string
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    public function decrypt($value)
    {
        AlibabaCloud::accessKeyClient(
            $this->access_key_id,
            $this->access_key_secret
        )->regionId($this->kms_region_id)->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Kms')
                ->scheme('https')// https | http
                ->version('2016-01-20')
                ->action('Decrypt')
                ->method('POST')
                ->host($this->kms_host)
                ->options([
                    'query' => [
                        'RegionId' => $this->kms_region_id,
                        'CiphertextBlob' => $value,
                    ],
                ])
                ->request();
            
            return $result->toArray();
        } catch (ClientException $e) {
            return $e->getErrorMessage();
        } catch (ServerException $e) {
            return $e->getErrorMessage();
        }
    }
    
    /**
     * 监听配置变化
     *
     * @param string $content 配置内容，使用加密则未加密的内容
     *
     * @return false|mixed|string
     * @throws \Aliyun_ACM_Exception
     * @throws \RequestCore_Exception
     */
    public function listenConfig($content)
    {
        $client = new Aliyun_ACM_Client($this->end_point, $this->port);
        $client->refreshServerList();
        $client->setNameSpace($this->namespace);
        $client->setAccessKey($this->access_key_id);
        $client->setSecretKey($this->access_key_secret);
        $client->setAppName($this->app_name);
        $listen = $client->listenConfig($this->data_id, $this->group, $this->namespace, $content);
        if ($listen === false) {
            Yii::error($client->errors);
            
            return false;
        }
        
        return $listen;
    }
}