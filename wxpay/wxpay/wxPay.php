<?php
// +----------------------------------------------------------------------
// | 使用时将配置改成自己的
// +----------------------------------------------------------------------
class wxPay {
    private $appid = ''; //appid
    private $macid = ''; //商户id
    private $key = ''; //商户平台密钥
    private $notify_url = ''; //回调地址
    public function __construct($config = []) {
        $keys = ['appid' => 'APPID', 'macid' => '商户id', 'key' => '商户平台密钥', 'notify_url' => '回调地址'];
        foreach ($keys as $k => $value) {
            if (!isset($config[$k]) || $config[$k] == '') {
                throw new Exception($value . '参数没有设置或为空');
            }
            $this->$k = $config[$k];
        }
    }
    /**
     * trade_type = 支付方式
     *  NATIVE 验证成功 将返回支付的二维码地址，可以通过插件将改地址转成二维码
     *  MWEB，验证成功将返回一个url，挑战至改url可以调起微信app
     *
     *
     * @param $price //支付金额
     * @param $pay_number //订单编号
     * @param string $trade_type    //支付类型 mweb移动端调起支付   native 扫码支付
     * @return mixed|string
     */
    public function pay($price, $pay_number, $trade_type = "MWEB") {
        $money = $price * 100; //充值金额 微信支付单位为分
        $appid = $this->appid; //应用APPID
        $mch_id = $this->macid; //微信支付商户号
        $key = $this->key; //微信商户API密钥
        $out_trade_no = $pay_number; //订单id  穿过去，支付成功按订单id修改状态
        $nonce_str = $this->createNoncestr(); //随机字符串
        $body = "订单支付"; //内容
        $total_fee = $money; //金额
        $spbill_create_ip = $this->get_client_ip(); //终端IP
        $notify_url = $this->notify_url; //回调地址
        $scene_info = '{"h5_info":{"type":"Wap","wap_url":"http://qq52o.me","wap_name":"支付"}}'; //场景信息 必要参数
        $signA = "appid={$appid}&attach={$out_trade_no}&body={$body}&mch_id={$mch_id}&nonce_str={$nonce_str}&notify_url={$notify_url}&out_trade_no={$out_trade_no}&scene_info={$scene_info}&spbill_create_ip={$spbill_create_ip}&total_fee={$total_fee}&trade_type={$trade_type}";
        $strSignTmp = $signA . "&key={$key}"; //拼接字符串  注意顺序微信有个测试网址 顺序按照他的来 直接点下面的校正测试 包括下面XML  是否正确
        $sign = strtoupper(MD5($strSignTmp)); //签名
        $post_data = "<xml>
                    <appid>$appid</appid>
                    <mch_id>$mch_id</mch_id>
                    <body>$body</body>
                    <out_trade_no>$out_trade_no</out_trade_no>
                    <total_fee>$total_fee</total_fee>
                    <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                    <notify_url>$notify_url</notify_url>
                    <trade_type>$trade_type</trade_type>
                    <scene_info>$scene_info</scene_info>
                    <attach>$out_trade_no</attach>
                    <nonce_str>$nonce_str</nonce_str>
                    <sign>$sign</sign>
            </xml>";
        //拼接成XML 格式
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder"; //统一下单地址
        $dataxml = $this->postXmlCurl($post_data, $url); //后台POST微信传参地址  同时取得微信返回的参数
        $objectxml = (array)simplexml_load_string($dataxml, 'SimpleXMLElement', LIBXML_NOCDATA);
        //将微信返回的XML 转换成数
        if ($objectxml['return_code'] == 'SUCCESS') {
            switch ($trade_type) {
                case 'NATIVE':
                    //获取微信二维码支付地址
                    return ['code' => 1, 'msg' => '获取微信二维码支付地址成功', 'code_url' => $objectxml['code_url']];
                case 'MWEB':
                    //调起微信app支付
                    return ['code' => 1, 'msg' => '微信端调起成功', 'mweb_url' => $objectxml['mweb_url']];
                    return;
            }
        } else {
            //验证不通过
            print_r($objectxml);
        }
    }
    /**
     * 支付回调地址
     */
    public function notify() {
        //获取返回的xml
        $xml = file_get_contents("php://input");
        //将xml转化为json格式
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        //转成数组
        $result = json_decode($jsonxml, true);
        if ($result) {
            //如果成功返回了
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                //当return_code为success  并且 result_code也为succes才是真的支付完成
                $pay_number = $result['out_trade_no']; //接收返回的商家订单id进行业务操作
                //............业务逻辑。。。。。

            }
        }
    }
    /**
     * 查询订单是否支付
     * @param string $out_trade_no  要查询的订单
     */
    public function queryOrder($out_trade_no = '202012182012311131') {
        $appid = $this->appid; //应用APPID
        $mch_id = $this->macid; //微信支付商户号
        $key = $this->key; //微信商户API密钥
        $nonce_str = $this->createNoncestr(); //随机字符串
        $signA = "appid={$appid}&mch_id={$mch_id}&nonce_str={$nonce_str}&out_trade_no={$out_trade_no}";
        $strSignTmp = ($signA . "&key={$key}"); //拼接字符串  注意顺序微信有个测试网址 顺序按照他的来 直接点下面的校正测试 包括下面XML  是否正确
        $sign = strtoupper(MD5($strSignTmp)); //签名
        $post_data = "<xml>
                       <appid>{$appid}</appid>
                       <mch_id>{$mch_id}</mch_id>
                       <nonce_str>{$nonce_str}</nonce_str>
                       <out_trade_no >{$out_trade_no}</out_trade_no >
                       <sign>{$sign}</sign>
                    </xml>";
        //拼接成XML 格式
        $url = "https://api.mch.weixin.qq.com/pay/orderquery"; //统一下单地址
        $dataxml = $this->postXmlCurl($post_data, $url); //后台POST微信传参地址  同时取得微信返回的参数
        $objectxml = (array)simplexml_load_string($dataxml, 'SimpleXMLElement', LIBXML_NOCDATA);
        //将微信返回的XML 转换成数
        if ($objectxml['return_code'] == 'SUCCESS' && $objectxml['result_code'] == 'SUCCESS') {
            //支付状态为 succes支付成功
            if ($objectxml['trade_state'] == 'SUCCESS') {
                return ['code' => 1, 'msg' => '支付成功'];
            }
        }
        //支付失败或未支付
        return ['code' => 0, 'msg' => '支付失败/或未支付'];
    }
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str.= substr($chars, mt_rand(0, strlen($chars) - 1) , 1);
        }
        return $str;
    }
    private function postXmlCurl($xml, $url, $second = 30) {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            echo "curl出错，错误码:{$error}" . "<br>";
        }
    }
    private function get_client_ip($type = 0) {
        $type = $type ? 1 : 0;
        $ip = 'unknown';
        if ($ip !== 'unknown') {
            return $ip[$type];
        }
        if (isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP']) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            //客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //浏览当前页面的用户计算机的网关
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            //浏览当前页面的用户计算机的ip地址

        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array(
            $ip,
            $long
        ) : array(
            '0.0.0.0',
            0
        );
        return $ip[$type];
    }
}

