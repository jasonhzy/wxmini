<?php
/**
 * @desc: 微信小程序支付demo
 *
 * 支付参考文档：https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=7_3&index=1
 * 小程序开发参考文档：https://mp.weixin.qq.com/debug/wxadoc/dev/index.html?t=2017621
 *      1、wx.request(OBJECT)参考文档: https://mp.weixin.qq.com/debug/wxadoc/dev/api/network-request.html
 *      2、wx.requestPayment(OBJECT)参考文档: https://mp.weixin.qq.com/debug/wxadoc/dev/api/api-pay.html#wxrequestpaymentobject
 *      3、获取openid参考文档：https://mp.weixin.qq.com/debug/wxadoc/dev/api/api-login.html#wxloginobject
 *
 * @author: jason
 * @since:  2017-08-08 18:59
 */
$ret = array('resultCode' => 1);
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
switch($type){
    case 'openid':
        //小程序的appid和appsecret
        $appid = 'wx22f0f5085f846137';
        $appsecret = '3e5f684e2948de6f74b487386692940a';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        if(empty($code)){
            $ret['errMsg'] = '登录凭证code获取失败';
            exit(json_encode($ret));
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appsecret&js_code=$code&grant_type=authorization_code";

        $json = json_decode(file_get_contents($url));
        if(isset($json->errcode) && $json->errcode){
            $ret['errMsg'] = $json->errcode.', '.$json->errmsg;
            exit(json_encode($ret));
        }
        $openid = $json->openid;

        $ret['resultCode'] = 0;
        $ret['openid'] = $openid;
        exit(json_encode($ret));
        break;
    case 'send':
        //小程序的appid和appsecret
        $appid = 'wx22f0f5085f846137';
        $appsecret = '3e5f684e2948de6f74b487386692940a';
        $access_token = '';

        $openid = isset($_POST['openid']) ? trim($_POST['openid']) : ''; //小程序的openid
        if(empty($openid)){
            $ret['errMsg'] = '却少参数openid';
            exit(json_encode($ret));
        }
        //表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id
        $formid = isset($_POST['form_id']) ? trim($_POST['form_id']) : '';
        if(empty($formid)){
            $ret['errMsg'] = '却少参数form_id';
            exit(json_encode($ret));
        }

        //消息模板id
        $temp_id = 'eogEBS2i4VeS2rZfwda7kdkePLTcbmPm-wW1s7A0ky4';
        //获取access_token, 做缓存，expires_in：7200
        generate_token($access_token, $appid, $appsecret);

        $data['touser'] = $openid;
        $data['template_id'] = $temp_id;
        $data['page'] =  'pages/index/index'; //该字段不填则模板无跳转
        $data['form_id'] = $formid;
        $data['data'] = array(
            'keyword1' => array('value' => '小程序模板消息测试'),
            'keyword2' => array('value' => '100元'),
            'keyword3' => array('value' => '支付成功'),
            'keyword4' => array('value' => time() * 1000),
            'keyword5' => array('value' => '微信支付'),
            'keyword6' => array('value' => date('Y-m-d H:i:s')),
        );
        $data['emphasis_keyword'] = 'keyword5.DATA'; //模板需要放大的关键词，不填则默认无放大

        $send_url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
        $str = request($send_url, 'post', $data);
        $json = json_decode(request($send_url, 'post', $data));
        if(!$json){
            $ret['errMsg'] = $str;
            exit(json_encode($ret));
        }else if(isset($json->errcode) && $json->errcode){
            $ret['errMsg'] = $json->errcode.', '.$json->errmsg;
            exit(json_encode($ret));
        }
        $ret['resultCode'] = 0;
        exit(json_encode($ret));
        break;
    case 'pay':
        include_once('lib/WxPayPubHelper/WxPayPubHelper.php');

        $openid = isset($_POST['openid']) ? trim($_POST['openid']) : '';
        if(empty($openid)){
            $ret['errMsg'] = '缺少参数openid';
            exit(json_encode($ret));
        }

        $order = new UnifiedOrder_pub();
        $order->setParameter("openid", $openid);//商品描述
        $order->setParameter('out_trade_no', 'phpdemo' . $timestamp);
        $order->setParameter('total_fee', 1);
        $order->setParameter('trade_type', 'JSAPI');
        $order->setParameter('body', 'PHP微信小程序支付测试');
        $order->setParameter('notify_url', 'https://www.example.cn/a.php');

        $prepay_id = $order->getPrepayId();
        $jsApi = new JsApi_pub();
        $jsApi->setPrepayId($prepay_id);
        $jsApiParams = json_decode($jsApi->getParameters());

        $ret['resultCode'] = 0;
        $ret['params'] = array(
                'appid' => $jsApiParams->appId,
                'timestamp' => $jsApiParams->timeStamp,
                'nonce_str' => $jsApiParams->nonceStr,
                'sign_type' => $jsApiParams->signType,
                'package' => $jsApiParams->package,
                'pay_sign' => $jsApiParams->paySign,
            );
        exit(json_encode($ret));
        break;
    default :
        $ret['errMsg'] = 'No this type : ' . $type;
        exit(json_encode($ret));
       break;
}

function generate_token(&$access_token, $appid, $appsecret){
    $token_file = '/tmp/token';
    $general_token = true;
    if(file_exists($token_file) && ($info = json_decode(file_get_contents($token_file)))){
        if(time() < $info->create_time + $info->expires_in - 200){
            $general_token = false;
            $access_token = $info->access_token;
        }
    }
    if($general_token){
        new_access_token($access_token, $token_file, $appid, $appsecret);
    }
}

function new_access_token(&$access_token, $token_file, $appid, $appsecret){
    $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
    $str = file_get_contents($token_url);
    $json = json_decode($str);
    if(isset($json->access_token)){
        $access_token = $json->access_token;
        file_put_contents($token_file, json_encode(array('access_token' => $access_token, 'expires_in' => $json->expires_in, 'create_time' => time())));
    }else{
        file_put_contents('/tmp/error', date('Y-m-d H:i:s').'-Get Access Token Error: '.print_r($json, 1).PHP_EOL, FILE_APPEND);
    }
}

function request($url, $method, array $data, $timeout = 30) {
    try {
        $ch = curl_init();
        /*支持SSL 不验证CA根验证*/
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        /*重定向跟随*/
        if (ini_get('open_basedir') == '' && !ini_get('safe_mode')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        //设置 CURLINFO_HEADER_OUT 选项之后 curl_getinfo 函数返回的数组将包含 cURL
        //请求的 header 信息。而要看到回应的 header 信息可以在 curl_setopt 中设置
        //CURLOPT_HEADER 选项为 true
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);

        //fail the request if the HTTP code returned is equal to or larger than 400
        //curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $header = array("Content-Type:application/json;charset=utf-8;", "Connection: keep-alive;");
        switch (strtolower($method)) {
            case "post":
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            case "put":
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            case "delete":
                curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case "get":
                curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data));
                break;
            case "new_get":
                curl_setopt($ch, CURLOPT_URL, $url."?para=".urlencode(json_encode($data)));
                break;
            default:
                throw new Exception('不支持的HTTP方式');
                break;
        }
        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    } catch (Exception $e) {
        return "CURL EXCEPTION: ".$e->getMessage();
    }
}