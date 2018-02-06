<?php
require("vendor/autoload.php");

use \Adinf\RagnarSDK\RagnarSDK as RagnarSDK;
use \Adinf\RagnarSDK\RagnarConst as RagnarConst;
error_reporting(E_ALL);
ini_set("display_errors", "On");
//这俩必须在init之前
//设置业务日志等级
RagnarSDK::setLogLevel(RagnarConst::LOG_TYPE_INFO);
//初始化ragnar项目 实际生产环境用这个初始化,仅限FPM工作
\Adinf\RagnarSDK\RagnarSDK::init("ragnar_projectname");
// url过滤回调函数
RagnarSDK::setUrlFilterCallback(function ($url, $hashquery) {
		if (trim($url) == "") {
		return "";
		}
		if (stripos($url, 'http') !== 0) {
		$url = "http://" . $url;
		}
		$urlinfo = parse_url($url);
		if(!$urlinfo){
		return $url."#PARSERERROR";
		}
		if (!isset($urlinfo["scheme"])) {
		$urlinfo["scheme"] = "http";
		}
		if (!isset($urlinfo["path"])) {
		$urlinfo["path"] = "/";
		}
		if (!isset($urlinfo["query"])) {
		$urlinfo["query"] = "";
		}
		if ($hashquery) {
			return $urlinfo["scheme"] . "://" . $urlinfo["host"] . $urlinfo["path"] . "?" . $urlinfo["query"];
		} else {
			return $urlinfo["scheme"] . "://" . $urlinfo["host"] . $urlinfo["path"];
		}
});
//命令行测试使用，生产环境不适用
RagnarSDK::devmode();
//设置要索引的日志附加数据，在ES搜索内能看到，不建议加太多
RagnarSDK::setMeta(mt_rand(1,100000), "", array( "extrakey" => "extraval" ));
for($i=0;$i<10;$i++){
	//输出info级别日志
	RagnarSDK::RecordLog(RagnarConst::LOG_TYPE_INFO, __FILE__, __LINE__, "module1_msg", "i wish i can fly!".mt_rand(1,111));
	//输出debug级别日志
	RagnarSDK::RecordLog(RagnarConst::LOG_TYPE_DEBUG, __FILE__, __LINE__, "module2_msg", "i wish i'm rich!".mt_rand(100,2223));
	//自定义性能埋点示范
	$digpooint = RagnarSDK::digLogStart(__FILE__, __LINE__, "ragnar_test");
	usleep(mt_rand(100,1111));
	//性能测试内容
	//自定义性能埋点结束
	RagnarSDK::digLogEnd($digpooint, "performance test");


	//curl must fill
	$digpooint = RagnarSDK::digLogStart(__FILE__, __LINE__, "curl");

	//curl init ....

	$nextrpcidheader = RagnarSDK::getCurlChildCallParam($digpooint);

	usleep(mt_rand(1000,5000));
	$result = json_encode(array("data"=>"aha","code"=>22));

	$ext = array("errorno" => mt_rand(0,123), "error" => "error is xxxx:".mt_rand(0,200));

	RagnarSDK::digLogEnd($digpooint, array(
				"url" => "http://www.test.com/test/url".((int)mt_rand(1,3)), "method" => "post",
				"param" => array("post" => array("do"=>mt_rand(100,10000)), "get" => array()),
				"info" => array("ok"=>"d"),
				"error" => $ext,
				"result" => json_decode($result,true),//must array
				));

	$sql = "select * from test where uid = '".mt_rand(1000,5000)."'";
	//this for record the exception when the error
	RagnarSDK::RecordLog(RagnarConst::LOG_TYPE_EXCEPTION, __FILE__, __LINE__, "mysql", array("fun" => "query", "sql" => $sql, "error" => "error info"));

	//start monitor the performance
	$digPoint = RagnarSDK::digLogStart(__FILE__, __LINE__, "mysql");

	//do some sql execute

	//for the mysql performance dig point end
	//RagnarSDK::digLogEnd($digpooint, array("sql" => $sql, "data" => "sql的参数", "op" => "select\delete\update\...", "fun" => "execute_sql"));
	RagnarSDK::digMysqlEnd($digPoint, $sql, "sql的参数", "select", "execute_sql");
}
