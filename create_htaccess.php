#!/usr/bin/php
<?php
/*
 * Author: 十円玉(2018/06/02)
 * Proxyと日本のIP以外弾く.htaccess作るスクリプト。CRONで動かすこと。
 *
 * $htaccess_file:設定を書き込む.htaccessのパス
 * $iplist_url:国コード別IP帯リストのURL
 * $allow_countries:アクセスを許可する国コード
 * $ignore_proxy_elements:アクセスを拒否するProxy環境変数
 * $tmp_ip_list:許可した国の全IP帯
 * $allow_lists:アクセス許可国のIP帯
 * $allow_ips:上記IP帯を、.htaccessのアクセスルールに直した文字列群
 * $proxy_blocks:Proxyの環境変数をアクセスから除外するためのルール文
 * $fp:ファイルポインタ
 * $write:.htaccessに書き込む内容
*/

/*
 * 初期設定
*/

// .htaccessのパス
$htaccess_file = "/path/to/.htaccess";

// 国コード別IP帯リストのURL
$iplist_url = "http://nami.jp/ipv4bycc/cidr.txt";

// アクセスを許可する国コード
$allow_countries = "JP"; // JP|US|DE ←パイプで分ける

// アクセスを拒否するProxy環境変数
$ignore_proxy_elements = array(
	"FORWARDED",
	"FORWARDED-FOR",
	"X-FORWARDED",
	"X_FORWARDED_FOR",
	"HTTP_X_FORWARDED_FOR",
	"VIA",
	"USERAGENT_VIA",
	"XPROXY_CONNECTION",
	"PROXY_CONNECTION",
	"HTTP_PC_REMOTE_ADDR",
	"HTTP_CLIENT_IP",
);

/*
 * 初期設定はここまで
*/

// 全世界のIP帯を取得
$tmp_ip_list = @file_get_contents($iplist_url);

// 上記国コードのIP帯を抜き出す
preg_match_all("/(?:".$allow_countries.")\t(?:[^\t\n]+)/",$tmp_ip_list,$allow_lists);

// 上記IP帯を.htaccessでのアクセス許可文として整形する
$allow_ips = "";
foreach($allow_lists[2] as $val)
{
	$allow_ips.= "Allow from ".$val."\n";
}

// 上記のProxy環境変数を持っていたらアクセス除外するルール文を作成
$proxy_blocks="# block proxy servers\n";
$i=0;
while($i<count($ignore_proxy_elements))
{
	if ($i == (count($ignore_proxy_elements)-1))
	{
		$proxy_blocks.= "RewriteCond %{".$ignore_proxy_elements[$i]."} !^$\n";
	}
	else
	{
		$proxy_blocks.= "RewriteCond %{".$ignore_proxy_elements[$i]."} !^$ [OR]\n";
	}
	$i+=1;
}

$proxy_blocks.= "RewriteRule ^(.*)$ - [F]\n";

// .htaccessを作成して終了
$fp = @fopen($htaccess_file,"w");
$write = "<IfModule mod_rewrite.c>\n";
$write.= "RewriteEngine On\n";
$write.= "RewriteBase /\n";
$write.= $proxy_blocks;
$write.= "Order deny,Allow\n";
$write.= "Deny from all\n";
$write.= $allow_ips;
$write.= "</IfModule>\n";

$fp = @fwrite($fp,$write);
@fclose($fp);
