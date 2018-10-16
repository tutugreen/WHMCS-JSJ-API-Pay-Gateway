<?php
/**
 * @WHMCS-JSJ-API-Pay-Gateway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016~2018
 * @license    MIT
 * @version    0.20-2018-10-09-01
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway
 * 
 */

require_once("JSJApiPay/JSJApiPay.class.php");

function JSJApiPay_Alipay_Web_config() {
    $configarray = array(
		"FriendlyName" => array("Type" => "System", "Value"=>"金莎云[云发卡] 支付宝 即时到账API接口 For WHMCS - Code By Tutugreen.com"),
		"apiid" => array("FriendlyName" => "合作伙伴ID(APIID)", "Type" => "text", "Size" => "25","Description" => "[必填]到你的API后台查找，没有账户的请在 <a href=\"http://api.jsjapp.com/plugin.php?id=add:user&apiid=12744&from=whmcs\" target=\"_blank\" onclick=\"return confirm('此链接为邀请链接，是否同意接口开发者成为阁下的邀请人？')\">这里注册</a> ", ),
		"apikey" => array("FriendlyName" => "安全检验码(APIKEY)", "Type" => "text", "Size" => "50", "Description" => "[必填]同上",),
		"fee_acc" => array("FriendlyName" => "记账手续费[仅显示]", "Type" => "text", "Size" => "50", "Description" => "[必填,不填会报错]默认0，如填写0.01，即是1%手续费，用于WHMCS记账时后台显示和统计，不影响实际支付价格。",),
		"debug" => array("FriendlyName" => "调试模式", "Type" => "yesno", "Description" => "调试模式,详细LOG请见[WHMCS]/download/JSJApiPay_log.php，使用文件管理或FTP等查看。", ),
    );
	return $configarray;
}

