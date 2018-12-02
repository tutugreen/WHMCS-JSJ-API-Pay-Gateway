<?php
/**
 * @WHMCS-JSJ-API-Pay-Gateway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016~2019
 * @license    MIT
 * @version    0.21-2018-12-02-01
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway
 *
 */

require_once("JSJApiPay/JSJApiPay.class.php");

function JSJApiPay_Card_Redeem_config() {
	$configarray = array(
		"FriendlyName" => array("Type" => "System", "Value"=>"金莎云[云锁] 卡密兑换 - Code By Tutugreen"),
		"apiid" => array("FriendlyName" => "合作伙伴ID(APIID)", "Type" => "text", "Size" => "25","Description" => "[必填]到你的API后台查找，没有账户的请在 <a href=\"https://yun.jsjapp.com/reg.php?tg=MjTsIj35N2D1Q&from=whmcs\" target=\"_blank\" onclick=\"return confirm('此链接为邀请链接，是否同意接口开发者成为阁下的邀请人？\n邀请通过后阁下将获得10000积分奖励。\nPS：走邀请链接属自愿项目。非正规业务请勿使用。')\">这里注册</a> ", ),
		"apikey" => array("FriendlyName" => "安全检验码(APIKEY)", "Type" => "text", "Size" => "50", "Description" => "[必填]同上",),
		"buy_link" => array("FriendlyName" => "购卡页面地址", "Type" => "text", "Size" => "50", "Description" => "[必填]引导用户购买卡密。",),
		"card_amount_1" => array("FriendlyName" => "卡面金额(1/5)", "Type" => "text", "Size" => "50", "Description" => "[必填]准确填写，例：100.00",),
		"card_amount_2" => array("FriendlyName" => "卡面金额(2/5)", "Type" => "text", "Size" => "50", "Description" => "[必填]准确填写，例：100.00",),
		"card_amount_3" => array("FriendlyName" => "卡面金额(3/5)", "Type" => "text", "Size" => "50", "Description" => "[必填]准确填写，例：100.00",),
		"card_amount_4" => array("FriendlyName" => "卡面金额(4/5)", "Type" => "text", "Size" => "50", "Description" => "[必填]准确填写，例：100.00",),
		"card_amount_5" => array("FriendlyName" => "卡面金额(5/5)", "Type" => "text", "Size" => "50", "Description" => "[必填]准确填写，例：100.00",),
		"fee_acc" => array("FriendlyName" => "记账手续费[仅显示]", "Type" => "text", "Size" => "50", "Description" => "[必填,不填会报错]默认0，如填写0.01，即是1%手续费，用于WHMCS记账时后台显示和统计，不影响实际支付价格。",),
		"debug" => array("FriendlyName" => "调试模式", "Type" => "yesno", "Description" => "调试模式,详细LOG请见[WHMCS]/download/JSJApiPay_log.php，使用文件管理或FTP等查看。", ),
	);
	return $configarray;
}

function JSJApiPay_Card_Redeem_link($params) {
	if (!isset($params['apiid'])) {
	echo '$apiid(合作伙伴ID) 为必填项目，请在后台-系统设置-付款-支付接口，Manage Existing Gateways 选项卡中设置。';
	exit;
	}
	if (!isset($params['apikey'])) {
	echo '$apikey(安全检验码) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
	exit;
	}
	if (!isset($params['fee_acc'])) {
	echo '$fee_acc(记账手续费) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
	exit;
	}
	//判断是否需要获取二维码，排除轮询请求节省服务器资源
	if ($_POST['noqrcode'] or $_GET['noqrcode']){
		$noqrcode = trim($_POST['noqrcode'] ? $_POST['noqrcode'] : $_GET['noqrcode']);;
	}else{
		$noqrcode = "false";
	}
	if ($noqrcode == "true") {
		//轮询请求不需要返回二维码和表单，直接退出
		return;
	}

	$JSJApiPay_Card_Redeem_config['input_charset'] = 'utf-8';
	$JSJApiPay_Card_Redeem_config['apiid'] = trim($params['apiid']);
	$JSJApiPay_Card_Redeem_config['apikey'] = trim($params['apikey']);
	$JSJApiPay_Card_Redeem_config['fee_acc'] = trim($params['fee_acc']);
	$JSJApiPay_Card_Redeem_config['buy_link'] = trim($params['buy_link']);
	$JSJApiPay_Card_Redeem_config['card_amount_1'] = trim($params['card_amount_1']);
	$JSJApiPay_Card_Redeem_config['card_amount_2'] = trim($params['card_amount_2']);
	$JSJApiPay_Card_Redeem_config['card_amount_3'] = trim($params['card_amount_3']);
	$JSJApiPay_Card_Redeem_config['card_amount_4'] = trim($params['card_amount_4']);
	$JSJApiPay_Card_Redeem_config['card_amount_5'] = trim($params['card_amount_5']);
	$debug = trim($params["debug"]);

	#Invoice Variables
	$amount = $params['amount']; # Format: ##.##
	$invoiceid = $params['invoiceid']; # Invoice ID Number
	$description = $params['description']; # Description (eg. Company Name - Invoice #xxx)
	$amount = $params['amount']; # Format: xxx.xx
	$currency = $params['currency']; # Currency Code (eg. GBP, USD, etc...

	#System Variables
	$companyname = $params['companyname'];
	$system_url = rtrim($params['systemurl'], "/");

	#Special Variables

	#如需要指定HTTP/HTTPS可手动修改，参考格式：https://prpr.cloud/modules/gateways/callback/JSJApiPay_callback.php?payment_type=card_redeem&act=return
	#$JSJApiPay_Card_Redeem_config['return_url'] = "";
	$JSJApiPay_Card_Redeem_config['return_url'] = $system_url . "/modules/gateways/callback/JSJApiPay_callback.php";

	/*生成addnum参数:
	单号格式:wx+您的apiid+字符串
	*/

	$JSJApiPay_Card_Redeem_config['addnum'] = "wx".$JSJApiPay_Card_Redeem_config['apiid']."001Invoice".$invoiceid."wx";

	//基本参数
	$parameter = array(
	"_input_charset"=> trim(strtolower($JSJApiPay_Card_Redeem_config['input_charset'])),
	"addnum"        => trim($JSJApiPay_Card_Redeem_config['addnum']),
	"amount"        => number_format(trim($amount),2,".",""),
	"return_url"	=> trim($JSJApiPay_Card_Redeem_config['return_url'])."&invoiceid=".trim($invoiceid),
	"invoiceid"		=> trim($invoiceid),
	"apiid"		    => $JSJApiPay_Card_Redeem_config['apiid'],
	"apikey"		=> strtolower(md5($JSJApiPay_Card_Redeem_config['apikey'])),
	"api_url"		=> urlencode(trim($JSJApiPay_Card_Redeem_config['api_url'])),
	);

	//判断是否已抵达账单页面，兼容手续费插件，减少账单金额更改几率
	if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
		return "处理中";
	}

	//tooltip提示，判断是否为移动端
	if (isMobile()) {
		$tooltip_QRCode_info='<div id="JSJApiPay_Card_Redeem_IMG" data-toggle="tooltip" data-placement="top" title="<h5>欢迎使用微信扫码支付，请使用微信扫一扫支付。</h5>" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;padding-top: 5px;">';
	} else {
		$tooltip_QRCode_info='<div id="JSJApiPay_Card_Redeem_IMG" data-toggle="tooltip" data-placement="left" title="<h4>欢迎使用微信扫码支付</h4>" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;padding-top: 5px;">';
	}

	$html_code = <<<HTML_CODE
