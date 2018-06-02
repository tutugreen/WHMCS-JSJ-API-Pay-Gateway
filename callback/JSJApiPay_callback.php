<?php
/**
 * @WHMCS-JSJ-API-Pay-Gateway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016~2018
 * @license    MIT
 * @version    0.18-2018-06-02-01
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway
 * 
 */

# Required File Includes
require_once("../../../init.php");
require_once("../../../includes/functions.php");
require_once("../../../includes/gatewayfunctions.php");
require_once("../../../includes/invoicefunctions.php");
require_once("../JSJApiPay/JSJApiPay.class.php");

#Load
use Illuminate\Database\Capsule\Manager as Capsule;

$gatewaymodule = "JSJApiPay";

if ($_POST['payment_type'] or $_GET['payment_type']){
    $incoming_payment_type = $_POST['payment_type'] ? $_POST['payment_type'] : $_GET['payment_type'];
    if ($incoming_payment_type == 'alipay_web'){
    	$gatewaymodule = "JSJApiPay_Alipay_Web";
    } elseif ($incoming_payment_type == 'alipay_wap'){
    	$gatewaymodule = "JSJApiPay_Alipay_Wap";
    } elseif ($incoming_payment_type == 'alipay_qrcode'){
    	$gatewaymodule = "JSJApiPay_Alipay_QRCode";
    } elseif ($incoming_payment_type == 'wechat_pay_qrcode'){
    	$gatewaymodule = "JSJApiPay_WeChat_Pay_QRCode";
    } elseif ($incoming_payment_type == 'qq_pay_qrcode'){
    	$gatewaymodule = "JSJApiPay_QQ_Pay_QRCode";
    } else {
    	if ($debug) {
    		$msg="[JSJApiPay]收到未知回调，payment_type 变量缺失或错误";
    		JSJApiPay_logResult($msg);
    	}
    	$api_pay_failed = "true";
    	echo "非法访问";
    }
}else{
	if ($debug) {
		$msg="[JSJApiPay]收到未知回调，payment_type 变量缺失或错误";
		JSJApiPay_logResult($msg);
	}
	$api_pay_failed = "true";
	echo "非法访问";
}

