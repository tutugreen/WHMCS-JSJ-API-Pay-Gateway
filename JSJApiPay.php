<?php
/**
 * @WHMCS-JSJ-API-Pay-Gataway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016
 * @license    MIT
 * @version    0.9-2016-10-07-01
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gataway
 * 
 */
 
require_once("callback/JSJApiPay.class.php");
function JSJApiPay_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"金沙江支付宝免签 即时到账API接口 For WHMCS - Code By Tutugreen.com"),
     "apiid" => array("FriendlyName" => "合作伙伴ID(APIID)", "Type" => "text", "Size" => "25","Description" => "[必填]到你的API后台查找，没有账户的请在这里注册：http://api.web567.net/", ),
     "apikey" => array("FriendlyName" => "安全检验码(APIKEY)", "Type" => "text", "Size" => "50", "Description" => "[必填]同上",),
     "fee_acc" => array("FriendlyName" => "记账手续费[仅显示]", "Type" => "text", "Size" => "50", "Description" => "[必填,不填会报错]默认0，如填写0.01，即是1%手续费，用于WHMCS记账时后台显示和统计，不影响实际支付价格。",),
     "debug" => array("FriendlyName" => "调试模式", "Type" => "yesno", "Description" => "调试模式,详细LOG请见[WHMCS]/download/JSJApiPay_log.php，使用文件管理或FTP等查看。", ),
    );
	return $configarray;
}
function JSJApiPay_link($params) {
    if (!isset($params['apiid'])) { 
    echo '$apiid(合作伙伴ID) is not set at all,required.';
    exit;
    }
    if (!isset($params['apikey'])) { 
    echo '$apikey(安全检验码) is not set at all,required.';
    exit;
    }
    if (!isset($params['fee_acc'])) { 
    echo '$fee_acc(记账手续费) is not set at all,required.';
    exit;
    }

    /********************************************************************************************************
    POST页面，发送参数传递至 http://api.web567.net/plugin.php?id=add:alipay
    传递需求参数说明：
    参数	含义	是否必须	
    $_POST['addnum']	订单编号：见下面的特别参数说明	可选	特别参数
    $_POST['total']	交易金额	必须	必须为数字
    $_POST['showurl']	回调地址（用于接收支付状态）	必须	必须为网址
    $_POST['uid']	该订单对应会员编号ID：小于8位数	可选	必须为数字  PS：本接口中作为订单号。实际中相比用户ID更易于定位订单。
    $_POST['apiid']	您的apiid	必须	我站申请
    $_POST['apikey']	您的apikey 注：需要md5加密： md5(您的apikey)	必须	我站申请
    
    ********************************************************************************************************/
	$JSJApiPay_config['input_charset']  = 'utf-8';
	$JSJApiPay_config['apiid']        = trim($params['apiid']);
	$JSJApiPay_config['apikey']       = trim($params['apikey']);
	$JSJApiPay_config['fee_acc']        = trim($params['fee_acc']);
	$debug                             = trim($params["debug"]);

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
	$img=$systemurl."/modules/gateways/callback/img/ali_02.png";

	$JSJApiPay_config['return_url'] = "http://修改为你的地址！！！！！！！/modules/gateways/callback/JSJApiPay_callback.php?act=return";
	$JSJApiPay_config['api_url'] = "http://api.web567.net/plugin.php?id=add:alipay";

    	/*生成addnum参数:
        我们允许自定义订单传递过来，变量为 $_POST['addnum']  组合方式为 alip + 您的apiid + 自定义参数
        其中，自定义参数必须为 数字、字母、或数字字母组合，不能超过18位。组合成功如：alip12345cd3d333233efeef690
        如果没有传递过来订单号，则系统自动生成订单号。除了商品类，一般的充值类不需要自定义订单号。
		PS！请不要随意修改！后方回调也会验证。
		*/

        $JSJApiPay_config['addnum'] = "alip".$JSJApiPay_config['apiid']."WHMCSInvoces".$invoiceid;

    	//基本参数
    	$parameter = array(
    	"_input_charset"=> trim(strtolower($JSJApiPay_config['input_charset'])),
    	"addnum"        => trim($JSJApiPay_config['addnum']),
    	"amount"        => trim($amount),
    	"return_url"	=> trim($JSJApiPay_config['return_url']),
    	"invoiceid"		=> trim($invoiceid),
    	"apiid"		    => $JSJApiPay_config['apiid'],
    	"apikey"		=> strtolower(md5($JSJApiPay_config['apikey'])),
    	"api_url"		=> trim($JSJApiPay_config['api_url']),
    	);

    	$html_code="
    		<form name='JSJApiPay_form1' action='".$parameter['api_url']."' method='POST'>
    			<input type='hidden' name='addnum' value='".$parameter['addnum']."'>
    			<input type='hidden' name='total' value='".$parameter['amount']."'>
    			<input type='hidden' name='showurl' value='".$parameter['return_url']."'>
    			<input type='hidden' name='uid' value='".$parameter['invoiceid']."'>
    			<input type='hidden' name='apiid' value='".$parameter['apiid']."'>
    			<input type='hidden' name='apikey' value='".$parameter['apikey']."'>
    		</form>
            <a href='#' onclick=\"document.forms['JSJApiPay_form1'].submit();\"><img src='$img' alt='点击使用支付宝支付'> </a>
    	";
    	/**备用：<a href='#' onclick=\"document.forms['JSJApiPay_form1'].submit();\"><img src='$img' alt='点击使用支付宝支付'> </a>**/

	if ($debug) {
		$msg="[JSJApiPay]订单: $invoiceid 生成支付表单 $html_code";
		JSJApiPay_logResult($msg);
	}
	return $html_code;
}
?>