<!-- Powered By yun.jsjapp.com , Coded By Tutugreen.com -->
							<!-- Loading Required JS/CSS -->
							<script type="text/javascript" src="//cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
							<script type="text/javascript" src="//cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
							<script type="text/javascript" src="//cdn.bootcss.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
							<form action="{$JSJApiPay_Card_Redeem_config['return_url']}" method="POST">
								<input type="hidden" name="payment_type" value="card_redeem">
								<input type="hidden" name="act" value="redeem">
								<input type="hidden" name="invoiceid" value="{$parameter['invoiceid']}">
								<fieldset class="form-group">
									<label for="card_type">选择卡类型</label>
									<select class="form-control" id="card_type" name="card_type">
										<option>请选择</option>
										<option value="1">{$JSJApiPay_Card_Redeem_config['card_amount_1']} CNY</option>
										<option value="2">{$JSJApiPay_Card_Redeem_config['card_amount_2']} CNY</option>
										<option value="3">{$JSJApiPay_Card_Redeem_config['card_amount_3']} CNY</option>
										<option value="4">{$JSJApiPay_Card_Redeem_config['card_amount_4']} CNY</option>
										<option value="5">{$JSJApiPay_Card_Redeem_config['card_amount_5']} CNY</option>
									</select>
									<small class="text-muted">需与卡面金额一致，结余充入账户余额。</small>
								</fieldset>
								<fieldset class="form-group">
									<label for="card">卡密</label>
									<input type="password" class="form-control" id="card" name="card" placeholder="例：21A0CCC6D16E99966756A87976E3A197">
									<small class="text-muted">账单可多次兑换，一次兑换一张。</small>
								</fieldset>
								<button type="submit" class="btn btn-primary">核销</button>
								<a class="btn btn-primary" href="{$JSJApiPay_Card_Redeem_config['buy_link']}" target="_blank">购买卡</a>
							</form>
							<!-- Jquery Polling Invoice & Check Result-->
							<script>
							jQuery(document).ready(function() {
								var paid_status = false
								var paid_timer = setInterval(function(){
									$.ajax({
										type: "post",
										url : window.location.href,
										data : {noqrcode: "true"},
										dataType : "text",
										success: function(data){
											if ( data.indexOf('class="'+"paid"+'"') != -1)
											{
												clearInterval(paid_timer)
												$('#paysuccess').modal('show')
												setTimeout(function(){location.reload()},3000)
											}
										}})
								},1500)
							})
							</script>
							<div class="modal fade" id="paysuccess">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<h4 class="modal-title"><p class="text-success">支付成功</p></h4>
										</div>
										<div class="modal-body">
											<p>本页面将在3秒后刷新</p>
										</div>
									</div>
								</div>
							</div>
HTML_CODE;

	if ($debug) {
		$msg="[YM01ApiPay_card_redeem]订单: $invoiceid 生成支付表单 $html_code";
		JSJApiPay_logResult($msg);
	}
	return $html_code;

}
?>
