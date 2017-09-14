<?php
/**
 * @desc: wx mini api
 *
 * @author: jason
 * @since:  2017-09-06 10:53
 */
include_once 'wechat.php';
class Wxapi {
    private static $_instance = null;

    private $token;
    private $weixin;
    private $data = array();

    private function __construct() { }

    public function __clone() {
        file_put_contents('/tmp/wxmini', date('Y-m-d H:i:s') . '-clone is forbidden'.PHP_EOL, FILE_APPEND);
    }

    public static function getInstance() {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function index()  {
        $thisurl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $rt = explode('wx.php', $thisurl);
        $arg = isset($rt[1]) ? $rt[1] : '';
        if(!empty($arg)){
            $rt = explode('/',$arg);
            $arg = isset($rt[1]) ? $rt[1] : '';
            if(!empty($arg)){
                $this->token = trim($arg);
            }
        }
        file_put_contents('/tmp/wxmini', date('Y-m-d H:i:s') . print_r($rt, 1).PHP_EOL, FILE_APPEND);
        if (!class_exists('SimpleXMLElement')){
            file_put_contents('/tmp/wxmini', date('Y-m-d H:i:s') . '-SimpleXMLElement class not exist'.PHP_EOL, FILE_APPEND);
        }
        if (!function_exists('dom_import_simplexml')){
            file_put_contents('/tmp/wxmini', date('Y-m-d H:i:s') . '-dom_import_simplexml function not exist'.PHP_EOL, FILE_APPEND);
        }
        if(!preg_match("/^[0-9a-zA-Z]{3,42}$/", $this->token)){
            file_put_contents('/tmp/wxmini', date('Y-m-d H:i:s') . '-error token, only support characters and number'.PHP_EOL, FILE_APPEND);
        }
        $this->weixin = new Wechat($this->token);
        $this->data = $this->weixin->request();
        if ($this->data) {
            list($content, $type) = $this->reply($this->data);
            if($type){
                $this->weixin->response($content, $type);
            }
        }
    }

    private function reply($data){
        file_put_contents('/tmp/wechat', date('Y-m-d H:i:s').'-'.json_encode($data).PHP_EOL, FILE_APPEND);
        if (isset($data['Event'])) {
            $event = strtolower($data['Event']);
            switch ($event) {
                case 'subscribe': //不带参数/带参数的二维码
                    break;
                case 'unsubscribe'://取消关注
                    break;
                case 'scan'://带参数的二维码
                    break;
                case 'location'://自动获取位置回复
                    break;
                case 'templatesendjobfinish':
                    break;
                case 'masssendjobfinish': //群发
                    break;
                case 'click'://自定义菜单
                    break;
                default:
                    break;
            }
        }else if (isset($data['MsgType'])) {
            //消息转发到客服
            return array('turn on transfer_customer_service', 'transfer_customer_service');
        }
    }
}