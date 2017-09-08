<?php
class Wechat {

    private $signature = '';
    private $timestamp = '';
    private $nonce = '';
    private $token = ''; //Token(令牌)
    private $msgcrypt = '';
    private $hasAES = false;
    private $data = array();


    public function __construct($token) {
        $this->signature = $_GET["signature"];
        $this->timestamp = $_GET["timestamp"];
        $this->nonce = $_GET["nonce"];
        $this->token = $token;
        $this->msg_signature = $_GET["msg_signature"];
        $this->auth() || exit;
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
            echo($_GET['echostr']);
            exit;
        }else {
            //可根据token参数获取相应的加密密钥、AppID等参数
            $encodingAESKey = 'D0o8aAIZNfxWJ2EeGmKheadc9Vpe8OZZ8YcbXtLqPUW'; //EncodingAESKey(消息加密密钥)
            $appId = 'wx22f0f5085f846137'; //AppID(微信公众号或者小程序ID)

            $this->msgcrypt = new WXBizMsgCrypt($this->token, $encodingAESKey, $appId);
            $xml = $GLOBALS["HTTP_RAW_POST_DATA"];
            if(empty($xml)) {
                $xml = file_get_contents("php://input");
            }
            if(isset($_GET['encrypt_type']) && strtolower($_GET['encrypt_type']) == 'aes' ){
                //有加密信息
                $decryptMsg = $this->decryptMsg($xml);
                if($decryptMsg === false){
                    //TODO: 解密失败
                }else{
                    $xml = $decryptMsg;
                    $this->hasAES = true;
                }
            }
            $this->data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
    }

    /**
     * 	作用：产生随机字符串，不长于32位
     */
    public function createNoncestr( $length = 32 ) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     *  [encryptMsg 加密消息]
     *  @param  [type] $xml            [待加密消息XML格式]
     *  @param  [type] $encodingAesKey [43位的encodingAesKey]
     *  @param  [type] $token          [token]
     *  @param  [type] $appId          [公众号APPID]
     *  @return [type]                 [false标识解密失败，否则为加密后的字符串]
     */
    private function encryptMsg($xml) {
        $timestamp = time();
        $nonce = $this->createNoncestr(9);
        $encryptMsg = '';
        $errCode = $this->msgcrypt->encryptMsg($xml, $timestamp, $nonce, $encryptMsg);
        if ($errCode == 0) {
            return $encryptMsg;
        } else {
            return false;
        }
    }
    /**
     *  [decryptMsg 解密消息体]
     *  @param  [type] $encryptMsg [加密的消息体]
     *  @return [type]             [false =>解密失败，否则为解密后的消息]
     */
    private function decryptMsg($encryptMsg) {
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        //$array_s = $xml_tree->getElementsByTagName('MsgSignature');
        $encrypt = $array_e->item(0)->nodeValue;
        //$msg_sign = $array_s->item(0)->nodeValue;

        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);

        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $this->msgcrypt->decryptMsg($this->msg_signature, $this->timestamp, $this->nonce, $from_xml, $msg);
        if ($errCode == 0) {
            return $msg;
        } else {
            return false;
        }
    }

    /**
     * 获取微信推送的数据
     * @return array 转换为数组后的数据
     */
    public function request() {
        return $this->data;
    }
    /**
     * * 响应微信发送的信息（自动回复）
     * @param  string $to      接收用户名
     * @param  string $from    发送者用户名
     * @param  array  $content 回复信息，文本信息为string类型
     * @param  string $type    消息类型
     * @param  string $flag    是否新标刚接受到的信息
     * @return string          XML字符串
     */
    public function response($content, $type = 'text', $flag = 0) {
        $this->data = array('ToUserName' => $this->data['FromUserName'], 'FromUserName' => $this->data['ToUserName'], 'CreateTime' => time(), 'MsgType' => $type);
        /* 添加类型数据 */
        $this->$type($content);
        /* 添加状态 */
        $this->data['FuncFlag'] = $flag;
        /* 转换数据为XML */
        $xml = new SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $this->data);
        if($this->hasAES){
            exit($this->encryptMsg($xml->asXML()));
        }else{
            exit($xml->asXML());
        }
    }
    /**
     * 回复文本信息
     * @param  string $content 要回复的信息
     */
    private function text($content) {
        $this->data['Content'] = $content;
    }
    /**
     * 回复音乐信息
     * @param  string $content 要回复的音乐
     */
    private function music($music) {
        list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
        $this->data['Music'] = $music;
    }
    /**
     * 回复图文信息
     * @param  string $news 要回复的图文内容
     */
    private function news($news) {
        $articles = array();
        foreach ($news as $key => $value) {
            list($articles[$key]['Title'], $articles[$key]['Description'], $articles[$key]['PicUrl'], $articles[$key]['Url']) = $value;
            if ($key >= 9) {
                break;
            }//最多只允许10调新闻
        }
        $this->data['ArticleCount'] = count($articles);
        $this->data['Articles'] = $articles;
    }
    private function transfer_customer_service($content) {
        $this->data['Content'] = '';
    }
    private function data2xml($xml, $data, $item = 'item') {
        foreach ($data as $key => $value) {
            /* 指定默认的数字key */
            is_numeric($key) && $key = $item;
            /* 添加子元素 */
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            }else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                }else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }
    private function auth() {
        $tmpArr = array($this->token, $this->timestamp, $this->nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if (trim($tmpStr) == trim($this->signature)) {
            return true;
        }else {
            return false;
        }
    }
}