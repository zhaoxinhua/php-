<?php
include './wxPay.php';
//引入微信支付类
$config = [
    'appid' => 'wx2ce5c3dbc2de5019',
    'macid' => '1599106161',
    'key' => '51f09ebb142afe4fd3305420795fcbd6',
    'notify_url' => 'https://apple.lc-web.cn/portal/wxpay/notify'
];
//创建支付类
$wxpay = new wxPay($config);


//-----------------------------------------------------------
//二维码付款
$price = 0.1;
$out_trade_no = '202012191805126709';
$trade_type = 'NATIVE';

//获取二维码
$res = $wxpay->pay($price, $out_trade_no, $trade_type);

print_r($res);
/*
 推荐jquery插件将地址转二维码

   Array
    (
        [code] => 1
        [msg] => 获取微信二维码支付地址成功
        [code_url] => weixin://wxpay/bizpayurl?pr=Bdmjdwf00
    )
*/

//-----------------------------------------------------------
//调起app
$out_trade_no = '202012191805136709';
//本站唯一订单id
$trade_type = 'MWEB';
//将付款方式改成MWEB
$res = $wxpay->pay($price, $out_trade_no, $trade_type);
print_r($res);
/*
前段自动跳转到mweb_url就可以调起app

  Array
    (
       [code] => 1
       [msg] => 微信端调起成功
       [mweb_url] => https://wx.tenpay.com/cgi-bin/mmpayweb-bin/checkmweb?prepay_id=wx19175936543308e1cf16667d2eb9690000&package=2635019108
    )
*/


//-----------------------------------------------------------
//订单查询
$out_trade_no = '202042191805126709';
$res = $wxpay->queryOrder($out_trade_no);
print_r($res);
/*
 Array
    (
       [code] => 1
       [msg] => 支付成功
    )

      */