###配置参数（params-local）
```php
'ali_cloud' => [
        'access_key' => [
            'access_key_id' => 'xh*********O',
            'access_key_secret' => 'jE************3',
        ],
        'acm_key_list' => ['acm_bi_db'],
        'acm_bi_db' => [
            'namespace' => '40b************1b',
            'app_name' => 'app**',
            'data_id' => 'cipher-db***',
            'group' => 'group**',
            'is_encrypt' => 1,//1KMS加密2不加密
            'end_point' => 'acm.aliyun.com',
            'port' => '8080',
            'kms_region_id' => 'cn-shanghai',
            'kms_host' => 'kms.cn-shanghai.aliyuncs.com',
        ]
    ]
```
###获取更新
```php
$ali_helper = new AcmHelper($key_name);//$key_name=acm_bi_db
$result = $ali_helper->getConfig();
```
###监听配置
若配置中心启用加密，$content为加密后的密文
```php
$ali_helper->listenConfig($content)
```