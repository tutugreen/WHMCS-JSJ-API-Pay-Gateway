# WHMCS-JSJ-API-Pay-Gataway

为金沙江支付宝免签API写的WHMCS接口，难得的好接口。请合理使用。。

本接口用于WHMCS与金沙江API接口对接，以完成支付宝免签支付。

测试可用版本：WHMCS 6

理论支持5和7，未测试。

原作者不对使用该接口而产生的问题负责，请了解。

目前暂不支持手动"补单"，有需要的可以在WHMCS系统中使用"添加付款"的功能，请注意填写交易流水号为：JSJApiPay_alip开头的唯一流水号，以防止重复回调被刷余额。

## 快速开始

首先在金沙江API后台注册一个账户。

下载本接口，并把文件放到站点的/modules/gateways/里。

修改 JSJApiPay.php 的65行，为你自己的WHMCS回调地址。

进入WHMCS后台，系统设置-付款-支付网关-All Payment Gateways选项卡设置中：启用本接口，并在 Manage Existing Gateways 选项卡中填写APIID&APIKEY等，手续费仅用于WHMCS内部记账统计，但是必须填写（可以填0，WHMCS记账有手续费这么个特性，不会对实际支付金额产生影响）。

完成√

## 说明

最近更新:2016-10-07

代码仅供参考，有问题/BUG等请发Issue（请先确认您的WHMCS版本为6）。

使用的API为金沙江API：http://api.web567.net/

依据金沙江官方PHP通用接口写的。未来官方有改动也会更新。

请了解这边订单号暂时是用UID做的，如果您的WHMCS系统订单数量超过99999999个，请联系我帮您修改为其他参数。。。。。

PS：如果你需要吧手续费分摊给用户，请参考这个插件：[WHMCS-Gateway-Fees](https://github.com/delta360/WHMCS-Gateway-Fees) （第三方），本插件不会额外添加手续费（建议使用插件在之前添加，不建议在网关支付时添加，会产生取整影响回调判断的问题。）

（本来为自用，感觉金沙江提供的服务还是很不错的，所以公开了，合理使用。）

## 非本接口问题请联系

Email：yuanming@tutugreen.com

## Copyright and license

Copyright 2016 Tutugreen.com. Code released under [the MIT license](https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gataway/blob/master/LICENSE).
