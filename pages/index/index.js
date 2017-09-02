//index.js
//获取应用实例
var app = getApp()
Page({
  data: {
    motto: '微信小程序支付',
    userInfo: {},
  },
  onLoad: function () {//生命周期函数--监听页面加载
    var that = this
    //调用应用实例的方法获取全局数据
    app.getUserInfo(function (userInfo) {
      //更新数据
      that.setData({
        userInfo: userInfo
      })
    });
    //登陆获取code
    var openid = wx.getStorageSync('openid') || '';
    if (!openid) {
      wx.login({
        success: function (res) {
          console.log(res);
          that.getOpenId(res.code);
        }
      });
    }
  },
  onShareAppMessage: function () {
    return {
      title: '微信小程序支付',
      path: 'pages/index/index',
      success: function (res) {
        // 分享成功
      },
      fail: function (res) {
        // 分享失败
      }
    }
  },
  //事件处理函数
  bindViewTap: function() {
    wx.navigateTo({
      url: '../logs/logs'
    })
  },
  showInfo: function (msg) {
    wx.showModal({
      title: '提示',
      showCancel : false,
      content: msg
    });
  },
  getOpenId: function (code) {
    var that = this;
    wx.request({
      url: "https://phpdemo.beecloud.cn/wxmini.php",
      data: {
        code: code,
        type: 'openid'
      },
      header: {
        'content-type': 'application/x-www-form-urlencoded'
      },
      method: 'POST',
      success: function (res) {
        console.log(res);
        if (res.data.resultCode != 0) {
          that.showInfo(res.data.errMsg);
          return;
        }
        wx.setStorageSync('openid', res.data.openid);
      },
      fail: function () {
        // fail
      },
      complete: function (openid) {
        // complete
      }
    });
  },
  wxpay: function () {
    var openid = wx.getStorageSync('openid');
    if(!openid){
      that.showInfo('获取openid信息失败');
      return;
    }
    this.generateOrder(openid);
  },
  generateOrder: function (openid) {
    var that = this;
    wx.request({
      url: "https://phpdemo.beecloud.cn/wxmini.php",
      data: {
        type: 'pay',
        openid : openid
      },
      header: {
        'content-type': 'application/x-www-form-urlencoded'
      },
      method: 'POST',
      success: function (res) {
        console.log(res);
        if (res.data.resultCode != 0) {
          that.showInfo(res.data.errMsg);
          return;
        }
        that.pay(res.data.params);
      },
      fail: function () {
        // fail
      },
      complete: function () {
        // complete
      }
    })
  },
  pay: function (param) {
    var that = this;
    wx.requestPayment({
      'timeStamp': param.timestamp,
      'nonceStr': param.nonce_str,
      'package': param.package,
      'signType': param.sign_type,
      'paySign': param.pay_sign,
      success: function (res) {
        // success
        console.log(res);
        that.showInfo('支付成功');
      },
      fail: function (res) {
        // fail
        console.log(res);
        var strMsg = res.errMsg;
        if (res.err_desc){
          strMsg += ', ' + res.err_desc;
        }
        that.showInfo(strMsg);
      },
      complete: function () {
        // complete
        console.log("pay complete");
      }
    });
  },
  send_temp : function(e){
    var that = this;
    var openid = wx.getStorageSync('openid');
    if (!openid) {
      that.showInfo('获取openid信息失败');
      return;
    }
    wx.request({
      url: 'https://phpdemo.beecloud.cn/wxmini.php',
      data: {
        'form_id': e.detail.formId,
        'openid': openid,
        type: 'send',
      },
      header: {
        'content-type': 'application/x-www-form-urlencoded'
      },
      method: 'POST',
      success: function(res){
        // success
        console.log(res);
        if (res.data.resultCode != 0){
          that.showInfo(res.data.errMsg);
          return;
        }
        that.showInfo('发送成功');
        // console.log(e.detail.formId);
      },
      fail: function(err) {
        // fail
        console.log('失败');
        console.log(res);
      },
      complete: function() {
        // complete
      }
    });
  }
})