if ($api_pay_failed<>"true"){
	$gateway = getGatewayVariables($gatewaymodule);
	if (!$gateway["type"]) die("Module Not Activated，您所请求的回调接口未启用！"); # Checks gateway module is active before accepting callback
	//Var Check
		if (!isset($gateway['apiid'])) { 
		echo '$apiid(合作伙伴ID) 为必填项目，请在后台-系统设置-付款-支付接口，Manage Existing Gateways 选项卡中设置。';
		exit;
		}
		if (!isset($gateway['apikey'])) { 
		echo '$apikey(安全检验码) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
		exit;
		}
		if (!isset($gateway['fee_acc'])) { 
		echo '$fee_acc(记账手续费) 为必填项目，请在后台-系统设置-付款-支付接口设置，Manage Existing Gateways 选项卡中设置。';
		exit;
		}

	//Start
		$JSJApiPay_config['apiid'] = trim($gateway['apiid']);
		$JSJApiPay_config['apikey'] = trim($gateway['apikey']);
		$JSJApiPay_config['fee_acc'] = trim($GATEWAY['fee_acc']);

	if ($_GET['act']=='return' or $_GET['act']=='bd'){
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
		$incoming_uid = $_POST['uid'] ? $_POST['uid'] : $_GET['uid'];//$uid 现作为订单号使用，上限99999999
		$incoming_total = $_POST['total'] ? $_POST['total'] : $_GET['total'];//$total 为接收到的支付金额

		$incoming_invoiceid = $_POST['invoiceid'] ? $_POST['invoiceid'] : $_GET['invoiceid'];//$invoiceid 作为回调地址内订单号，一般使用GET。

		//参数转化
		$addnum     = strtolower($incoming_addnum);    //订单信息
		$amount     = $incoming_total;     //支付金额
		$invoiceid  = $incoming_uid;       //支付会员(代替订单号)ID
		$apikey     = strtolower($incoming_apikey);     //传入的回调Key
		$transid    = "YM01ApiPay_".$addnum;        //订单流水传递，可在此修改前缀，请注意保持唯一性以防被刷单。

		$invoiceid  = $incoming_uid;       //支付会员(代替订单号)ID

		//手续费计算
		$fee        = $amount*$JSJApiPay_config['fee_acc'];

		//验证回调key

		//官方支付宝WEB接口、支付宝WAP接口与微信、QQ钱包支付接口回调验证和订单组合有所区别

		if ($gatewaymodule == "JSJApiPay_Alipay_Web" or $gatewaymodule == "JSJApiPay_Alipay_Wap" or $gatewaymodule == "JSJApiPay_Alipay_QRCode"){
			//支付宝回调验证部分
			//备用(请注意此参数并未启用) md5("apikey[".$apikey."]addnum[".$addnum."]uid[".$uid."]total[".$total."]");
			if($apikey == md5($JSJApiPay_config['apikey'].$incoming_addnum.$invoiceid.$amount)){
				$apikey_validate_result = "Success";
			} else {
				$apikey_validate_result = "Failed";
			}
		} elseif ($gatewaymodule == "JSJApiPay_WeChat_Pay_QRCode" or $gatewaymodule == "JSJApiPay_QQ_Pay_QRCode"){
			//微信回调验证部分
			if($apikey == md5($JSJApiPay_config['apikey'].$incoming_addnum.$invoiceid.$amount)){
				$apikey_validate_result = "Success";
			} else {
				$apikey_validate_result = "Failed";
			}
		}

		if ($apikey_validate_result!="Success"){
			$api_pay_failed = "true";
			if ($gatewaymodule == "JSJApiPay_Alipay_Wap"){
			    //Wap同步回调比较特殊，不带参数，此处暂不对其订单判断结果，直接转到账单页等待异步更新（如支付成功会自动刷新）。
				logTransaction($gateway["name"],$_GET.$_POST.$_SERVER['HTTP_USER_AGENT'],"Client Reach Invoice Page.");
			    header("location:../../../viewinvoice.php?id=$incoming_invoiceid&from=paygateway&status=waitsuccess");
			    exit;
			} else {
    			//取消异步回调后，统一跳转到账单
				if($_SERVER['HTTP_USER_AGENT'] && $_SERVER['HTTP_USER_AGENT'] !=""){
					logTransaction($gateway["name"],$_GET.$_POST.$_SERVER['HTTP_USER_AGENT'],"Client Reach Invoice Page.");
				}else{
					logTransaction($gateway["name"],$_GET.$_POST,"Unsuccessfull-APIKEY-Validate-Failed");
				}
    			if($_SERVER['HTTP_USER_AGENT']){
    			if($_SERVER['HTTP_USER_AGENT'] && $_SERVER['HTTP_USER_AGENT'] !=""){
					header("location:../../../viewinvoice.php?id=$incoming_invoiceid&from=paygateway&status=waitsuccess");
					exit;
    			}else{
    			    echo "error";
					exit;
    			}
    			}else{
    			    echo "error";
					exit;
    			}
			}
			exit;
		} else {
			//正确的路径，合法的参数，的确支付过的会员
			/********************************************
				这里根据业务逻辑编写相应的程序代码。
				1、（本条由WHMCS处理）checkCbInvoiceID 会确认交易流水号的唯一性，防止刷单，如存在将exit自动停止。
				2、（本条由WHMCS处理）addInvoicePayment 会校验交易基本信息，包括金额，完成自动入账，失败将自动exit退出。
			********************************************/
            # convert the currency where necessary
            $userCurrency = getCurrency(Capsule::table("tblinvoices")->where("id",$invoiceid)->get()[0]->userid);
            $userCurrency_id = $userCurrency["id"];
            $userCurrency_suffix = $userCurrency["suffix"];
            if($gateway['convertto'] && ($userCurrency != $gateway['convertto'])) {
                # the users currency is not the same as the JSJApiPay currency, convert to the users currency
                $amount = convertCurrency($amount,$gateway['convertto'],$userCurrency_id);
                $fee   = convertCurrency($fee,$gateway['convertto'],$userCurrency_id);
            }
			if ($debug) JSJApiPay_logResult("[JSJApiPay]订单 $invoiceid 回调验证成功，如入账成功详细参数可在WHMCS-财务记录-接口日志(网关事务日志)中查看");
			//注意，如果你的WHMCS目录比较特殊或需要修改目的地，请在这里修改回调目的地，改为你的账单页面或其他。
			if($_SERVER['HTTP_USER_AGENT'] && $_SERVER['HTTP_USER_AGENT'] !=""){
			    header("location:../../../viewinvoice.php?id=$incoming_invoiceid&from=paygateway&status=waitsuccess");
			}else{
			    echo "success";
			}
        	$invoiceid = checkCbInvoiceID($invoiceid,$gateway["name"]); # Checks invoice ID is a valid invoice number or ends processing
        	checkCbTransID($transid);
        	addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
        	logTransaction($GATEWAY["name"],$_POST,"Successful-A");
		}
	}
}
?>