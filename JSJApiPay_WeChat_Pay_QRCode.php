<?php
/**
 * @WHMCS-JSJ-API-Pay-Gateway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016~2017
 * @license    MIT
 * @version    0.14-2017-06-05-01
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway
 * 
 */
 
require_once("JSJApiPay/JSJApiPay.class.php");

function JSJApiPay_WeChat_Pay_QRCode_config() {
    $configarray = array(
		"FriendlyName" => array("Type" => "System", "Value"=>"金沙江[微信扫码支付]免签 即时到账API接口 For WHMCS - Code By Tutugreen.com"),
		"apiid" => array("FriendlyName" => "合作伙伴ID(APIID)", "Type" => "text", "Size" => "25","Description" => "[必填]到你的API后台查找，没有账户的请在这里注册：http://api.jsjapp.com/", ),
		"apikey" => array("FriendlyName" => "安全检验码(APIKEY)", "Type" => "text", "Size" => "50", "Description" => "[必填]同上",),
		"fee_acc" => array("FriendlyName" => "记账手续费[仅显示]", "Type" => "text", "Size" => "50", "Description" => "[必填,不填会报错]默认0，如填写0.01，即是1%手续费，用于WHMCS记账时后台显示和统计，不影响实际支付价格。",),
		"debug" => array("FriendlyName" => "调试模式", "Type" => "yesno", "Description" => "调试模式,详细LOG请见[WHMCS]/download/JSJApiPay_log.php，使用文件管理或FTP等查看。", ),
    );
	return $configarray;
}

