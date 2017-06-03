<?php
/**
 * @WHMCS-JSJ-API-Pay-Gateway
 *
 * @For    	   WHMCS 6+
 * @author     tutugreen (yuanming@tutugreen.com)
 * @copyright  Copyright (c) Tutugreen.com 2016~2017
 * @license    MIT
 * @version    0.11-2017-06-03-02
 * @link       https://github.com/tutugreen/WHMCS-JSJ-API-Pay-Gateway
 * 
 */
 
/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 * 本段来自Alipay插件
 */
function JSJApiPay_logResult($word='',$file="../../../downloads/JSJApiPay_log.php") {
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())." <?php die() ?>\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}
?>
