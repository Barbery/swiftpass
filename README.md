# SwiftPass

这是一个威富通（swiftpass）支付工具类，实现了微信原生支付、跳转支付、退款、交易查询、退款查询等功能。
如果这个工具对你有帮助，请点个star谢谢


## Install

```
composer require barbery/swiftpass:dev-master
```


## Usage
```php
<?php
// 创建配置
$config = [
    'mch_id' => 'YOUR MCH ID',
    'mch_key' => 'YOUR SECRET KEY',
    'notify_url' => 'YOUR NOTIFY URL',
];


// 实例化
$SwiftPass = new \Barbery\SwiftPass($config);


// 组装支付参数
$data = [
    'sub_openid'   => 'YOUR WECHAT OPEN ID';
    'body'         => 'YOUR ITEM DESCRIPTION',
    'out_trade_no' => 'YOUR ORDER ID',
    'total_fee'    => 'YOUR ITEM TOTAL FEE',
    'is_raw'       => 1, //原生支付必须要传
    'time_start'   => date('YmdHis'),//订单支付开始时间
    'time_expire'  => date('YmdHis', strtotime('+30minute')),//订单关闭时间，超时会自动关闭
];

// 原生支付
$Response  = $SwiftPass->pay($data);
if ($Response->status > 0 || $Response->result_code > 0) {
    var_dump((string)$Response->message, (string)$Response->err_msg, (string)$Response->err_code);
    exit('fail');
}
var_dump(json_decode($Response->pay_info));


// 跳转支付（扫码支付）
$url = $SwiftPass->getPayLink($Resonse->token_id);
var_dump($url);


// 查询交易详情
$Response = $SwiftPass->getTradeDetail(['out_trade_no' => 'YOUR ORDER ID']);
foreach($Response as $key => $value) {
    var_dump("{$key} => {$value}");
}


// 组装退款参数
$data      = [
    'out_trade_no'  => 'YOUR ORDER ID',
    'out_refund_no' => 'YOUR REFUND ID',
    'total_fee'     => 'YOUR ITEM TOTAL FEE',
    'refund_fee'    => 'YOUR REFUND FEE',
];


// 退款
$Response = $SwiftPass->refund($data);
if ($Response->status > 0 || $Response->result_code > 0) {
    var_dump((string)$Response->message, (string)$Response->err_msg, (string)$Response->err_code);
    exit('fail');
}


// 查询退款详情
$Response = $SwiftPass->getRefundDetail(['out_trade_no' => 'YOUR ORDER ID']);
foreach($Response as $key => $value) {
    var_dump("{$key} => {$value}");
}

```