function JSJApiPay_WeChat_Pay_QRCode_link($params) {
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

    /********************************************************************************************************
    GET页面
	Iframe嵌套至 https://alipay.xunchu.net/pay/wx/native.php?apiid=【您的apiid】&total=【金额】&apikey=【MD5(您的apikey)】&uid=【支付的会员UID】&showurl=【您的回调地址】&addnum=【您的订单编号】
	订单编号规则：wx+您的apiid+20位以内数字字母
    
    ********************************************************************************************************/
	$JSJApiPay_WeChat_Pay_QRCode_config['input_charset'] = 'utf-8';
	$JSJApiPay_WeChat_Pay_QRCode_config['apiid'] = trim($params['apiid']);
	$JSJApiPay_WeChat_Pay_QRCode_config['apikey'] = trim($params['apikey']);
	$JSJApiPay_WeChat_Pay_QRCode_config['fee_acc'] = trim($params['fee_acc']);
	$debug = trim($params["debug"]);

	#Invoice Variables
	$amount = $params['amount']; # Format: ##.##
	$invoiceid = $params['invoiceid']; # Invoice ID Number
	$description = $params['description']; # Description (eg. Company Name - Invoice #xxx)
	$amount = $params['amount']; # Format: xxx.xx
	$currency = $params['currency']; # Currency Code (eg. GBP, USD, etc...

    #System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	
	#Special Variables

	#支付提示图片默认可选

	//创建订单后跳转至发票页面前Loading时显示的图片，依据需求可选底色和中英文PNG，一般情况下此图片不会展示较长时间(除非您站点服务器跳转较慢)。
	$img = $systemurl . "/modules/gateways/JSJApiPay/img/WeChat_Pay_NO_BG_zh_cn.png";
	//发票二维码嵌入ICON
	$QRCode_ICON_img = $systemurl . "/modules/gateways/JSJApiPay/img/WeChat_Pay_Money_Icon.png";
	
	#如需要指定HTTP/HTTPS可手动修改，参考格式：https://prpr.cloud/modules/gateways/callback/JSJApiPay_callback.php?payment_type=wechat_pay_qrcode&act=return
	#现已支持HTTPS地址-2016-11-13
	#$JSJApiPay_WeChat_Pay_QRCode_config['return_url'] = "";
	$system_url = $params['systemurl'];
	$JSJApiPay_WeChat_Pay_QRCode_config['return_url'] = $system_url . "/modules/gateways/callback/JSJApiPay_callback.php?payment_type=wechat_pay_qrcode&act=return";
	
	#API接口设定(此处使用特别接口)
	$JSJApiPay_WeChat_Pay_QRCode_config['api_url'] = "https://alipay.xunchu.net/pay/wx/native2.php";

	/*生成addnum参数:
	我们允许自定义订单传递过来，订单编号规则：wx+您的apiid+20位以内数字字母，变量为 $_POST['addnum'] 或 $_GET['addnum']
	其中，自定义参数必须为 数字、字母、或数字字母组合，不能超过20位。组合成功如：wx12345cd3d333233efeef690
	如果没有传递过来订单号，则系统自动生成订单号。除了商品类，一般的充值类不需要自定义订单号。
	PS！本接口文件已做好匹配生成，请不要随意修改！后方回调也会验证。
	*/

	$JSJApiPay_WeChat_Pay_QRCode_config['addnum'] = "wx".$JSJApiPay_WeChat_Pay_QRCode_config['apiid']."WeChatInvoces".$invoiceid;

	//基本参数
	$parameter = array(
	"_input_charset"=> trim(strtolower($JSJApiPay_WeChat_Pay_QRCode_config['input_charset'])),
	"addnum"        => trim($JSJApiPay_WeChat_Pay_QRCode_config['addnum']),
	"amount"        => trim($amount),
	"return_url"	=> trim($JSJApiPay_WeChat_Pay_QRCode_config['return_url']),
	"invoiceid"		=> trim($invoiceid),
	"apiid"		    => $JSJApiPay_WeChat_Pay_QRCode_config['apiid'],
	"apikey"		=> strtolower(md5($JSJApiPay_WeChat_Pay_QRCode_config['apikey'])),
	"api_url"		=> urlencode(trim($JSJApiPay_WeChat_Pay_QRCode_config['api_url'])),
	);

	//准备CURL POST表单参数
	$curl_create_qrcode_res_postfields = array(
		'addnum' => $parameter['addnum'],
		'total' => $parameter['amount'],
		'showurl' => $parameter['return_url'],
		'uid' => $parameter['invoiceid'],
		'apiid' => $parameter['apiid'],
		'apikey' => $parameter['apikey']
	);
	//创建CURL，向API获取二维码字符参数
	$curl_create_qrcode_res = curl_init();
	curl_setopt($curl_create_qrcode_res, CURLOPT_URL, $JSJApiPay_WeChat_Pay_QRCode_config['api_url']);
	curl_setopt($curl_create_qrcode_res, CURLOPT_POST, 1);
	curl_setopt($curl_create_qrcode_res, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl_create_qrcode_res, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($curl_create_qrcode_res, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl_create_qrcode_res, CURLOPT_SSL_VERIFYHOST, true);
	curl_setopt($curl_create_qrcode_res, CURLOPT_HEADER, 0);
	curl_setopt($curl_create_qrcode_res, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_create_qrcode_res, CURLOPT_POSTFIELDS, $curl_create_qrcode_res_postfields);
	curl_setopt($curl_create_qrcode_res, CURLOPT_USERAGENT, "WHMCS_PHP_CURL");
	//存储字符
	$curl_create_qrcode_res_data = trim(trim(curl_exec($curl_create_qrcode_res)), "\xEF\xBB\xBF");
	//关闭CURL
	curl_close($curl_create_qrcode_res);

	$html_code = <<<HTML_CODE
<!-- Powered By api.jsjapp.com , Coded By Tutugreen.com -->
<!-- Loading Required JS/CSS -->
<script type="text/javascript" src="//cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script type="text/javascript" src="//cdn.bootcss.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<!-- Jquery Polling Invoice & Check Result-->
<div class="JSJApiPay_WeChat_Pay_QRCode" style="max-width: 240px;margin: 0 auto">
	<div id="JSJApiPay_WeChat_Pay_QRCode_IMG" data-toggle="tooltip" data-placement="left" title="<h4>欢迎使用微信扫码支付</h4>" style="border: 1px solid #AAA;border-radius: 4px;overflow: hidden;margin-bottom: 5px;padding-top: 5px;">
		<!-- QRCode Should Be Display Here , Or Check Your Explorer Version.-->
		<img src="{$QRCode_ICON_img}" style="position: absolute;top: 50%;left: 50%;width:57.5px;height:57.5px;margin-left: -28.75px;margin-top: -14.375px">
	</div>
</div>
<button type="button" class="btn btn-success btn-block" onclick="location.reload();">刷新二维码</button>
<script>
	jQuery('#JSJApiPay_WeChat_Pay_QRCode_IMG').qrcode({
		width	:	230,
		height	:	230,
		text	:	'{$curl_create_qrcode_res_data}'
	});	
</script>
<script>
	$(function () { $("[data-toggle='tooltip']").tooltip({html : true }); });
</script>
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
		$msg="[JSJApiPay_WeChat_Pay_QRCode]订单: $invoiceid 生成支付表单 $html_code";
		JSJApiPay_logResult($msg);
	}
	//return $html_code;
	if (stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
		//return $html_code;
		if (stristr($curl_create_qrcode_res_data, 'weixin://wxpay/')) {
			return $html_code;
		} else {
			return "<center><b>二维码获取失败，请重试</b></center></br><button type=\"button\" class=\"btn btn-danger btn-block\" onclick=\"location.reload();\">重新获取二维码</button>";
		}
	} else {
		return "<img src='$img' alt='使用微信支付'>";
	}
}
?>
