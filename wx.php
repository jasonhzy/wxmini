<?php
/**
 * @desc: 微信小程序入口文件
 *
 * @author: jason
 * @since:  2017-09-06 11:07
 */
require_once 'lib/wxapi.php';
$wx = Wxapi::getInstance();
$wx->index();