function JSJApiPay_Alipay_Web_link($params) {
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

	$JSJApiPay_Alipay_Web_config['input_charset'] = 'utf-8';
	$JSJApiPay_Alipay_Web_config['apiid'] = trim($params['apiid']);
	$JSJApiPay_Alipay_Web_config['apikey'] = trim($params['apikey']);
	$JSJApiPay_Alipay_Web_config['fee_acc'] = trim($params['fee_acc']);
	$debug = trim($params["debug"]);

	#Invoice Variables
	$amount = trim($params['amount']); # Format: ##.##
	$invoiceid = $params['invoiceid']; # Invoice ID Number
	$description = $params['description']; # Description (eg. Company Name - Invoice #xxx)
	$amount = $params['amount']; # Format: xxx.xx
	$currency = $params['currency']; # Currency Code (eg. GBP, USD, etc...

    #System Variables
	$companyname = $params['companyname'];
	$system_url = rtrim($params['systemurl'], "/");
	
	#Special Variables

	#支付提示图片默认可选
	$img["Alipay_Logo"] = $system_url . "/modules/gateways/JSJApiPay/assets/images/Alipay/Alipay_Web_Logo_342x120.png";
	$img["Alipay_Button"] = $system_url . "/modules/gateways/JSJApiPay/assets/images/Alipay/Alipay_02.png";

	//判断是否已抵达账单页面，兼容手续费插件，减少账单金额更改几率
	if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
		return "<img src='".$img["Alipay_Logo"]."' alt='使用支付宝支付'>";
	}

    //转换订单金额
    if($amount<=9.99){
        $JSJApiPay_Alipay_Web_config['card_type']=1;
        $JSJApiPay_Alipay_Web_config['card_number']=ceil($amount/0.01);
        $JSJApiPay_Alipay_Web_config['card_total']=$JSJApiPay_Alipay_Web_config['card_number']*0.01;
    }else if($amount<=99.9){
        $JSJApiPay_Alipay_Web_config['card_type']=2;
        $JSJApiPay_Alipay_Web_config['card_number']=ceil($amount/0.1);
        $JSJApiPay_Alipay_Web_config['card_total']=$JSJApiPay_Alipay_Web_config['card_number']*0.1;
    }else if($amount<=999){
        $JSJApiPay_Alipay_Web_config['card_type']=3;
        $JSJApiPay_Alipay_Web_config['card_number']=ceil($amount/1);
        $JSJApiPay_Alipay_Web_config['card_total']=$JSJApiPay_Alipay_Web_config['card_number']*1;
    }else if($amount<=9990){
        $JSJApiPay_Alipay_Web_config['card_type']=4;
        $JSJApiPay_Alipay_Web_config['card_number']=ceil($amount/10);
        $JSJApiPay_Alipay_Web_config['card_total']=$JSJApiPay_Alipay_Web_config['card_number']*10;
    }else if($amount<=99900){
        $JSJApiPay_Alipay_Web_config['card_type']=5;
        $JSJApiPay_Alipay_Web_config['card_number']=ceil($amount/100);
        $JSJApiPay_Alipay_Web_config['card_total']=$JSJApiPay_Alipay_Web_config['card_number']*100;
    }else{
        echo "订单金额超限，请联系客服支持。";
        return;
    }
	
	#API接口设定(此处使用特别接口)
	$JSJApiPay_Alipay_Web_config['api_url'] = "https://yun.jsjapp.com/k/show.php?u=".$JSJApiPay_Alipay_Web_config['apiid']."&k=".$JSJApiPay_Alipay_Web_config['card_type']."&g=".$invoiceid;
	$JSJApiPay_Alipay_Web_config['api_url_order'] = "https://yun.jsjapp.com/k/order.php?suid=".$invoiceid;

	//获取平台订单号
	$curl_create_order_res = curl_init();
	curl_setopt($curl_create_order_res, CURLOPT_URL, $JSJApiPay_Alipay_Web_config['api_url']);
	curl_setopt($curl_create_order_res, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl_create_order_res, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($curl_create_order_res, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl_create_order_res, CURLOPT_SSL_VERIFYHOST, true);
	curl_setopt($curl_create_order_res, CURLOPT_HEADER, 0);
	curl_setopt($curl_create_order_res, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_create_order_res, CURLOPT_USERAGENT, "WHMCS_PHP_CURL");
	//存储字符
	$curl_create_order_res_data = trim(trim(curl_exec($curl_create_order_res)), "\xEF\xBB\xBF");
	//关闭CURL
	curl_close($curl_create_order_res);
    //抽取平台订单号
	$JSJApiPay_Alipay_Web_config['addnum'] = trim(trim(get_string_between($curl_create_order_res_data, 'addnum:"', '"')));

	//准备获取订单链接参数
	$curl_create_order_link_res_postfields = array(
    	"_input_charset"=> trim(strtolower($JSJApiPay_Alipay_Web_config['input_charset'])),
    	"apiid" => $JSJApiPay_Alipay_Web_config['apiid'],
    	"addnum" => $JSJApiPay_Alipay_Web_config['addnum'],
    	"total" => number_format($JSJApiPay_Alipay_Web_config['card_total'],2,".",""),
    	"usermail" => "",
    	"num" => $JSJApiPay_Alipay_Web_config['card_number'],
    	"tid" => $JSJApiPay_Alipay_Web_config['card_type'],
    	"tel" => "13".date('dH',time()).rand(10000,99999),
    	"paylei" => "1",//Alipay:1,WeChat:2,QQ:3
	);

	//提交获取订单链接
	$curl_create_order_link_res = curl_init();
	curl_setopt($curl_create_order_link_res, CURLOPT_URL, $JSJApiPay_Alipay_Web_config['api_url_order']);
	curl_setopt($curl_create_order_link_res, CURLOPT_POST, 1);
	curl_setopt($curl_create_order_link_res, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl_create_order_link_res, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($curl_create_order_link_res, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl_create_order_link_res, CURLOPT_SSL_VERIFYHOST, true);
	curl_setopt($curl_create_order_link_res, CURLOPT_HEADER, 0);
	curl_setopt($curl_create_order_link_res, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_create_order_link_res, CURLOPT_POSTFIELDS, $curl_create_order_link_res_postfields);
	curl_setopt($curl_create_order_link_res, CURLOPT_USERAGENT, "WHMCS_PHP_CURL");
	//存储字符 
	$curl_create_order_link_res_data = trim(trim(curl_exec($curl_create_order_link_res)), "\xEF\xBB\xBF");
	//关闭CURL
	curl_close($curl_create_order_link_res);

	//获取支付表单
	$curl_create_form_res = curl_init();
	curl_setopt($curl_create_form_res, CURLOPT_URL, $curl_create_order_link_res_data);
	curl_setopt($curl_create_form_res, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl_create_form_res, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($curl_create_form_res, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($curl_create_form_res, CURLOPT_SSL_VERIFYHOST, true);
	curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1);//For 302
	curl_setopt($curl_create_form_res, CURLOPT_HEADER, 0);
	curl_setopt($curl_create_form_res, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_create_form_res, CURLOPT_USERAGENT, "WHMCS_PHP_CURL Alipay Web");//Use QQ user-agent
	//存储字符
	$curl_create_form_res_data = trim(trim(curl_exec($curl_create_form_res)), "\xEF\xBB\xBF");
    $curl_create_form_res_url = trim(trim(curl_getinfo($curl_create_form_res)["redirect_url"]));
	//关闭CURL
	curl_close($curl_create_form_res);

	if ($debug) {
		$msg="[YM01ApiPay_Alipay_Web]订单: $invoiceid 生成支付表单 $html_code";
		JSJApiPay_logResult($msg);
	}
	$no_service_provider_in_error_message = "false"; //如不希望在错误信息中展示金沙江连接，请设置为true。
	//return $html_code;
	if (stristr($curl_create_form_res_data, 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8')) {
		$curl_create_form_res_data = get_string_between($curl_create_form_res_data, "method='get'>", "<input type='submit'");
		if (!stristr($curl_create_form_res_data, $JSJApiPay_Alipay_Web_config['addnum'])) {
			return "<center><b>网关通讯出现偏差，请重试</b></center><button type=\"button\" class=\"btn btn-danger btn-block\" style=\"margin-top: 10px;\" onclick=\"location.reload();\">重新初始化</button>";
		}
	} else {
		return "<center><b>网关通讯出现偏差，请重试</b></center><button type=\"button\" class=\"btn btn-danger btn-block\" style=\"margin-top: 10px;\" onclick=\"location.reload();\">重新初始化</button>";
	}
	$html_code = <<<HTML_CODE
<!-- Powered By api.jsjapp.com , Coded By Tutugreen.com -->
<!-- Loading Required JS/CSS -->
<script type="text/javascript" src="//cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script type="text/javascript" src="//cdn.bootcss.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<!-- Jquery Polling Invoice & Check Result-->
<form id='YM02ApiPay_Alipay_Web_form' name='YM02ApiPay_Alipay_Web_form' action='https://mapi.alipay.com/gateway.do?_input_charset=utf-8' method='post'>
{$curl_create_form_res_data}
</form>
<a href="#" onclick="document.forms['YM02ApiPay_Alipay_Web_form'].submit();">
    <img src="{$img["Alipay_Button"]}" alt="点击使用支付宝支付">
</a>
<script>jQuery(document).ready(function() {
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
	return $html_code;
}
?>