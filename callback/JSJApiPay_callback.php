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
 
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once("./JSJApiPay.class.php");
$gatewaymodule = "JSJApiPay";
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
//Var Check
    if (!isset($GATEWAY['apiid'])) { 
    echo '$apiid(合作伙伴ID) 为必填项目，请在后台-系统设置-付款-支付接口，Manage Existing Gateways 选项卡中设置。';
    exit;
    }
    if (!isset($GATEWAY['apikey'])) { 
    echo '$apikey(安全检验码) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
    exit;
    }
    if (!isset($GATEWAY['fee_acc'])) { 
    echo '$fee_acc(记账手续费) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
    exit;
    }

//Start
    $JSJApiPay_config['apikey'] = trim($GATEWAY['apikey']);
    $JSJApiPay_config['fee_acc'] = trim($GATEWAY['fee_acc']);

if($_GET['act']=='return' or $_GET['act']=='bd'){
    //回调地址
			/********************************************
				这里会传过来几个参数 分别为：
				$_POST['addnum'] 订单编号
				$_POST['total']  支付金额
				$_POST['uid']    支付会员ID
				$_POST['apikey'] 您的(apikey+订单号)小写md5加密，下文称之为回调key
			********************************************/

    //参数获取
    //自动判断POST/GET
    $incoming_apikey = $_POST['apikey'] ? $_POST['apikey'] : $_GET['apikey'];//$apikey 为接收到的回调key,用于认证
    $incoming_addnum = $_POST['addnum'] ? $_POST['addnum'] : $_GET['addnum'];//$addnum 为接收到的订单编号
    $incoming_uid = $_POST['uid'] ? $_POST['uid'] : $_GET['uid'];//$uid 为接收到的支付订单的网站会员
    $incoming_total = $_POST['total'] ? $_POST['total'] : $_GET['total'];//$total 为接收到的支付金额
    //参数转化
    $addnum     = strtolower($incoming_addnum);    //订单信息
    $amount     = $incoming_total;     //支付金额
    $invoiceid  = $incoming_uid;       //支付会员(代替订单号)ID
    $apikey     = strtolower($incoming_apikey);     //传入的回调Key
    $transid    = "JSJApiPay_".$addnum;        //订单流水传递
    
    //手续费计算
    $fee        = $amount*$JSJApiPay_config['fee_acc'];

	if($apikey!=md5($JSJApiPay_config['apikey'].$incoming_addnum.$invoiceid.$amount)){     //先验证下回调key,不正确让他跳转到首页去
	    logTransaction($GATEWAY["name"],$_POST,"Unsuccessfull-APIKEY-DENY");
		header('location:/');
	    exit;
	}else{
	    //正确的路径，合法的参数，的确支付过的会员
		/********************************************
			这里根据业务逻辑编写相应的程序代码。
			1、（本条由WHMCS负责处理）checkCbInvoiceID 会确认交易流水号的唯一性，防止刷单，如存在将exit自动停止。
			2、（本条由WHMCS负责处理）addInvoicePayment 会校验交易基本信息，包括金额，完成自动入账，失败将自动exit退出。
		********************************************/
	    if ($debug) JSJApiPay_logResult("[JSJApiPay]订单 $invoiceid 支付成功.");
	    header("location:/whmcs/viewinvoice.php?id=$invoiceid&from=paygateway");
	    $invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
	    checkCbTransID($transid);
	    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
	    logTransaction($GATEWAY["name"],$_POST,"Successful-A");
		echo "支付成功";
	}
}

?>