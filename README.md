# WHMCS-JSJ-API-Pay-Gateway

为金沙江支付宝免签API写的WHMCS接口，难得的好接口。请合理使用。。

本接口用于WHMCS与金沙江API接口对接，以完成支付宝免签支付。

测试可用版本：WHMCS 6

理论支持5和7，未测试。

原作者不对使用该接口而产生的问题负责，请了解。

目前暂不支持手动"补单"，有需要的可以在WHMCS系统中使用"添加付款"的功能，请注意填写交易流水号为：JSJApiPay_alip开头的唯一流水号，以防止重复回调被刷余额。

【PS：这个接口**不是帮你挂个监控手动转账订单**，而是由金沙江的即时到账接口代收货款，可以申请提现至支付宝或微信。】

## 快速开始

首先在金沙江API后台 [注册一个账户](//api.jsjapp.com/plugin.php?id=add:user&apiid=12744&from=github)。

下载本接口，并把文件放到站点的/modules/gateways/里。

**修改 JSJApiPay.php 的74行，为你自己的WHMCS回调地址。**

进入WHMCS后台，系统设置-付款-支付网关-All Payment Gateways选项卡设置中：启用本接口，并在 Manage Existing Gateways 选项卡中填写APIID&APIKEY等，手续费仅用于WHMCS内部记账统计，但是必须填写（可以填0，WHMCS记账有手续费这么个特性，不会对实际支付金额产生影响）。

完成√

## 说明

代码仅供参考，有问题/BUG等请发Issue（请先确认您的WHMCS版本为6）。

使用的API为金沙江API：http://api.jsjapp.com/

依据金沙江官方PHP通用接口写的。未来官方有改动也会更新。

请了解这边订单号暂时是用UID做的，如果您的WHMCS系统订单数量超过99999999个，请联系我帮您修改为其他参数|´・ω・)ノ

PS：如果你需要吧手续费分摊给用户，请参考这个插件：[WHMCS-Gateway-Fees](https://github.com/delta360/WHMCS-Gateway-Fees) （第三方），本插件不会额外添加手续费（建议使用插件在之前添加，不建议在网关支付时添加，会产生取整影响回调判断的问题。）

（本来为自用，感觉金沙江提供的服务还是很不错的，所以公开了，请合理使用）

## 更新日志

最近更新:2017-06-01

2017-06-01：
修正一些注释错误

2016-11-18：
修复回调非子目录，导致404的问题

2017-04-17：
更新官方新域名，并支持HTTPS。
请注意！官方已修改接口域名，请在2017-04-17之前下载接口的用户尽快更新代码（对支付和之前的订单无任何影响）
如果在2017年04月17日0点整仍未更改的，接口将无法使用。

**官方现已支持 HTTPS 回调地址**

2016-11-03:
依据官方要求更新参数编号规范
请注意！官方已修改订单编号(addnum)参数编号规范，请在2016-11-03之前下载接口的用户尽快更新代码（对支付和之前的订单无任何影响）
如果在2016年11月10日0点整仍未更改的，接口将无法使用。(详情见：[官方贴](//api.jsjapp.com/forum.php?mod=viewthread&tid=52))

## 常见问题

Q：订单流水号存在"JSJApiPay"，是否可以修改？

A：可以的，修改 callback/JSJApiPay_callback.php 第 62 行即可，请注意保持唯一性以防刷单。


Q：如何修改用户支付完后跳转到的落地页面。

A：编辑 callback/JSJApiPay_callback.php 第 82 行，可修改完成支付验证后的地址。

## 非本接口问题请联系

金沙江问题请去[官方板块](//api.jsjapp.com/forum.php?mod=forumdisplay&fid=36)咨询

窝的Email：yuanming@tutugreen.com

## Copyright and license

Copyright 2016~2017 Tutugreen.com. Code released under [the MIT license](https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway/blob/master/LICENSE).
