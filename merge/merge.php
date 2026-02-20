<?php
/**
 * M3U 聚合工具 - 语句级换行排版 & 全量审计版
 */
$scriptStartTime = microtime(true); // 记录脚本开始执行的时间
date_default_timezone_set('Asia/Shanghai'); // 强制指定为北京时间
chdir(__DIR__); // [关键] 确保 Crontab 调用时能找到同目录文件
umask(000); // [新增] 确保脚本创建的文件对所有用户都有读写权限
// ================= [ 用户自定义配置区 ] =================
$templateFile = 'demo.m3u';
$outputFile   = 'result.m3u';

$sourceUrls = [
	//ENSHAN
	'https://ghfast.top/https://raw.githubusercontent.com/meishero/testipvt/main/vtpiyidong.m3u',
	//chinatv
	'https://live.264788.xyz/sub/02Rzne28JFpslw3tVEoWU5DYa814gL',
	//nas 咪咕
	'http://192.168.33.93:3007',
	//APTV
	'https://ghfast.top/https://raw.githubusercontent.com/Kimentanm/aptv/master/m3u/iptv.m3u#UA=aliplayer',
	//冰茶TV
	//'https://bc.188766.xyz/?url=https://live.188766.xyz&lunbo=false&mima=bingcha1130',
	//直播电视 https://live.zbds.top/
	'https://live.zbds.top/tv/iptv4.m3u',
//	'https://live.zbds.top/tv/iptv6.m3u',
	//裤佬
	'https://bit.ly/jsnzkpg',
	//itvlist
	'https://bit.ly/itvlist',
	//suxuang-v4
	'https://bit.ly/suxuang-v4',
	//大圣若鱼iptv
	'https://ghfast.top/https://raw.githubusercontent.com/moonkeyhoo/3kids/refs/heads/main/0186Wtm3H56k.m3u',

	
	//海外源
	//catvod https://live.catvod.com/login.php  令牌42fc9e5a5932f32ebb11c0c838b74fbeef8086acb3eed38ca0920a9adc5ff467
	//catvod https://iptv.catvod.com/user_panel.php 网站查看 
	'https://iptv.catvod.com/list.php?token=42fc9e5a5932f32ebb11c0c838b74fbeef8086acb3eed38ca0920a9adc5ff467',
	//jackTV
	'https://php.946985.filegear-sg.me/jackTV.m3u',
	//mursor
	'https://live.ottiptv.cc/iptv.m3u?userid=5870134784&sign=597a41048979fa2f0f9447be88f433f7f32914024567253f55dda782e889117d2de162d8f79b398dbe5cef1daf96dc76758675b06fbdeec3b19a1a4c1fee152a9411ab34621124&auth_token=720628804be3d44523e0b170aab73e30',
	//streamlink.org   需续期 20260315过期
	'https://www.stream-link.org/playlist.m3u?token=92f7d738-585f-4795-9bb4-07fa3e1d1a2e', 	
	//iptv研究所	   需续期 20260322过期
	'https://iptv.mydiver.eu.org/get.php?username=tg_st1h14nc&password=fsmi7r6t4tfd&type=m3u_plus',   
	//益力多 肥羊
	'https://tv.iill.top/m3u/Gather', 
];

$doubleFetchUrls = [
	'https://iptv.catvod.com/list.php?token=42fc9e5a5932f32ebb11c0c838b74fbeef8086acb3eed38ca0920a9adc5ff467',
];

// --- 性能与规则参数 ---
$maxConcurrency = 1;      
// [并行路数] 同时测速的线程数，建议 5-10

$testTimeout = 3;      
// [超时秒数] 超过此时间无响应即认为该线路连接失败

$testRetries = 1;      
// [重试次数] 测速失败后的尝试次数，2 表示共请求 3 次

$defaultUA = 'okHttp/Mod-1.5.0.0'; 
// [默认UA] 当订阅源或标签未指定 User-Agent 时使用的全局默认标识

$maxLinksPerChannel = 2;  
// [最大保留数] 每个频道最终保留的最快线路个数（测速不通的自动删除）

// --- 全局探测配置 (提速核心) ---
define('FF_TIMEOUT', 4000000);      // 5秒超时
define('FF_PROBE_SIZE', 200000);    // 降低到 500KB (默认是 5M)
define('FF_ANALYZE_DUR', 2000000);  // 降低到 1秒 (默认是 5秒)

// ========================================================

// --- [内置别名库] 原 alias.json 内容直接放这里 ---
$aliasJson = <<<'JSON'
{
  "__suffix": ["台","频道","-MCP", "亚洲", "粤语", "国语","版","-高码","-50-FPS","-HEVC","-UHD","-SDR","_10m","_36m","_120m","-HD","-高清","-IPV4","-IPV6","「IPV4」","「IPV6」" ],
  "CCTV1": ["CCTV1HD","CCTV1 HD","CCTV1高清","CCTV1 高清","CCTV1综合","CCTV1 综合","CCTV-1","CCTV-1综合","CCTV-1 综合","CCTV1综合HD","CCTV1 综合HD","CCTV1 综合 HD","CCTV-1HD","CCTV-1 HD","CCTV-1综合HD","CCTV-1 综合HD","CCTV-1 综合 HD","CCTV1综合高清","CCTV1 综合高清","CCTV1 综合 高清","CCTV-1高清","CCTV-1 高清","CCTV-1综合高清","CCTV-1 综合高清","CCTV-1 综合 高清"],
  "CCTV2": ["CCTV2HD","CCTV2 HD","CCTV2高清","CCTV2 高清","CCTV2财经","CCTV2 财经","CCTV-2","CCTV-2财经","CCTV-2 财经","CCTV2财经HD","CCTV2 财经HD","CCTV2 财经 HD","CCTV-2HD","CCTV-2 HD","CCTV-2财经HD","CCTV-2 财经HD","CCTV-2 财经 HD","CCTV2财经高清","CCTV2 财经高清","CCTV2 财经 高清","CCTV-2高清","CCTV-2 高清","CCTV-2财经高清","CCTV-2 财经高清","CCTV-2 财经 高清"],
  "CCTV3": ["CCTV3HD","CCTV3 HD","CCTV3高清","CCTV3 高清","CCTV3综艺","CCTV3 综艺","CCTV-3","CCTV-3综艺","CCTV-3 综艺","CCTV3综艺HD","CCTV3 综艺HD","CCTV3 综艺 HD","CCTV-3HD","CCTV-3 HD","CCTV-3综艺HD","CCTV-3 综艺HD","CCTV-3 综艺 HD","CCTV3综艺高清","CCTV3 综艺高清","CCTV3 综艺 高清","CCTV-3高清","CCTV-3 高清","CCTV-3综艺高清","CCTV-3 综艺高清","CCTV-3 综艺 高清"],
  "CCTV4": ["CCTV4HD","CCTV4 HD","CCTV4高清","CCTV4 高清","CCTV4中文国际","CCTV4 中文国际","CCTV-4","CCTV-4中文国际","CCTV-4 中文国际","CCTV4中文国际HD","CCTV4 中文国际HD","CCTV4 中文国际 HD","CCTV-4HD","CCTV-4 HD","CCTV-4中文国际HD","CCTV-4 中文国际HD","CCTV-4 中文国际 HD","CCTV4中文国际高清","CCTV4 中文国际高清","CCTV4 中文国际 高清","CCTV-4高清","CCTV-4 高清","CCTV-4中文国际高清","CCTV-4 中文国际高清","CCTV-4 中文国际 高清","CCTV4国际","CCTV4 国际"],
  "CCTV5": ["CCTV5HD","CCTV5 HD","CCTV5高清","CCTV5 高清","CCTV5体育","CCTV5 体育","CCTV-5","CCTV-5体育","CCTV-5 体育","CCTV5体育HD","CCTV5 体育HD","CCTV5 体育 HD","CCTV-5HD","CCTV-5 HD","CCTV-5体育HD","CCTV-5 体育HD","CCTV-5 体育 HD","CCTV5体育高清","CCTV5 体育高清","CCTV5 体育 高清","CCTV-5高清","CCTV-5 高清","CCTV-5体育高清","CCTV-5 体育高清","CCTV-5 体育 高清"],
  "CCTV5+": ["CCTV5+HD","CCTV5+ HD","CCTV5+高清","CCTV5+ 高清","CCTV5+体育赛事","CCTV5+ 体育赛事","CCTV-5+","CCTV-5+体育赛事","CCTV-5+ 体育赛事","CCTV5+体育赛事HD","CCTV5+ 体育赛事HD","CCTV5+ 体育赛事 HD","CCTV-5+HD","CCTV-5+ HD","CCTV-5+体育赛事HD","CCTV-5+ 体育赛事HD","CCTV-5+ 体育赛事 HD","CCTV5+体育赛事高清","CCTV5+ 体育赛事高清","CCTV5+ 体育赛事 高清","CCTV-5+高清","CCTV-5+ 高清","CCTV-5+体育赛事高清","CCTV-5+ 体育赛事高清","CCTV-5+ 体育赛事 高清","CCTV5P"],
  "CCTV6": ["CCTV6HD","CCTV6 HD","CCTV6高清","CCTV6 高清","CCTV6电影","CCTV6 电影","CCTV-6","CCTV-6电影","CCTV-6 电影","CCTV6电影HD","CCTV6 电影HD","CCTV6 电影 HD","CCTV-6HD","CCTV-6 HD","CCTV-6电影HD","CCTV-6 电影HD","CCTV-6 电影 HD","CCTV6电影高清","CCTV6 电影高清","CCTV6 电影 高清","CCTV-6高清","CCTV-6 高清","CCTV-6电影高清","CCTV-6 电影高清","CCTV-6 电影 高清"],
  "CCTV7": ["CCTV7HD","CCTV7 HD","CCTV7高清","CCTV7 高清","CCTV7国防军事","CCTV7 国防军事","CCTV-7","CCTV-7国防军事","CCTV-7 国防军事","CCTV7国防军事HD","CCTV7 国防军事HD","CCTV7 国防军事 HD","CCTV-7HD","CCTV-7 HD","CCTV-7国防军事HD","CCTV-7 国防军事HD","CCTV-7 国防军事 HD","CCTV7国防军事高清","CCTV7 国防军事高清","CCTV7 国防军事 高清","CCTV-7高清","CCTV-7 高清","CCTV-7国防军事高清","CCTV-7 国防军事高清","CCTV-7 国防军事 高清"],
  "CCTV8": ["CCTV8HD","CCTV8 HD","CCTV8高清","CCTV8 高清","CCTV8电视剧","CCTV8 电视剧","CCTV-8","CCTV-8电视剧","CCTV-8 电视剧","CCTV8电视剧HD","CCTV8 电视剧HD","CCTV8 电视剧 HD","CCTV-8HD","CCTV-8 HD","CCTV-8电视剧HD","CCTV-8 电视剧HD","CCTV-8 电视剧 HD","CCTV8电视剧高清","CCTV8 电视剧高清","CCTV8 电视剧 高清","CCTV-8高清","CCTV-8 高清","CCTV-8电视剧高清","CCTV-8 电视剧高清","CCTV-8 电视剧 高清"],
  "CCTV9": ["CCTV9HD","CCTV9 HD","CCTV9高清","CCTV9 高清","CCTV9纪录","CCTV9 纪录","CCTV-9","CCTV-9纪录","CCTV-9 纪录","CCTV9纪录HD","CCTV9 纪录HD","CCTV9 纪录 HD","CCTV-9HD","CCTV-9 HD","CCTV-9纪录HD","CCTV-9 纪录HD","CCTV-9 纪录 HD","CCTV9纪录高清","CCTV9 纪录高清","CCTV9 纪录 高清","CCTV-9高清","CCTV-9 高清","CCTV-9纪录高清","CCTV-9 纪录高清","CCTV-9 纪录 高清"],
  "CCTV10": ["CCTV10HD","CCTV10 HD","CCTV10高清","CCTV10 高清","CCTV10科教","CCTV10 科教","CCTV-10","CCTV-10科教","CCTV-10 科教","CCTV10科教HD","CCTV10 科教HD","CCTV10 科教 HD","CCTV-10HD","CCTV-10 HD","CCTV-10科教HD","CCTV-10 科教HD","CCTV-10 科教 HD","CCTV10科教高清","CCTV10 科教高清","CCTV10 科教 高清","CCTV-10高清","CCTV-10 高清","CCTV-10科教高清","CCTV-10 科教高清","CCTV-10 科教 高清"],
  "CCTV11": ["CCTV11HD","CCTV11 HD","CCTV11高清","CCTV11 高清","CCTV11戏曲","CCTV11 戏曲","CCTV-11","CCTV-11戏曲","CCTV-11 戏曲","CCTV11戏曲HD","CCTV11 戏曲HD","CCTV11 戏曲 HD","CCTV-11HD","CCTV-11 HD","CCTV-11戏曲HD","CCTV-11 戏曲HD","CCTV-11 戏曲 HD","CCTV11戏曲高清","CCTV11 戏曲高清","CCTV11 戏曲 高清","CCTV-11高清","CCTV-11 高清","CCTV-11戏曲高清","CCTV-11 戏曲高清","CCTV-11 戏曲 高清"],
  "CCTV12": ["CCTV12HD","CCTV12 HD","CCTV12高清","CCTV12 高清","CCTV12社会与法","CCTV12 社会与法","CCTV-12","CCTV-12社会与法","CCTV-12 社会与法","CCTV12社会与法HD","CCTV12 社会与法HD","CCTV12 社会与法 HD","CCTV-12HD","CCTV-12 HD","CCTV-12社会与法HD","CCTV-12 社会与法HD","CCTV-12 社会与法 HD","CCTV12社会与法高清","CCTV12 社会与法高清","CCTV12 社会与法 高清","CCTV-12高清","CCTV-12 高清","CCTV-12社会与法高清","CCTV-12 社会与法高清","CCTV-12 社会与法 高清"],
  "CCTV13": ["CCTV13HD","CCTV13 HD","CCTV13高清","CCTV13 高清","CCTV13新闻","CCTV13 新闻","CCTV-13","CCTV-13新闻","CCTV-13 新闻","CCTV13新闻HD","CCTV13 新闻HD","CCTV13 新闻 HD","CCTV-13HD","CCTV-13 HD","CCTV-13新闻HD","CCTV-13 新闻HD","CCTV-13 新闻 HD","CCTV13新闻高清","CCTV13 新闻高清","CCTV13 新闻 高清","CCTV-13高清","CCTV-13 高清","CCTV-13新闻高清","CCTV-13 新闻高清","CCTV-13 新闻 高清"],
  "CCTV14": ["CCTV14HD","CCTV14 HD","CCTV14高清","CCTV14 高清","CCTV14少儿","CCTV14 少儿","CCTV-14","CCTV-14少儿","CCTV-14 少儿","CCTV14少儿HD","CCTV14 少儿HD","CCTV14 少儿 HD","CCTV-14HD","CCTV-14 HD","CCTV-14少儿HD","CCTV-14 少儿HD","CCTV-14 少儿 HD","CCTV14少儿高清","CCTV14 少儿高清","CCTV14 少儿 高清","CCTV-14高清","CCTV-14 高清","CCTV-14少儿高清","CCTV-14 少儿高清","CCTV-14 少儿 高清"],
  "CCTV15": ["CCTV15HD","CCTV15 HD","CCTV15高清","CCTV15 高清","CCTV15音乐","CCTV15 音乐","CCTV-15","CCTV-15音乐","CCTV-15 音乐","CCTV15音乐HD","CCTV15 音乐HD","CCTV15 音乐 HD","CCTV-15HD","CCTV-15 HD","CCTV-15音乐HD","CCTV-15 音乐HD","CCTV-15 音乐 HD","CCTV15音乐高清","CCTV15 音乐高清","CCTV15 音乐 高清","CCTV-15高清","CCTV-15 高清","CCTV-15音乐高清","CCTV-15 音乐高清","CCTV-15 音乐 高清"],
  "CCTV164K": ["CCTV16奥林匹克4K","CCTV16 奥林匹克4K","CCTV-164K","CCTV-164K奥林匹克","CCTV-164K 奥林匹克","CCTV16奥林匹克4K","CCTV16 奥林匹克4K","CCTV16 奥林匹克 4K","CCTV-164K","CCTV-16 4K","CCTV-16奥林匹克4K","CCTV-16 奥林匹克4K","CCTV-16 奥林匹克 4K","CCTV16奥林匹克超高清","CCTV16 奥林匹克超高清","CCTV16 奥林匹克 超高清","CCTV-16超高清","CCTV-16 超高清","CCTV-16奥林匹克超高清","CCTV-16 奥林匹克超高清","CCTV-16 奥林匹克 超高清","CCTV-16-4K","CCTV164k"],
  "CCTV16": ["CCTV16HD","CCTV16 HD","CCTV16高清","CCTV16 高清","CCTV16奥林匹克","CCTV16 奥林匹克","CCTV-16","CCTV-16奥林匹克","CCTV-16 奥林匹克","CCTV16奥林匹克HD","CCTV16 奥林匹克HD","CCTV16 奥林匹克 HD","CCTV-16HD","CCTV-16 HD","CCTV-16奥林匹克HD","CCTV-16 奥林匹克HD","CCTV-16 奥林匹克 HD","CCTV16奥林匹克高清","CCTV16 奥林匹克高清","CCTV16 奥林匹克 高清","CCTV-16高清","CCTV-16 高清","CCTV-16奥林匹克高清","CCTV-16 奥林匹克高清","CCTV-16 奥林匹克 高清"],
  "CCTV17": ["CCTV17HD","CCTV17 HD","CCTV17高清","CCTV17 高清","CCTV17农业农村","CCTV17 农业农村","CCTV-17","CCTV-17农业农村","CCTV-17 农业农村","CCTV17农业农村HD","CCTV17 农业农村HD","CCTV17 农业农村 HD","CCTV-17HD","CCTV-17 HD","CCTV-17农业农村HD","CCTV-17 农业农村HD","CCTV-17 农业农村 HD","CCTV17农业农村高清","CCTV17 农业农村高清","CCTV17 农业农村 高清","CCTV-17高清","CCTV-17 高清","CCTV-17农业农村高清","CCTV-17 农业农村高清","CCTV-17 农业农村 高清"],
  "CCTV4欧洲": ["CCTV4欧洲","CCTV4 欧洲","CCTV-4欧洲","CCTV-4 欧洲","CCTV4欧洲HD","CCTV4 欧洲HD","CCTV4 欧洲 HD","CCTV-4欧洲HD","CCTV-4 欧洲HD","CCTV-4 欧洲 HD","CCTV4欧洲高清","CCTV4 欧洲高清","CCTV4 欧洲 高清","CCTV-4欧洲高清","CCTV-4 欧洲高清","CCTV-4 欧洲 高清"],
  "CCTV4美洲": ["CCTV4美洲","CCTV4 美洲","CCTV-4美洲","CCTV-4 美洲","CCTV4美洲HD","CCTV4 美洲HD","CCTV4 美洲 HD","CCTV-4美洲HD","CCTV-4 美洲HD","CCTV-4 美洲 HD","CCTV4美洲高清","CCTV4 美洲高清","CCTV4 美洲 高清","CCTV-4美洲高清","CCTV-4 美洲高清","CCTV-4 美洲 高清"],
  "CGTN西语": ["CGTN西语","CGTN 西语","CGTN-西语","CGTN西班牙语","CGTN-西班牙语","CGTN西语HD","CGTN 西语HD","CGTN 西语 HD","CGTN-西语HD","CGTN-西语 HD","CGTN西班牙语HD","CGTN 西班牙语 HD","CGTN-西班牙语HD","CGTN-西班牙语 HD","CGTN西语高清","CGTN 西语高清","CGTN 西语 高清","CGTN-西语高清","CGTN-西语 高清","CGTN西班牙语高清","CGTN 西班牙语 高清","CGTN-西班牙语高清","CGTN-西班牙语 高清","cgtnsp"],
  "CGTN法语": ["CGTN法语","CGTN 法语","CGTN-法语","CGTN法语HD","CGTN 法语HD","CGTN 法语 HD","CGTN-法语HD","CGTN-法语 HD","CGTN法语高清","CGTN 法语高清","CGTN 法语 高清","CGTN-法语高清","CGTN-法语 高清","cgtnfr"],
  "CGTN俄语": ["CGTN俄语","CGTN 俄语","CGTN-俄语","CGTN俄语HD","CGTN 俄语HD","CGTN 俄语 HD","CGTN-俄语HD","CGTN-俄语 HD","CGTN俄语高清","CGTN 俄语高清","CGTN 俄语 高清","CGTN-俄语高清","CGTN-俄语 高清","cgtnru"],
  "CGTN阿语": ["CGTN阿语","CGTN 阿语","CGTN-阿语","CGTN阿拉伯语","CGTN-阿拉伯语","CGTN阿语HD","CGTN 阿语HD","CGTN 阿语 HD","CGTN-阿语HD","CGTN-阿语 HD","CGTN阿拉伯语HD","CGTN 阿拉伯语 HD","CGTN-阿拉伯语HD","CGTN-阿拉伯语 HD","CGTN阿语高清","CGTN 阿语高清","CGTN 阿语 高清","CGTN-阿语高清","CGTN-阿语 高清","CGTN阿拉伯语高清","CGTN 阿拉伯语 高清","CGTN-阿拉伯语高清","CGTN-阿拉伯语 高清","cgtnar"],
  "CGTN": ["CGTN","CGTN 英语","CGTN-英语","CGTN英语HD","CGTN 英语HD","CGTN 英语 HD","CGTN-英语HD","CGTN-英语 HD","CGTN英语高清","CGTN 英语高清","CGTN 英语 高清","CGTN-英语高清","CGTN-英语 高清","CGTN新闻","CGTN 新闻","CGTN-新闻","CGTN新闻HD","CGTN 新闻HD","CGTN 新闻 HD","CGTN-新闻HD","CGTN-新闻 HD","CGTN新闻高清","CGTN 新闻高清","CGTN 新闻 高清","CGTN-新闻高清","CGTN-新闻 高清","cgtnen"],
  "CCTV4K": ["CCTV4K","CCTV 4 K","CCTV4K超高清","CCTV 4K超高清","CCTV 4K 超高清","CCTV-4K","CCTV-4K超高清","CCTV-4K 超高清"],
  "CCTV8K": ["CCTV8K","CCTV 8 K","CCTV8K超高清","CCTV 8K超高清","CCTV 8K 超高清","CCTV-8K","CCTV-8 K","CCTV-8K超高清","CCTV-8K 超高清"],
  "CGTN纪录": ["CGTN纪录","CGTN 纪录","CGTN-纪录","CGTN纪录HD","CGTN 纪录HD","CGTN 纪录 HD","CGTN-纪录HD","CGTN-纪录 HD","CGTN纪录高清","CGTN 纪录高清","CGTN 纪录 高清","CGTN-纪录高清","CGTN-纪录 高清","cgtndoc","CGTN-记录"],
  "重庆卫视": ["重庆卫视HD","重庆卫视 HD","重庆卫视高清","重庆卫视 高清","四川重庆卫视"],
  "四川卫视": ["四川卫视HD","四川卫视 HD","四川卫视高清","四川卫视 高清"],
  "贵州卫视": ["贵州卫视HD","贵州卫视 HD","贵州卫视高清","贵州卫视 高清"],
  "东方卫视": ["东方卫视HD","东方卫视 HD","东方卫视高清","东方卫视 高清","SiTV东方卫视","上海东方卫视"],
  "湖南卫视": ["湖南卫视HD","湖南卫视 HD","湖南卫视高清","湖南卫视 高清"],
  "广东卫视": ["广东卫视HD","广东卫视 HD","广东卫视高清","广东卫视 高清"],
  "深圳卫视": ["深圳卫视HD","深圳卫视 HD","深圳卫视高清","深圳卫视 高清","广东深圳卫视"],
  "天津卫视": ["天津卫视HD","天津卫视 HD","天津卫视高清","天津卫视 高清"],
  "湖北卫视": ["湖北卫视HD","湖北卫视 HD","湖北卫视高清","湖北卫视 高清"],
  "辽宁卫视": ["辽宁卫视HD","辽宁卫视 HD","辽宁卫视高清","辽宁卫视 高清"],
  "安徽卫视": ["安徽卫视HD","安徽卫视 HD","安徽卫视高清","安徽卫视 高清"],
  "浙江卫视": ["浙江卫视HD","浙江卫视 HD","浙江卫视高清","浙江卫视 高清"],
  "山东卫视": ["山东卫视HD","山东卫视 HD","山东卫视高清","山东卫视 高清"],
  "北京卫视": ["北京卫视HD","北京卫视 HD","北京卫视高清","北京卫视 高清"],
  "江苏卫视": ["江苏卫视HD","江苏卫视 HD","江苏卫视高清","江苏卫视 高清"],
  "黑龙江卫视": ["黑龙江卫视HD","黑龙江卫视 HD","黑龙江卫视高清","黑龙江卫视 高清"],
  "河北卫视": ["河北卫视HD","河北卫视 HD","河北卫视高清","河北卫视 高清"],
  "云南卫视": ["云南卫视HD","云南卫视 HD","云南卫视高清","云南卫视 高清"],
  "江西卫视": ["江西卫视HD","江西卫视 HD","江西卫视高清","江西卫视 高清"],
  "东南卫视": ["东南卫视HD","东南卫视 HD","东南卫视高清","东南卫视 高清","福建东南卫视"],
  "海南卫视": ["海南卫视HD","海南卫视 HD","海南卫视高清","海南卫视 高清","旅游卫视"],
  "吉林卫视": ["吉林卫视HD","吉林卫视 HD","吉林卫视高清","吉林卫视 高清"],
  "甘肃卫视": ["甘肃卫视HD","甘肃卫视 HD","甘肃卫视高清","甘肃卫视 高清"],
  "河南卫视": ["河南卫视HD","河南卫视 HD","河南卫视高清","河南卫视 高清"],
  "内蒙古卫视": ["内蒙古卫视HD","内蒙古卫视 HD","内蒙古卫视高清","内蒙古卫视 高清"],
  "陕西卫视": ["陕西卫视HD","陕西卫视 HD","陕西卫视高清","陕西卫视 高清"],
  "广西卫视": ["广西卫视HD","广西卫视 HD","广西卫视高清","广西卫视 高清"],
  "青海卫视": ["青海卫视HD","青海卫视 HD","青海卫视高清","青海卫视 高清"],
  "新疆卫视": ["新疆卫视HD","新疆卫视 HD","新疆卫视高清","新疆卫视 高清"],
  "西藏卫视": ["西藏卫视HD","西藏卫视 HD","西藏卫视高清","西藏卫视 高清"],
  "厦门卫视": ["厦门卫视HD","厦门卫视 HD","厦门卫视高清","厦门卫视 高清","福建厦门卫视"],
  "宁夏卫视": ["宁夏卫视HD","宁夏卫视 HD","宁夏卫视高清","宁夏卫视 高清"],
  "山西卫视": ["山西卫视HD","山西卫视 HD","山西卫视高清","山西卫视 高清"],
  "兵团卫视": ["兵团卫视HD","兵团卫视 HD","兵团卫视高清","兵团卫视 高清","新疆兵团卫视"],
  "康巴卫视": ["康巴卫视HD","康巴卫视 HD","康巴卫视高清","康巴卫视 高清","四川康巴卫视"],
  "延边卫视": ["延边卫视HD","延边卫视 HD","延边卫视高清","延边卫视 高清","吉林延边卫视"],
  "卡酷少儿": ["北京卡酷少儿"],
  "北京纪实科教": ["纪实科教","纪实科教HD","纪实科教 HD","北京纪实","北京纪实科教HD","北京纪实科教 HD","北京纪实科教高清","北京纪实科教 高清","纪实科教高清","纪实科教 高清","北京纪实科教"],
  "第一剧场": ["第一剧场","CCTV第一剧场","CCTV 第一剧场","CCTV-第一剧场","CCTV-第一剧场HD","CCTV-第一剧场 HD","CCTV第一剧场HD","CCTV 第一剧场HD","CCTV第一剧场 HD","CCTV 第一剧场 HD","CCTV第一剧场高清","CCTV 第一剧场高清","CCTV第一剧场 高清","CCTV 第一剧场 高清"],
  "风云剧场": ["风云剧场","CCTV风云剧场","CCTV 风云剧场","CCTV-风云剧场","CCTV-风云剧场HD","CCTV-风云剧场 HD","CCTV风云剧场HD","CCTV 风云剧场HD","CCTV风云剧场 HD","CCTV 风云剧场 HD","CCTV风云剧场高清","CCTV 风云剧场高清","CCTV风云剧场 高清","CCTV 风云剧场 高清"],
  "怀旧剧场": ["怀旧剧场","CCTV怀旧剧场","CCTV 怀旧剧场","CCTV-怀旧剧场","CCTV-怀旧剧场HD","CCTV-怀旧剧场 HD","CCTV怀旧剧场HD","CCTV 怀旧剧场HD","CCTV怀旧剧场 HD","CCTV 怀旧剧场 HD","CCTV怀旧剧场高清","CCTV 怀旧剧场高清","CCTV怀旧剧场 高清","CCTV 怀旧剧场 高清"],
  "世界地理": ["世界地理","CCTV世界地理","CCTV 世界地理","CCTV-世界地理","CCTV-世界地理HD","CCTV-世界地理 HD","CCTV世界地理HD","CCTV 世界地理HD","CCTV世界地理 HD","CCTV 世界地理 HD","CCTV世界地理高清","CCTV 世界地理高清","CCTV世界地理 高清","CCTV 世界地理 高清"],
  "风云音乐": ["CCTV风云音乐","CCTV 风云音乐","CCTV-风云音乐","CCTV-风云音乐HD","CCTV-风云音乐 HD","CCTV风云音乐HD","CCTV 风云音乐HD","CCTV风云音乐 HD","CCTV 风云音乐 HD","CCTV风云音乐高清","CCTV 风云音乐高清","CCTV风云音乐 高清","CCTV 风云音乐 高清","风云音乐"],
  "兵器科技": ["兵器科技","CCTV兵器科技","CCTV 兵器科技","CCTV-兵器科技","CCTV-兵器科技HD","CCTV-兵器科技 HD","CCTV兵器科技HD","CCTV 兵器科技HD","CCTV兵器科技 HD","CCTV 兵器科技 HD","CCTV兵器科技高清","CCTV 兵器科技高清","CCTV兵器科技 高清","CCTV 兵器科技 高清"],
  "风云足球": ["风云足球","CCTV风云足球","CCTV 风云足球","CCTV-风云足球","CCTV-风云足球HD","CCTV-风云足球 HD","CCTV风云足球HD","CCTV 风云足球HD","CCTV风云足球 HD","CCTV 风云足球 HD","CCTV风云足球高清","CCTV 风云足球高清","CCTV风云足球 高清","CCTV 风云足球 高清"],
  "高尔夫网球": ["高尔夫网球","CCTV高尔夫网球","CCTV 高尔夫网球","CCTV-高尔夫网球","CCTV-高尔夫网球HD","CCTV-高尔夫网球 HD","CCTV高尔夫网球HD","CCTV 高尔夫网球HD","CCTV高尔夫网球 HD","CCTV 高尔夫网球 HD","CCTV高尔夫网球高清","CCTV 高尔夫网球高清","CCTV高尔夫网球 高清","CCTV 高尔夫网球 高清"],
  "女性时尚": ["女性时尚","CCTV女性时尚","CCTV 女性时尚","CCTV-女性时尚","CCTV-女性时尚HD","CCTV-女性时尚 HD","CCTV女性时尚HD","CCTV 女性时尚HD","CCTV女性时尚 HD","CCTV 女性时尚 HD","CCTV女性时尚高清","CCTV 女性时尚高清","CCTV女性时尚 高清","CCTV 女性时尚 高清"],
  "央视文化精品": ["央视文化精品","CCTV央视文化精品","CCTV 央视文化精品","CCTV-央视文化精品","CCTV-央视文化精品HD","CCTV-央视文化精品 HD","CCTV央视文化精品HD","CCTV 央视文化精品HD","CCTV央视文化精品 HD","CCTV 央视文化精品 HD","CCTV央视文化精品高清","CCTV 央视文化精品高清","CCTV央视文化精品 高清","CCTV 央视文化精品 高清"],
  "央视台球": ["央视台球","CCTV央视台球","CCTV 央视台球","CCTV-央视台球","CCTV-央视台球HD","CCTV-央视台球 HD","CCTV央视台球HD","CCTV 央视台球HD","CCTV央视台球 HD","CCTV 央视台球 HD","CCTV央视台球高清","CCTV 央视台球高清","CCTV央视台球 高清","CCTV 央视台球 高清"],
  "求索纪录": ["求索纪录","求索纪录HD","求索纪录 HD","求索纪录高清","求索纪录 高清","华数求索纪录"],
  "求索科学": ["求索科学","求索科学HD","求索科学 HD","求索科学高清","求索科学 高清","华数求索科学"],
  "CETV1": ["CETV1","CETV 1","CETV1HD","CETV1 HD","CETV 1 HD","CETV1高清","CETV 1高清","CETV 1 高清","CETV-1","CETV-1HD","CETV-1 HD","CETV-1高清","CETV-1 高清","中国教育1","中国教育1台","中国教育1频道","中国教育电视台1","中国教育电视台1台","中国教育电视台1频道"],
  "CETV2": ["CETV2","CETV 2","CETV2HD","CETV2 HD","CETV 2 HD","CETV2高清","CETV 2高清","CETV 2 高清","CETV-2","CETV-2HD","CETV-2 HD","CETV-2高清","CETV-2 高清","中国教育2","中国教育2台","中国教育2频道","中国教育电视台2","中国教育电视台2台","中国教育电视台2频道"],
  "CETV3": ["CETV3","CETV 3","CETV3HD","CETV3 HD","CETV 3 HD","CETV3高清","CETV 3高清","CETV 3 高清","CETV-3","CETV-3HD","CETV-3 HD","CETV-3高清","CETV-3 高清","中国教育3","中国教育3台","中国教育3频道","中国教育电视台3","中国教育电视台3台","中国教育电视台3频道"],
  "CETV4": ["CETV4","CETV 4","CETV4HD","CETV4 HD","CETV 4 HD","CETV4高清","CETV 4高清","CETV 4 高清","CETV-4","CETV-4HD","CETV-4 HD","CETV-4高清","CETV-4 高清","中国教育4","中国教育4台","中国教育4频道","中国教育电视台4","中国教育电视台4台","中国教育电视台4频道"],
  "嘉佳卡通": ["嘉佳卡通HD","嘉佳卡通 HD","嘉佳卡通高清","嘉佳卡通 高清","广东嘉佳卡通"],
  "大湾区卫视": ["南方卫视","大湾区卫视HD","大湾区卫视 HD","大湾区卫视高清","大湾区卫视 高清","广东大湾区卫视"],
  "金鹰卡通": ["金鹰卡通HD","金鹰卡通 HD","金鹰卡通高清","金鹰卡通 高清","湖南金鹰卡通"],
  "金鹰纪实": ["金鹰纪实HD","金鹰纪实 HD","金鹰纪实高清","金鹰纪实 高清","湖南金鹰纪实"],
  "五星体育": ["五星体育HD","五星体育 HD","五星体育高清","五星体育 高清","上海五星体育","SiTV五星体育","SiTV 五星体育"],
  "求索生活": ["求索生活HD","求索生活 HD","求索生活高清","求索生活 高清","华数求索生活"],
  "新视觉": ["新视觉HD","新视觉 HD","新视觉高清","新视觉 高清","SiTV新视觉","SiTV 新视觉"],
  "快乐垂钓": ["快乐垂钓HD","快乐垂钓 HD","快乐垂钓高清","快乐垂钓 高清","湖南快乐垂钓"],
  "东方财经": ["东方财经HD","东方财经 HD","东方财经高清","东方财经 高清","SiTV东方财经","SiTV东方财经HD","SiTV东方财经 HD","SiTV 东方财经 HD","SiTV 东方财经","SiTV东方财经高清","SiTV东方财经 高清","SiTV 东方财经 高清","上海东方财经"],
  "动漫秀场": ["动漫秀场HD","动漫秀场 HD","动漫秀场高清","动漫秀场 高清","SiTV动漫秀场","SiTV动漫秀场HD","SiTV动漫秀场 HD","SiTV 动漫秀场 HD","SiTV 动漫秀场","SiTV动漫秀场高清","SiTV动漫秀场 高清","SiTV 动漫秀场 高清","上海动漫秀场"],
  "劲爆体育": ["劲爆体育HD","劲爆体育 HD","劲爆体育高清","劲爆体育 高清","SiTV劲爆体育","SiTV劲爆体育HD","SiTV劲爆体育 HD","SiTV 劲爆体育 HD","SiTV 劲爆体育","SiTV劲爆体育高清","SiTV劲爆体育 高清","SiTV 劲爆体育 高清","上海劲爆体育"],
  "茶频道": ["茶频道HD","茶频道 HD","茶频道高清","茶频道 高清","湖南茶频道","湖南 茶频道"],
  "都市剧场": ["都市剧场HD","都市剧场 HD","都市剧场高清","都市剧场 高清","SiTV都市剧场","SiTV都市剧场HD","SiTV都市剧场 HD","SiTV 都市剧场 HD","SiTV 都市剧场","SiTV都市剧场高清","SiTV都市剧场 高清","SiTV 都市剧场 高清","上海都市剧场"],
  "乐游": ["乐游HD","乐游 HD","乐游高清","乐游 高清","全纪实","全纪实HD","全纪实 HD","全纪实高清","全纪实 高清","SiTV全纪实","SiTV 全纪实","SiTV乐游","SiTV乐游HD","SiTV乐游 HD","SiTV乐游高清","SiTV乐游 高清","SiTV 乐游","SiTV 乐游 HD","SiTV 乐游 高清"],
  "CHC动作电影": ["CHC动作电影HD","CHC动作电影 HD","CHC动作电影高清","CHC动作电影 高清","CHC 动作电影","CHC 动作电影 HD","CHC 动作电影 高清","CHC-动作电影"],
  "CHC家庭影院": ["CHC家庭影院HD","CHC家庭影院 HD","CHC家庭影院高清","CHC动家庭影院 高清","CHC 家庭影院","CHC 家庭影院 HD","CHC 家庭影院 高清","CHC-家庭影院"],
  "CHC影迷电影": ["CHC高清电影HD","CHC高清电影 HD","CHC高清电影高清","CHC高清电影 高清","CHC 高清电影","CHC 高清电影 HD","CHC 高清电影 高清","CHC-高清电影","CHC影迷电影HD","CHC影迷电影 HD","CHC影迷电影高清","CHC影迷电影 高清","CHC 影迷电影","CHC 影迷电影 HD","CHC 影迷电影 高清","CHC-影迷电影"],
  "欢笑剧场": ["欢笑剧场HD","欢笑剧场 HD","欢笑剧场高清","欢笑剧场 高清","SiTV欢笑剧场","SiTV欢笑剧场HD","SiTV欢笑剧场 HD","SiTV欢笑剧场高清","SiTV欢笑剧场 高清","SiTV 欢笑剧场","SiTV 欢笑剧场HD","SiTV 欢笑剧场 HD","SiTV 欢笑剧场高清","SiTV 欢笑剧场 高清","上海欢笑剧场"],
  "求索动物": ["求索动物HD","求索动物 HD","求索动物高清","求索动物 高清","华数求索动物"],
  "金色学堂": ["金色学堂HD","金色学堂 HD","金色学堂高清","金色学堂 高清","SiTV金色学堂","SiTV金色学堂HD","SiTV金色学堂 HD","SiTV 金色学堂 HD","SiTV 金色学堂","SiTV金色学堂高清","SiTV金色学堂 高清","SiTV 金色学堂 高清","上海金色学堂"],
  "魅力足球": ["魅力足球HD","魅力足球 HD","魅力足球高清","魅力足球 高清","SiTV魅力足球","SiTV魅力足球HD","SiTV魅力足球 HD","SiTV 魅力足球 HD","SiTV 魅力足球","SiTV魅力足球高清","SiTV魅力足球 高清","SiTV 魅力足球 高清","上海魅力足球"],
  "法治天地": ["法治天地HD","法治天地 HD","法治天地高清","法治天地 高清","SiTV法治天地","SiTV法治天地HD","SiTV法治天地 HD","SiTV 法治天地 HD","SiTV 法治天地","SiTV法治天地高清","SiTV法治天地 高清","SiTV 法治天地 高清","上海法治天地","上视法治天地"],
  "生活时尚": ["生活时尚HD","生活时尚 HD","生活时尚高清","生活时尚 高清","SiTV生活时尚","SiTV生活时尚HD","SiTV生活时尚 HD","SiTV 生活时尚 HD","SiTV 生活时尚","SiTV生活时尚高清","SiTV生活时尚 高清","SiTV 生活时尚 高清","上海生活时尚"],
  "TVB翡翠": ["翡翠台HD","翡翠台 HD","翡翠台高清","翡翠台 高清","TVB翡翠","TVB翡翠台","TVB 翡翠台","TVB翡翠台HD","TVB翡翠台 HD","TVB翡翠台高清","TVB翡翠台 高清","TVB 翡翠台 HD","TVB 翡翠台 高清"],
  "明珠台": ["明珠台HD","明珠台 HD","明珠台高清","明珠台 高清","TVB明珠","TVB明珠台","TVB 明珠台","TVB明珠台HD","TVB明珠台 HD","TVB明珠台高清","TVB明珠台 高清","TVB 明珠台 HD","TVB 明珠台 高清"],
  "TVB星河": ["无线星河","星河台HD","星河台 HD","星河台高清","星河台 高清","TVB星河","TVB星河台","TVB 星河台","TVB星河台HD","TVB星河台 HD","TVB星河台高清","TVB星河台 高清","TVB 星河台 HD","TVB 星河台 高清"],
  "澳亚卫视": ["澳亚卫视HD","澳亚卫视 HD","澳亚卫视高清","澳亚卫视 高清","澳门澳亚卫视"],
  "凤凰中文": ["凤凰中文HD","凤凰中文高清","凤凰中文台","凤凰中文台HD","凤凰中文台高清","凤凰卫视中文","凤凰卫视中文台"],
  "凤凰资讯": ["凤凰资讯HD","凤凰资讯高清","凤凰资讯台","凤凰资讯台HD","凤凰资讯台高清","凤凰卫视资讯","凤凰卫视资讯台"],
  "凤凰电影": ["凤凰电影HD","凤凰电影高清","凤凰电影台","凤凰电影台HD","凤凰电影台高清","凤凰卫视资讯","凤凰卫视资讯台"],
  "东方影视": ["东方影视HD","东方影视 HD","东方影视高清","东方影视 高清","SiTV东方影视","SiTV东方影视HD","SiTV东方影视 HD","SiTV 东方影视 HD","SiTV 东方影视","SiTV东方影视高清","SiTV东方影视 高清","SiTV 东方影视 高清","上海东方影视"],
  "上海新纪实": ["纪实人文HD","纪实人文 HD","纪实人文高清","纪实人文 高清","SiTV纪实人文","SiTV纪实人文HD","SiTV纪实人文 HD","SiTV 纪实人文 HD","SiTV 纪实人文","SiTV纪实人文高清","SiTV纪实人文 高清","SiTV 纪实人文 高清","上海纪实","上海纪实人文"],
  "上海外语": ["上海外语HD","上海外语 HD","上海外语高清","上海外语 高清","SiTV上海外语","SiTV上海外语HD","SiTV上海外语 HD","SiTV 上海外语 HD","SiTV 上海外语","SiTV上海外语高清","SiTV上海外语 高清","SiTV 上海外语 高清"],
  "第一财经": ["第一财经HD","第一财经 HD","第一财经高清","第一财经 高清","SiTV第一财经","SiTV第一财经HD","SiTV第一财经 HD","SiTV 第一财经 HD","SiTV 第一财经","SiTV第一财经高清","SiTV第一财经 高清","SiTV 第一财经 高清","上海第一财经"],
  "上海新闻综合": ["上海新闻HD","上海新闻 HD","上海新闻高清","上海新闻 高清","SiTV上海新闻","SiTV上海新闻HD","SiTV上海新闻 HD","SiTV 上海新闻 HD","SiTV 上海新闻","SiTV上海新闻高清","SiTV上海新闻 高清","SiTV 上海新闻 高清","上海新闻","上海新闻频道","上海新闻综合HD","上海新闻综合 HD","上海新闻综合高清","上海新闻综合 高清","SiTV上海新闻综合"],
  "优漫卡通": ["优漫卡通HD","优漫卡通 HD","优漫卡通高清","优漫卡通 高清","江苏优漫卡通"],
  "哈哈炫动": ["哈哈炫动HD","哈哈炫动 HD","哈哈炫动高清","哈哈炫动 高清","SiTV哈哈炫动","SiTV哈哈炫动HD","SiTV哈哈炫动 HD","SiTV 哈哈炫动 HD","SiTV 哈哈炫动","SiTV哈哈炫动高清","SiTV哈哈炫动 高清","SiTV 哈哈炫动 高清","上海哈哈炫动"],
  "山东教育": ["山东教育HD","山东教育 HD","山东教育高清","山东教育 高清","山东教育台","山东教育频道","山东教育电视台","山东教育电视台HD","山东教育电视台高清","山东教育卫视","山东教育卫视HD","山东教育卫视 HD","山东教育卫视高清","山东教育卫视 高清"],
  "游戏风云": ["游戏风云HD","游戏风云 HD","游戏风云高清","游戏风云 高清","SiTV游戏风云","SiTV游戏风云HD","SiTV游戏风云 HD","SiTV 游戏风云 HD","SiTV 游戏风云","SiTV游戏风云高清","SiTV游戏风云 高清","SiTV 游戏风云 高清","上海游戏风云"],
  "TVB Plus": ["TVBPlus","TVB Plus HD","TVB Plus 高清"],
  "美亚电影": ["美亚电影HD","美亚电影 HD","美亚电影高清","美亚电影 高清"],
  "无线新闻": ["TVB无线新闻","TVB无线新闻台","TVB 无线新闻","TVB 无线新闻台","无线新闻HD","无线新闻 HD","无线新闻高清","无线新闻 高清","TVB无线新闻HD","TVB无线新闻 HD","TVB 无线新闻 HD","TVB无线新闻台HD","TVB无线新闻台 HD","TVB无线新闻台高清","TVB无线新闻台 高清"],
  "书画": ["书画频道"],
  "农林卫视": ["农林卫视HD","农林卫视 HD","农林卫视高清","农林卫视 高清","陕西农林卫视","陕西农林卫视HD","陕西农林卫视高清"],
  "中华美食": ["中华美食HD","中华美食 HD","中华美食高清","中华美食 高清","青岛中华美食"],
  "上海都市": ["上海都市HD","上海都市 HD","上海都市高清","上海都市 高清","SiTV上海都市","SiTV上海都市HD","SiTV上海都市 HD","SiTV 上海都市 HD","SiTV 上海都市","SiTV上海都市高清","SiTV上海都市 高清","SiTV 上海都市 高清","SiTV都市"],
  "海峡卫视": ["海峡卫视HD","海峡卫视 HD","海峡卫视高清","海峡卫视 高清","福建海峡卫视"],
  "凤凰香港": ["凤凰香港HD","凤凰香港高清","凤凰香港台","凤凰香港台HD","凤凰香港台高清","凤凰卫视香港","凤凰卫视香港台"],
  "七彩戏剧": ["七彩戏剧HD","七彩戏剧 HD","七彩戏剧高清","七彩戏剧 高清","SiTV七彩戏剧","SiTV七彩戏剧HD","SiTV七彩戏剧 HD","SiTV 七彩戏剧 HD","SiTV 七彩戏剧","SiTV七彩戏剧高清","SiTV七彩戏剧 高清","SiTV 七彩戏剧 高清","上海七彩戏剧"],
  "三沙卫视": ["三沙卫视HD","三沙卫视 HD","三沙卫视高清","三沙卫视 高清","海南三沙卫视"],
  "湖南爱晚": ["爱晚","爱晚HD","爱晚 HD","爱晚高清","爱晚 高清"],
  "云南都市": ["云南都市HD","云南都市 HD","云南都市高清","云南都市 高清","云南都市频道","云南都市频道高清","云南都市频道 高清","云南都市频道HD","云南都市频道 HD"],
  "云南娱乐": ["云南娱乐HD","云南娱乐 HD","云南娱乐高清","云南娱乐 高清","云南娱乐频道","云南娱乐频道高清","云南娱乐频道 高清","云南娱乐频道HD","云南娱乐频道 HD"],
  "云南影视": ["云南影视HD","云南影视 HD","云南影视高清","云南影视 高清","云南影视频道","云南影视频道高清","云南影视频道 高清","云南影视频道HD","云南影视频道 HD"],
  "云南康旅": ["云南康旅HD","云南康旅 HD","云南康旅高清","云南康旅 高清","云南康旅频道","云南康旅频道高清","云南康旅频道 高清","云南康旅频道HD","云南康旅频道 HD"],
  "云南少儿": ["云南少儿HD","云南少儿 HD","云南少儿高清","云南少儿 高清","云南少儿频道","云南少儿频道高清","云南少儿频道 高清","云南少儿频道HD","云南少儿频道 HD"],
  "澜湄国际": ["澜湄国际HD","澜湄国际 HD","澜湄国际高清","澜湄国际 高清","澜湄国际频道","澜湄国际频道高清","澜湄国际频道 高清","澜湄国际频道HD","澜湄国际频道 HD","云南澜湄国际","云南澜湄国际HD","云南澜湄国际 HD","云南澜湄国际高清","云南澜湄国际 高清","云南国际","云南国际频道"],
  "黑莓电影": ["黑莓电影HD","黑莓电影 HD","黑莓电影高清","黑莓电影 高清","NewTV黑莓电影","NewTV 黑莓电影","NewTV黑莓电影HD","NewTV黑莓电影 HD","NewTV 黑莓电影 HD","NewTV黑莓电影高清","NewTV黑莓电影 高清","NewTV 黑莓电影 高清"],
  "黑莓动画": ["黑莓动画HD","黑莓动画 HD","黑莓动画高清","黑莓动画 高清","NewTV黑莓动画","NewTV 黑莓动画","NewTV黑莓动画HD","NewTV黑莓动画 HD","NewTV 黑莓动画 HD","NewTV黑莓动画高清","NewTV黑莓动画 高清","NewTV 黑莓动画 高清"],
  "动作电影": ["动作电影HD","动作电影 HD","动作电影高清","动作电影 高清","NewTV动作电影","NewTV 动作电影","NewTV动作电影HD","NewTV动作电影 HD","NewTV动作电影高清","NewTV动作电影 高清","NewTV 动作电影 HD","NewTV 动作电影 高清"],
  "重温经典": ["重温经典HD","重温经典 HD","重温经典高清","重温经典 高清"],
  "潮妈辣婆": ["潮妈辣婆HD","潮妈辣婆 HD","潮妈辣婆高清","潮妈辣婆 高清","NewTV潮妈辣婆","NewTV 潮妈辣婆","NewTV潮妈辣婆HD","NewTV潮妈辣婆 HD","NewTV潮妈辣婆高清","NewTV潮妈辣婆 高清","NewTV 潮妈辣婆 HD","NewTV 潮妈辣婆 高清"],
  "哒啵赛事": ["哒啵赛事HD","哒啵赛事 HD","哒啵赛事高清","哒啵赛事 高清","NewTV哒啵赛事","NewTV 哒啵赛事","NewTV哒啵赛事HD","NewTV哒啵赛事 HD","NewTV哒啵赛事高清","NewTV哒啵赛事 高清","NewTV 哒啵赛事 HD","NewTV 哒啵赛事 高清"],
  "哒啵电竞": ["哒啵电竞HD","哒啵电竞 HD","哒啵电竞高清","哒啵电竞 高清","NewTV哒啵电竞","NewTV 哒啵电竞","NewTV哒啵电竞HD","NewTV哒啵电竞 HD","NewTV哒啵电竞高清","NewTV哒啵电竞 高清","NewTV 哒啵电竞 HD","NewTV 哒啵电竞 高清"],
  "军事评论": ["军事评论HD","军事评论 HD","军事评论高清","军事评论 高清","NewTV军事评论","NewTV 军事评论","NewTV军事评论HD","NewTV军事评论 HD","NewTV军事评论高清","NewTV军事评论 高清","NewTV 军事评论 HD","NewTV 军事评论 高清"],
  "炫舞未来": ["炫舞未来HD","炫舞未来 HD","炫舞未来高清","炫舞未来 高清","NewTV炫舞未来","NewTV 炫舞未来","NewTV炫舞未来HD","NewTV炫舞未来 HD","NewTV炫舞未来高清","NewTV炫舞未来 高清","NewTV 炫舞未来 HD","NewTV 炫舞未来 高清"],
  "古装剧场": ["古装剧场HD","古装剧场 HD","古装剧场高清","古装剧场 高清","NewTV古装剧场","NewTV 古装剧场","NewTV古装剧场HD","NewTV古装剧场 HD","NewTV古装剧场高清","NewTV古装剧场 高清","NewTV 古装剧场 HD","NewTV 古装剧场 高清"],
  "军旅剧场": ["军旅剧场HD","军旅剧场 HD","军旅剧场高清","军旅剧场 高清","NewTV军旅剧场","NewTV 军旅剧场","NewTV军旅剧场HD","NewTV军旅剧场 HD","NewTV军旅剧场高清","NewTV军旅剧场 高清","NewTV 军旅剧场 HD","NewTV 军旅剧场 高清"],
  "家庭剧场": ["家庭剧场HD","家庭剧场 HD","家庭剧场高清","家庭剧场 高清","NewTV家庭剧场","NewTV 家庭剧场","NewTV家庭剧场HD","NewTV家庭剧场 HD","NewTV家庭剧场高清","NewTV家庭剧场 高清","NewTV 家庭剧场 HD","NewTV 家庭剧场 高清"],
  "爱情喜剧": ["爱情喜剧HD","爱情喜剧 HD","爱情喜剧高清","爱情喜剧 高清","NewTV爱情喜剧","NewTV 爱情喜剧","NewTV爱情喜剧HD","NewTV爱情喜剧 HD","NewTV爱情喜剧高清","NewTV爱情喜剧 高清","NewTV 爱情喜剧 HD","NewTV 爱情喜剧 高清"],
  "热播精选": ["热播精选HD","热播精选 HD","热播精选高清","热播精选 高清","NewTV热播精选","NewTV 热播精选","NewTV热播精选HD","NewTV热播精选 HD","NewTV热播精选高清","NewTV热播精选 高清","NewTV 热播精选 HD","NewTV 热播精选 高清"],
  "明星大片": ["明星大片HD","明星大片 HD","明星大片高清","明星大片 高清","NewTV明星大片","NewTV 明星大片","NewTV明星大片HD","NewTV明星大片 HD","NewTV明星大片高清","NewTV明星大片 高清","NewTV 明星大片 HD","NewTV 明星大片 高清"],
  "惊悚悬疑": ["惊辣悬疑HD","惊辣悬疑 HD","惊辣悬疑高清","惊辣悬疑 高清","NewTV惊辣悬疑","NewTV 惊辣悬疑","NewTV惊辣悬疑HD","NewTV惊辣悬疑 HD","NewTV惊辣悬疑高清","NewTV惊辣悬疑 高清","NewTV 惊辣悬疑 HD","NewTV 惊辣悬疑 高清"],
  "金牌综艺": ["金牌综艺HD","金牌综艺 HD","金牌综艺高清","金牌综艺 高清","NewTV金牌综艺","NewTV 金牌综艺","NewTV金牌综艺HD","NewTV金牌综艺 HD","NewTV金牌综艺高清","NewTV金牌综艺 高清","NewTV 金牌综艺 HD","NewTV 金牌综艺 高清"],
  "精品纪录": ["精品纪录HD","精品纪录 HD","精品纪录高清","精品纪录 高清","NewTV精品纪录","NewTV 精品纪录","NewTV精品纪录HD","NewTV精品纪录 HD","NewTV精品纪录高清","NewTV精品纪录 高清","NewTV 精品纪录 HD","NewTV 精品纪录 高清"],
  "精品大剧": ["精品大剧HD","精品大剧 HD","精品大剧高清","精品大剧 高清","NewTV精品大剧","NewTV 精品大剧","NewTV精品大剧HD","NewTV精品大剧 HD","NewTV精品大剧高清","NewTV精品大剧 高清","NewTV 精品大剧 HD","NewTV 精品大剧 高清"],
  "精品体育": ["精品体育HD","精品体育 HD","精品体育高清","精品体育 高清","NewTV精品体育","NewTV 精品体育","NewTV精品体育HD","NewTV精品体育 HD","NewTV精品体育高清","NewTV精品体育 高清","NewTV 精品体育 HD","NewTV 精品体育 高清"],
  "精品萌宠": ["精品萌宠HD","精品萌宠 HD","精品萌宠高清","精品萌宠 高清","NewTV精品萌宠","NewTV 精品萌宠","NewTV精品萌宠HD","NewTV精品萌宠 HD","NewTV精品萌宠高清","NewTV精品萌宠 高清","NewTV 精品萌宠 HD","NewTV 精品萌宠 高清"],
  "中国功夫": ["中国功夫HD","中国功夫 HD","中国功夫高清","中国功夫 高清","NewTV中国功夫","NewTV 中国功夫","NewTV中国功夫HD","NewTV中国功夫 HD","NewTV中国功夫高清","NewTV中国功夫 高清","NewTV 中国功夫 HD","NewTV 中国功夫 高清"],
  "怡伴健康": ["怡伴健康HD","怡伴健康 HD","怡伴健康高清","怡伴健康 高清","NewTV怡伴健康","NewTV 怡伴健康","NewTV怡伴健康HD","NewTV怡伴健康 HD","NewTV怡伴健康高清","NewTV怡伴健康 高清","NewTV 怡伴健康 HD","NewTV 怡伴健康 高清"],
  "超级综艺": ["超级综艺HD","超级综艺 HD","超级综艺高清","超级综艺 高清","NewTV超级综艺","NewTV 超级综艺","NewTV超级综艺HD","NewTV超级综艺 HD","NewTV超级综艺高清","NewTV超级综艺 高清","NewTV 超级综艺 HD","NewTV 超级综艺 高清"],
  "超级电影": ["超级电影HD","超级电影 HD","超级电影高清","超级电影 高清","NewTV超级电影","NewTV 超级电影","NewTV超级电影HD","NewTV超级电影 HD","NewTV超级电影高清","NewTV超级电影 高清","NewTV 超级电影 HD","NewTV 超级电影 高清"],
  "超级电视剧": ["超级电视剧HD","超级电视剧 HD","超级电视剧高清","超级电视剧 高清","NewTV超级电视剧","NewTV 超级电视剧","NewTV超级电视剧HD","NewTV超级电视剧 HD","NewTV超级电视剧高清","NewTV超级电视剧 高清","NewTV 超级电视剧 HD","NewTV 超级电视剧 高清"],
  "农业致富": ["农业致富HD","农业致富 HD","农业致富高清","农业致富 高清","NewTV农业致富","NewTV 农业致富","NewTV农业致富HD","NewTV农业致富 HD","NewTV农业致富高清","NewTV农业致富 高清","NewTV 农业致富 HD","NewTV 农业致富 高清"],
  "东北热剧": ["东北热剧HD","东北热剧 HD","东北热剧高清","东北热剧 高清","NewTV东北热剧","NewTV 东北热剧","NewTV东北热剧HD","NewTV东北热剧 HD","NewTV东北热剧高清","NewTV东北热剧 高清","NewTV 东北热剧 HD","NewTV 东北热剧 高清"],
  "欢乐剧场": ["欢乐剧场HD","欢乐剧场 HD","欢乐剧场高清","欢乐剧场 高清","NewTV欢乐剧场","NewTV 欢乐剧场","NewTV欢乐剧场HD","NewTV欢乐剧场 HD","NewTV欢乐剧场高清","NewTV欢乐剧场 高清","NewTV 欢乐剧场 HD","NewTV 欢乐剧场 高清"],
  "电视指南": ["CCTV电视指南","CCTV 电视指南","CCTV-电视指南","CCTV-电视指南HD","CCTV-电视指南 HD","CCTV电视指南HD","CCTV 电视指南HD","CCTV电视指南 HD","CCTV 电视指南 HD","CCTV电视指南高清","CCTV 电视指南高清","CCTV电视指南 高清","CCTV 电视指南 高清"],
  "台视": ["台视HD","台视 HD"],
  "民视": ["民视HD","民视 HD"],
  "华视": ["华视HD","华视 HD"],
  "中视": ["中视HD","中视 HD"],
  "藏语卫视": ["藏语卫视HD","藏语卫视 HD","藏语卫视高清","藏语卫视 高清","西藏藏语卫视","西藏藏语卫视HD","西藏藏语卫视 HD","西藏藏语卫视高清","西藏藏语卫视 高清"],
  "纬来综合台": ["纬来综合台HD","纬来综合台 HD","纬来综合","纬来综合HD","纬来综合 HD"],
  "纬来戏剧台": ["纬来戏剧台HD","纬来戏剧台 HD","纬来戏剧","纬来戏剧HD","纬来戏剧 HD"],
  "纬来日本台": ["纬来日本台HD","纬来日本台 HD","纬来日本","纬来日本HD","纬来日本 HD"],
  "纬来电影台": ["纬来电影台HD","纬来电影台 HD","纬来电影","纬来电影HD","纬来电影 HD"],
  "纬来体育台": ["纬来体育台HD","纬来体育台 HD","纬来体育","纬来体育HD","纬来体育 HD"],
  "纬来音乐台": ["纬来音乐台HD","纬来音乐台 HD","纬来音乐","纬来音乐HD","纬来音乐 HD"],
  "纬来精彩台": ["纬来精彩台HD","纬来精彩台 HD","纬来精彩","纬来精彩HD","纬来精彩 HD"],
  "天映经典": ["天映经典HD","天映经典 HD","CCM","CCM天映经典","CCM 天映经典","CCM天映经典HD","CCM天映经典 HD","CCM 天映经典 HD"],
  "星空卫视": ["星空卫视HD","星空卫视 HD"],
  "澳视澳门": ["澳视澳门HD","澳视澳门 HD"],
  "超级体育": ["超级体育HD","超级体育 HD","超级体育高清","超级体育 高清","NewTV超级体育","NewTV 超级体育","NewTV超级体育HD","NewTV超级体育 HD","NewTV超级体育高清","NewTV超级体育 高清","NewTV 超级体育 HD","NewTV 超级体育 高清"],
  "魅力潇湘": ["魅力潇湘HD","魅力潇湘 HD","魅力潇湘高清","魅力潇湘 高清","NewTV魅力潇湘","NewTV 魅力潇湘","NewTV魅力潇湘HD","NewTV魅力潇湘 HD","NewTV魅力潇湘高清","NewTV魅力潇湘 高清","NewTV 魅力潇湘 HD","NewTV 魅力潇湘 高清"],
  "蒙语卫视": ["蒙语卫视HD","蒙语卫视 HD","蒙语卫视高清","蒙语卫视 高清","内蒙古蒙语卫视"],
  "安多卫视": ["安多卫视HD","安多卫视 HD","安多卫视高清","安多卫视 高清","青海安多卫视","青海安多卫视HD","青海安多卫视 HD","青海安多卫视高清","青海安多卫视 高清"],
  
  "文物宝库": ["河南文物宝库"],
  "CCTV1港澳": ["中央電視台綜合頻道-港澳版","中央綜合台"],
  "CCTV13港澳": ["中央電視台新聞頻道-港澳版","中央新聞台"],
  
  "TVB华丽翡翠台": ["华丽翡翠台", "华丽翡翠"],
  "VIUTV6": ["VIUTVSIX"],
  "channel 5": ["CH5",新加坡5頻道],
  "channel 8": ["CH8",新加坡8頻道],
  "channel U": ["CHU",新加坡U頻道],
  "HOY72": ["HOY怪谈","HOY72怪谈", "HOY705"],
  "HOY73": ["HOY剧集","HOY73剧集","HOY707"],
  "HOY74": ["HOY生活","HOY74生活","HOY706"],
  "HOY75": ["HOY体育","HOY75体育"],
  "HOY76": ["HOY财经","HOY76财经","HOY76国际财经"],
  "HOY77": ["HOY TV","HOY77 TV"],
  "HOY78": ["HOY资讯","HOY78资讯"],
  "中視經典台": ["中视经典"],
  "TVBS綜藝台": ["TVBS综艺"],
  "TVBS台劇台": ["TVBS台剧"],
  "东森亚洲": ["东森亚洲卫视"],
  
  "RTHK31": ["RTHKTV31"],
  "RTHK32": ["RTHKTV32"],
  "RTHK33": ["RTHKTV33"],
  "RTHK34": ["RTHKTV34"],
  "RTHK35": ["RTHKTV35"],
  "爱奇艺": ["IQIYI"],
  "Astro欢喜台": ["HUAHEEDAI" ，"HUAHEE"],
  "Astro QJ": ["QJ" ，"HUAHEE"],
  "八度空间": ["8TV"],
  
  "功夫台": ["TVB功夫","亚洲武侠","TVB亚洲武侠"],
  "HITS頻道": ["HITS"],
  "經典電影台": ["经典电影"],
  "GINX运动": ["GINX", "GINX sport"],
  "尼克少兒": ["nick jr","尼克兒童"],
  "尼克動畫": ["Nickelodeon"],
  "經典卡通台": ["经典卡通"],
  "精選動漫台": ["精选动漫"],
  "深圳财经生活": ["深圳财经","深圳生活"]
  "东方卫视4K": ["东方卫视4K超","东方卫视超高清"] 
  
}
JSON;

// 将 JSON 转为 PHP 数组
$aliasData = json_decode($aliasJson, true);

// ========================================================
// --- 分辨率探测函数 ---
function getRealResolution($url, $ua = 'okHttp/Mod-1.5.0.0', $allLines = '') {
	// 1. 扩充拦截名单
//    $slowFeatures = [
//        'ofiii',        // 针对你卡住的这个 ofiii 源
//        '4gtv',         // 台湾常用的 4gtv 源，ffprobe 很难测
//    ];
	
	$mpdFeatures = [
        '#KODIPROP', 
        '.mpd', 
//        'license_key', 
//        'encrypted'     // 包含加密字样的
    ];
	
	$mpdFeaturesFilter = true;  
	
	// 1. 【精准拦截】直接判断是否存在 KODI 属性或 MPD 特征
    // 如果包含 #KODIPROP 或 manifest_type=mpd，说明是加密或特殊协议流
	if($mpdFeaturesFilter == true)
	{
		foreach ($mpdFeatures as $feature) {
	        if (stripos($allLines, $feature) !== false || stripos($url, $feature) !== false) {
	            return 0;   //过滤mpd dash
	        }
 	   }
	}
	else
	{
		foreach ($mpdFeatures as $feature) {
	        if (stripos($allLines, $feature) !== false || stripos($url, $feature) !== false) {
	            return 479; // 遇到这类“难搞”的源，直接保活，不测了
	        }
 	   }
	}
	
    // 1. 设置宿主机 ffprobe 路径
    // 如果你在终端输入 ffprobe 就能运行，这里直接写 'ffprobe'
    // 如果需要特定路径，请写绝对路径如 '/usr/bin/ffprobe'
    $ffprobePath = 'ffprobe'; 

    // 组装极速探测命令
    $cmd = "timeout 8s {$ffprobePath} -rw_timeout " . FF_TIMEOUT . " -timeout " . FF_TIMEOUT . " -user_agent " . escapeshellarg($ua) . 
           " -follow 1 -v error -hide_banner " .
           " -probesize " . FF_PROBE_SIZE . 
           " -analyzeduration " . FF_ANALYZE_DUR . 
           " -select_streams v:0 -show_entries stream=width,height -of default=noprint_wrappers=1 " .
           " " . escapeshellarg($url) . " 2>&1";

    
    $startTime = microtime(true);
    $res = shell_exec($cmd);
    $duration = round(microtime(true) - $startTime, 2);

    $rawOutput = trim($res);

    // 3. 解析输出
    // 宿主机可能返回多行（如你测试看到的 height=720 出现两次）
    $width = 0;
    $height = 0;
    $lines = explode("\n", $rawOutput);
    foreach ($lines as $line) {
        $line = trim($line);
        // 解析 width=1920 或 height=1080 格式
        if (preg_match('/^(width|height)=(\d+)$/', $line, $matches)) {
            if ($matches[1] == 'width') {
                $width = (int)$matches[2];
            } elseif ($matches[1] == 'height') {
                $height = (int)$matches[2];
            }
        }
    }

    // 返回宽度和高度的数组
    if ($width > 0 && $height > 0) {
        return [
            'width' => $width,
            'height' => $height,
            'resolution' => $width . 'x' . $height
        ];
    }

    // 4. 容错处理：如果没拿到数字，判断是否为网络解析问题
    if (empty($rawOutput) || stripos($rawOutput, 'resolve') !== false || stripos($rawOutput, 'timed out') !== false) {
        // 针对宿主机网络波动或 DNS 暂时的异常，强制保活
        logMsg( "宿主机解析超时/异常， " . $url . parse_url($url, PHP_URL_HOST), "ERROR", 2);
        return 0; 
    }

    // 5. 记录真正的错误（如 403/404）
    logMsg("探测失败: " . $url. substr($rawOutput, 0, 50), "ERROR", 2);
    return 0;
}

set_time_limit(0);
ini_set('memory_limit', '512M');

/**
 * 日志打印并保存到文件
 */
function logMsg($msg, $type = 'INFO', $indent = 0) 
{
    $time = date('H:i:s');
    $date = date('Ymd');
    $isCli = (php_sapi_name() === 'cli'); // 判断是否是命令行运行

    // 1. 构造写入文件的文本内容 (保持原样)
    $padding = str_repeat("    ", $indent); 
    $logFile = "log_{$date}.log";
    $plainMsg = "[$time] [$type] {$padding}{$msg}" . PHP_EOL;
    file_put_contents($logFile, $plainMsg, FILE_APPEND);
    @chmod($logFile, 0666); 

    // 2. 命令行显示逻辑
    if ($isCli) {
        // 终端专用颜色代码 (ANSI)
        $cliColors = [
            'MATCH'   => "\033[32m",    // 绿色
            'ERROR'   => "\033[31m",    // 红色
            'SUCCESS' => "\033[1;34m",  // 粗体蓝
            'TEST'    => "\033[33m",    // 黄色
            'INFO'    => "\033[0m",     // 默认
            'RESET'   => "\033[0m"
        ];
        $color = $cliColors[$type] ?? $cliColors['INFO'];
        $reset = $cliColors['RESET'];
        
        // 命令行输出：[时间] [类型] 缩进 消息
        echo "[$time] {$color}[$type]{$reset} {$padding}{$msg}" . PHP_EOL;
    } 
    // 3. 网页显示逻辑 (保留你原本的所有样式)
    else {
        $htmlPadding = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $indent);
        $htmlColors = [
            'MATCH'   => '#2e7d32', 
            'ERROR'   => '#c62828', 
            'SUCCESS' => '#1565c0', 
            'TEST'    => '#f57c00', 
            'INFO'    => '#333333'
        ];
        $color = $htmlColors[$type] ?? '#333333';
        
        echo "<div style='font-family:monospace;margin-bottom:2px;font-size:12px;border-left:".($indent*2)."px solid #ccc;padding-left:5px;'>";
        echo "<span style='color:$color'>[$time] [$type] $htmlPadding$msg</span>";
        echo "</div>";
        
        echo str_pad('', 4096); 
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
}

/**
 * 深度清洗函数
 */
// 繁简转换映射表
global $_UTF8_TRA;
$_UTF8_TRA = array (
'sim'=>'万与丑专业丛东丝丢两严丧个丬丰临为丽举么义乌乐乔习乡书买乱争于亏云亘亚产亩亲亵亸亿仅从仑仓仪们价众优伙会伛伞伟传伤伥伦伧伪伫体余佣佥侠侣侥侦侧侨侩侪侬俣俦俨俩俪俭债倾偬偻偾偿傥傧储傩儿兑兖党兰关兴兹养兽冁内冈册写军农冢冯冲决况冻净凄凉凌减凑凛几凤凫凭凯击凼凿刍划刘则刚创删别刬刭刽刿剀剂剐剑剥剧劝办务劢动励劲劳势勋勐勚匀匦匮区医华协单卖卢卤卧卫却卺厂厅历厉压厌厍厕厢厣厦厨厩厮县参叆叇双发变叙叠叶号叹叽吁后吓吕吗吣吨听启吴呒呓呕呖呗员呙呛呜咏咔咙咛咝咤咴咸哌响哑哒哓哔哕哗哙哜哝哟唛唝唠唡唢唣唤唿啧啬啭啮啰啴啸喷喽喾嗫呵嗳嘘嘤嘱噜噼嚣嚯团园囱围囵国图圆圣圹场坂坏块坚坛坜坝坞坟坠垄垅垆垒垦垧垩垫垭垯垱垲垴埘埙埚埝埯堑堕塆墙壮声壳壶壸处备复够头夸夹夺奁奂奋奖奥妆妇妈妩妪妫姗姜娄娅娆娇娈娱娲娴婳婴婵婶媪嫒嫔嫱嬷孙学孪宁宝实宠审宪宫宽宾寝对寻导寿将尔尘尧尴尸尽层屃屉届属屡屦屿岁岂岖岗岘岙岚岛岭岳岽岿峃峄峡峣峤峥峦崂崃崄崭嵘嵚嵛嵝嵴巅巩巯币帅师帏帐帘帜带帧帮帱帻帼幂幞干并广庄庆庐庑库应庙庞废庼廪开异弃张弥弪弯弹强归当录彟彦彻径徕御忆忏忧忾怀态怂怃怄怅怆怜总怼怿恋恳恶恸恹恺恻恼恽悦悫悬悭悯惊惧惨惩惫惬惭惮惯愍愠愤愦愿慑慭憷懑懒懔戆戋戏戗战戬户扎扑扦执扩扪扫扬扰抚抛抟抠抡抢护报担拟拢拣拥拦拧拨择挂挚挛挜挝挞挟挠挡挢挣挤挥挦捞损捡换捣据捻掳掴掷掸掺掼揸揽揿搀搁搂搅携摄摅摆摇摈摊撄撑撵撷撸撺擞攒敌敛数斋斓斗斩断无旧时旷旸昙昼昽显晋晒晓晔晕晖暂暧札术朴机杀杂权条来杨杩杰极构枞枢枣枥枧枨枪枫枭柜柠柽栀栅标栈栉栊栋栌栎栏树栖样栾桊桠桡桢档桤桥桦桧桨桩梦梼梾检棂椁椟椠椤椭楼榄榇榈榉槚槛槟槠横樯樱橥橱橹橼檐檩欢欤欧歼殁殇残殒殓殚殡殴毁毂毕毙毡毵氇气氢氩氲汇汉污汤汹沓沟没沣沤沥沦沧沨沩沪沵泞泪泶泷泸泺泻泼泽泾洁洒洼浃浅浆浇浈浉浊测浍济浏浐浑浒浓浔浕涂涌涛涝涞涟涠涡涢涣涤润涧涨涩淀渊渌渍渎渐渑渔渖渗温游湾湿溃溅溆溇滗滚滞滟滠满滢滤滥滦滨滩滪漤潆潇潋潍潜潴澜濑濒灏灭灯灵灾灿炀炉炖炜炝点炼炽烁烂烃烛烟烦烧烨烩烫烬热焕焖焘煅煳熘爱爷牍牦牵牺犊犟状犷犸犹狈狍狝狞独狭狮狯狰狱狲猃猎猕猡猪猫猬献獭玑玙玚玛玮环现玱玺珉珏珐珑珰珲琎琏琐琼瑶瑷璇璎瓒瓮瓯电画畅畲畴疖疗疟疠疡疬疮疯疱疴痈痉痒痖痨痪痫痴瘅瘆瘗瘘瘪瘫瘾瘿癞癣癫癯皑皱皲盏盐监盖盗盘眍眦眬着睁睐睑瞒瞩矫矶矾矿砀码砖砗砚砜砺砻砾础硁硅硕硖硗硙硚确硷碍碛碜碱碹磙礼祎祢祯祷祸禀禄禅离秃秆种积称秽秾稆税稣稳穑穷窃窍窑窜窝窥窦窭竖竞笃笋笔笕笺笼笾筑筚筛筜筝筹签简箓箦箧箨箩箪箫篑篓篮篱簖籁籴类籼粜粝粤粪粮糁糇紧絷纟纠纡红纣纤纥约级纨纩纪纫纬纭纮纯纰纱纲纳纴纵纶纷纸纹纺纻纼纽纾线绀绁绂练组绅细织终绉绊绋绌绍绎经绐绑绒结绔绕绖绗绘给绚绛络绝绞统绠绡绢绣绤绥绦继绨绩绪绫绬续绮绯绰绱绲绳维绵绶绷绸绹绺绻综绽绾绿缀缁缂缃缄缅缆缇缈缉缊缋缌缍缎缏缐缑缒缓缔缕编缗缘缙缚缛缜缝缞缟缠缡缢缣缤缥缦缧缨缩缪缫缬缭缮缯缰缱缲缳缴缵罂网罗罚罢罴羁羟羡翘翙翚耢耧耸耻聂聋职聍联聩聪肃肠肤肷肾肿胀胁胆胜胧胨胪胫胶脉脍脏脐脑脓脔脚脱脶脸腊腌腘腭腻腼腽腾膑臜舆舣舰舱舻艰艳艹艺节芈芗芜芦苁苇苈苋苌苍苎苏苘苹茎茏茑茔茕茧荆荐荙荚荛荜荞荟荠荡荣荤荥荦荧荨荩荪荫荬荭荮药莅莜莱莲莳莴莶获莸莹莺莼萚萝萤营萦萧萨葱蒇蒉蒋蒌蓝蓟蓠蓣蓥蓦蔷蔹蔺蔼蕲蕴薮藁藓虏虑虚虫虬虮虽虾虿蚀蚁蚂蚕蚝蚬蛊蛎蛏蛮蛰蛱蛲蛳蛴蜕蜗蜡蝇蝈蝉蝎蝼蝾螀螨蟏衅衔补衬衮袄袅袆袜袭袯装裆裈裢裣裤裥褛褴襁襕见观觃规觅视觇览觉觊觋觌觍觎觏觐觑觞触觯詟誉誊讠计订讣认讥讦讧讨让讪讫训议讯记讱讲讳讴讵讶讷许讹论讻讼讽设访诀证诂诃评诅识诇诈诉诊诋诌词诎诏诐译诒诓诔试诖诗诘诙诚诛诜话诞诟诠诡询诣诤该详诧诨诩诪诫诬语诮误诰诱诲诳说诵诶请诸诹诺读诼诽课诿谀谁谂调谄谅谆谇谈谊谋谌谍谎谏谐谑谒谓谔谕谖谗谘谙谚谛谜谝谞谟谠谡谢谣谤谥谦谧谨谩谪谫谬谭谮谯谰谱谲谳谴谵谶谷豮贝贞负贠贡财责贤败账货质贩贪贫贬购贮贯贰贱贲贳贴贵贶贷贸费贺贻贼贽贾贿赀赁赂赃资赅赆赇赈赉赊赋赌赍赎赏赐赑赒赓赔赕赖赗赘赙赚赛赜赝赞赟赠赡赢赣赪赵赶趋趱趸跃跄跖跞践跶跷跸跹跻踊踌踪踬踯蹑蹒蹰蹿躏躜躯车轧轨轩轪轫转轭轮软轰轱轲轳轴轵轶轷轸轹轺轻轼载轾轿辀辁辂较辄辅辆辇辈辉辊辋辌辍辎辏辐辑辒输辔辕辖辗辘辙辚辞辩辫边辽达迁过迈运还这进远违连迟迩迳迹适选逊递逦逻遗遥邓邝邬邮邹邺邻郁郄郏郐郑郓郦郧郸酝酦酱酽酾酿释里鉅鉴銮錾钆钇针钉钊钋钌钍钎钏钐钑钒钓钔钕钖钗钘钙钚钛钝钞钟钠钡钢钣钤钥钦钧钨钩钪钫钬钭钮钯钰钱钲钳钴钵钶钷钸钹钺钻钼钽钾钿铀铁铂铃铄铅铆铈铉铊铋铍铎铏铐铑铒铕铗铘铙铚铛铜铝铞铟铠铡铢铣铤铥铦铧铨铪铫铬铭铮铯铰铱铲铳铴铵银铷铸铹铺铻铼铽链铿销锁锂锃锄锅锆锇锈锉锊锋锌锍锎锏锐锑锒锓锔锕锖锗错锚锜锞锟锠锡锢锣锤锥锦锨锩锫锬锭键锯锰锱锲锳锴锵锶锷锸锹锺锻锼锽锾锿镀镁镂镃镆镇镈镉镊镌镍镎镏镐镑镒镕镖镗镙镚镛镜镝镞镟镠镡镢镣镤镥镦镧镨镩镪镫镬镭镮镯镰镱镲镳镴镶长门闩闪闫闬闭问闯闰闱闲闳间闵闶闷闸闹闺闻闼闽闾闿阀阁阂阃阄阅阆阇阈阉阊阋阌阍阎阏阐阑阒阓阔阕阖阗阘阙阚阛队阳阴阵阶际陆陇陈陉陕陧陨险随隐隶隽难雏雠雳雾霁霉霭靓静靥鞑鞒鞯鞴韦韧韨韩韪韫韬韵页顶顷顸项顺须顼顽顾顿颀颁颂颃预颅领颇颈颉颊颋颌颍颎颏颐频颒颓颔颕颖颗题颙颚颛颜额颞颟颠颡颢颣颤颥颦颧风飏飐飑飒飓飔飕飖飗飘飙飚飞飨餍饤饥饦饧饨饩饪饫饬饭饮饯饰饱饲饳饴饵饶饷饸饹饺饻饼饽饾饿馀馁馂馃馄馅馆馇馈馉馊馋馌馍馎馏馐馑馒馓馔馕马驭驮驯驰驱驲驳驴驵驶驷驸驹驺驻驼驽驾驿骀骁骂骃骄骅骆骇骈骉骊骋验骍骎骏骐骑骒骓骔骕骖骗骘骙骚骛骜骝骞骟骠骡骢骣骤骥骦骧髅髋髌鬓魇魉鱼鱽鱾鱿鲀鲁鲂鲄鲅鲆鲇鲈鲉鲊鲋鲌鲍鲎鲏鲐鲑鲒鲓鲔鲕鲖鲗鲘鲙鲚鲛鲜鲝鲞鲟鲠鲡鲢鲣鲤鲥鲦鲧鲨鲩鲪鲫鲬鲭鲮鲯鲰鲱鲲鲳鲴鲵鲶鲷鲸鲹鲺鲻鲼鲽鲾鲿鳀鳁鳂鳃鳄鳅鳆鳇鳈鳉鳊鳋鳌鳍鳎鳏鳐鳑鳒鳓鳔鳕鳖鳗鳘鳙鳛鳜鳝鳞鳟鳠鳡鳢鳣鸟鸠鸡鸢鸣鸤鸥鸦鸧鸨鸩鸪鸫鸬鸭鸮鸯鸰鸱鸲鸳鸴鸵鸶鸷鸸鸹鸺鸻鸼鸽鸾鸿鹀鹁鹂鹃鹄鹅鹆鹇鹈鹉鹊鹋鹌鹍鹎鹏鹐鹑鹒鹓鹔鹕鹖鹗鹘鹚鹛鹜鹝鹞鹟鹠鹡鹢鹣鹤鹥鹦鹧鹨鹩鹪鹫鹬鹭鹯鹰鹱鹲鹳鹴鹾麦麸黄黉黡黩黪黾鼋鼌鼍鼗鼹齄齐齑齿龀龁龂龃龄龅龆龇龈龉龊龋龌龙龚龛龟志制咨只里系范松没尝尝闹面准钟别闲干尽脏拼',
'tra'=>'萬與醜專業叢東絲丟兩嚴喪個爿豐臨為麗舉麼義烏樂喬習鄉書買亂爭於虧雲亙亞產畝親褻嚲億僅從侖倉儀們價眾優夥會傴傘偉傳傷倀倫傖偽佇體餘傭僉俠侶僥偵側僑儈儕儂俁儔儼倆儷儉債傾傯僂僨償儻儐儲儺兒兌兗黨蘭關興茲養獸囅內岡冊寫軍農塚馮衝決況凍淨淒涼淩減湊凜幾鳳鳧憑凱擊氹鑿芻劃劉則剛創刪別剗剄劊劌剴劑剮劍剝劇勸辦務勱動勵勁勞勢勳猛勩勻匭匱區醫華協單賣盧鹵臥衛卻巹廠廳曆厲壓厭厙廁廂厴廈廚廄廝縣參靉靆雙發變敘疊葉號歎嘰籲後嚇呂嗎唚噸聽啟吳嘸囈嘔嚦唄員咼嗆嗚詠哢嚨嚀噝吒噅鹹呱響啞噠嘵嗶噦嘩噲嚌噥喲嘜嗊嘮啢嗩唕喚呼嘖嗇囀齧囉嘽嘯噴嘍嚳囁嗬噯噓嚶囑嚕劈囂謔團園囪圍圇國圖圓聖壙場阪壞塊堅壇壢壩塢墳墜壟壟壚壘墾坰堊墊埡墶壋塏堖塒塤堝墊垵塹墮壪牆壯聲殼壺壼處備複夠頭誇夾奪奩奐奮獎奧妝婦媽嫵嫗媯姍薑婁婭嬈嬌孌娛媧嫻嫿嬰嬋嬸媼嬡嬪嬙嬤孫學孿寧寶實寵審憲宮寬賓寢對尋導壽將爾塵堯尷屍盡層屭屜屆屬屢屨嶼歲豈嶇崗峴嶴嵐島嶺嶽崠巋嶨嶧峽嶢嶠崢巒嶗崍嶮嶄嶸嶔崳嶁脊巔鞏巰幣帥師幃帳簾幟帶幀幫幬幘幗冪襆幹並廣莊慶廬廡庫應廟龐廢廎廩開異棄張彌弳彎彈強歸當錄彠彥徹徑徠禦憶懺憂愾懷態慫憮慪悵愴憐總懟懌戀懇惡慟懨愷惻惱惲悅愨懸慳憫驚懼慘懲憊愜慚憚慣湣慍憤憒願懾憖怵懣懶懍戇戔戲戧戰戩戶紮撲扡執擴捫掃揚擾撫拋摶摳掄搶護報擔擬攏揀擁攔擰撥擇掛摯攣掗撾撻挾撓擋撟掙擠揮撏撈損撿換搗據撚擄摑擲撣摻摜摣攬撳攙擱摟攪攜攝攄擺搖擯攤攖撐攆擷擼攛擻攢敵斂數齋斕鬥斬斷無舊時曠暘曇晝曨顯晉曬曉曄暈暉暫曖劄術樸機殺雜權條來楊榪傑極構樅樞棗櫪梘棖槍楓梟櫃檸檉梔柵標棧櫛櫳棟櫨櫟欄樹棲樣欒棬椏橈楨檔榿橋樺檜槳樁夢檮棶檢欞槨櫝槧欏橢樓欖櫬櫚櫸檟檻檳櫧橫檣櫻櫫櫥櫓櫞簷檁歡歟歐殲歿殤殘殞殮殫殯毆毀轂畢斃氈毿氌氣氫氬氳彙漢汙湯洶遝溝沒灃漚瀝淪滄渢溈滬濔濘淚澩瀧瀘濼瀉潑澤涇潔灑窪浹淺漿澆湞溮濁測澮濟瀏滻渾滸濃潯濜塗湧濤澇淶漣潿渦溳渙滌潤澗漲澀澱淵淥漬瀆漸澠漁瀋滲溫遊灣濕潰濺漵漊潷滾滯灩灄滿瀅濾濫灤濱灘澦濫瀠瀟瀲濰潛瀦瀾瀨瀕灝滅燈靈災燦煬爐燉煒熗點煉熾爍爛烴燭煙煩燒燁燴燙燼熱煥燜燾煆糊溜愛爺牘犛牽犧犢強狀獷獁猶狽麅獮獰獨狹獅獪猙獄猻獫獵獼玀豬貓蝟獻獺璣璵瑒瑪瑋環現瑲璽瑉玨琺瓏璫琿璡璉瑣瓊瑤璦璿瓔瓚甕甌電畫暢佘疇癤療瘧癘瘍鬁瘡瘋皰屙癰痙癢瘂癆瘓癇癡癉瘮瘞瘺癟癱癮癭癩癬癲臒皚皺皸盞鹽監蓋盜盤瞘眥矓著睜睞瞼瞞矚矯磯礬礦碭碼磚硨硯碸礬礦碭碼磚硨硯碸礪礱礫礎硜矽碩硤磽磑礄確鹼礙磧磣堿镟滾禮禕禰禎禱禍稟祿禪離禿稈種積稱穢穠穭稅穌穩穡窮竊竅窯竄窩窺竇窶豎競篤筍筆筧箋籠籩築篳篩簹箏籌簽簡籙簀篋籜籮簞簫簣簍籃籬籪籟糴類秈糶糲粵糞糧糝餱緊縶糸糾紆紅紂纖紇約級紈纊紀紉緯紜紘純紕紗綱納紝縱綸紛紙紋紡紵紖紐紓線紺絏紱練組紳細織終縐絆紼絀紹繹經紿綁絨結絝繞絰絎繪給絢絳絡絕絞統綆綃絹繡綌綏絛繼綈績緒綾緓續綺緋綽緔緄繩維綿綬繃綢綯綹綣綜綻綰綠綴緇緙緗緘緬纜緹緲緝縕繢緦綞緞緶線緱縋緩締縷編緡緣縉縛縟縝縫縗縞纏縭縊縑繽縹縵縲纓縮繆繅纈繚繕繒韁繾繰繯繳纘罌網羅罰罷羆羈羥羨翹翽翬耮耬聳恥聶聾職聹聯聵聰肅腸膚膁腎腫脹脅膽勝朧腖臚脛膠脈膾髒臍腦膿臠腳脫腡臉臘醃膕齶膩靦膃騰臏臢輿艤艦艙艫艱豔艸藝節羋薌蕪蘆蓯葦藶莧萇蒼苧蘇檾蘋莖蘢蔦塋煢繭荊薦薘莢蕘蓽蕎薈薺蕩榮葷滎犖熒蕁藎蓀蔭蕒葒葤藥蒞蓧萊蓮蒔萵薟獲蕕瑩鶯蓴蘀蘿螢營縈蕭薩蔥蕆蕢蔣蔞藍薊蘺蕷鎣驀薔蘞藺藹蘄蘊藪槁蘚虜慮虛蟲虯蟣雖蝦蠆蝕蟻螞蠶蠔蜆蠱蠣蟶蠻蟄蛺蟯螄蠐蛻蝸蠟蠅蟈蟬蠍螻蠑螿蟎蠨釁銜補襯袞襖嫋褘襪襲襏裝襠褌褳襝褲襇褸襤繈襴見觀覎規覓視覘覽覺覬覡覿覥覦覯覲覷觴觸觶讋譽謄訁計訂訃認譏訐訌討讓訕訖訓議訊記訒講諱謳詎訝訥許訛論訩訟諷設訪訣證詁訶評詛識詗詐訴診詆謅詞詘詔詖譯詒誆誄試詿詩詰詼誠誅詵話誕詬詮詭詢詣諍該詳詫諢詡譸誡誣語誚誤誥誘誨誑說誦誒請諸諏諾讀諑誹課諉諛誰諗調諂諒諄誶談誼謀諶諜謊諫諧謔謁謂諤諭諼讒諮諳諺諦謎諞諝謨讜謖謝謠謗諡謙謐謹謾謫譾謬譚譖譙讕譜譎讞譴譫讖穀豶貝貞負貟貢財責賢敗賬貨質販貪貧貶購貯貫貳賤賁貰貼貴貺貸貿費賀貽賊贄賈賄貲賃賂贓資賅贐賕賑賚賒賦賭齎贖賞賜贔賙賡賠賧賴賵贅賻賺賽賾贗讚贇贈贍贏贛赬趙趕趨趲躉躍蹌蹠躒踐躂蹺蹕躚躋踴躋躂躊蹤躓躑躡蹣躕躥躪躦軀車軋軌軒軑軔轉軛輪軟轟軲軻轤軸軹軼軤軫轢軺輕軾載輊轎輈輇輅較輒輔輛輦輩輝輥輞輬輟輜輳輻輯轀輸轡轅轄輾轆轍轔辭辯辮邊遼達遷過邁運還這進遠違連遲邇逕跡適選遜遞邐邏遺遙鄧鄺鄔郵鄒鄴鄰鬱郤郟鄶鄭鄆酈鄖鄲醞醱醬釅釃釀釋裏钜鑒鑾鏨釓釔針釘釗釙釕釷釺釧釤鈒釩釣鍆釹鍚釵鈃鈣鈈鈦鈍鈔鍾鈉鋇鋼鈑鈐鑰欽鈞鎢鉤鈧鈁鈥鈄鈕鈀鈺錢鉦鉗鈷缽鈳鉕鈽鈸鉞鑽鉬鉭鉀鈿鈾鐵鉑鈴鑠鉛鉚鈰鉉鉈鉍鈹鐸鉶銬銠鉺銪鋏鋣鐃銍鐺銅鋁銱銦鎧鍘銖銑鋌銩銛鏵銓鉿銚鉻銘錚銫鉸銥鏟銃鐋銨銀銣鑄鐒鋪鋙錸鋱鏈鏗銷鎖鋰鋥鋤鍋鋯鋨鏽銼鋝鋒鋅鋶鐦鐧銳銻鋃鋟鋦錒錆鍺錯錨錡錁錕錩錫錮鑼錘錐錦鍁錈錇錟錠鍵鋸錳錙鍥鍈鍇鏘鍶鍔鍤鍬鍾鍛鎪鍠鍰鎄鍍鎂鏤鎡鏌鎮鎛鎘鑷鐫鎳鎿鎦鎬鎊鎰鎔鏢鏜鏍鏰鏞鏡鏑鏃鏇鏐鐔钁鐐鏷鑥鐓鑭鐠鑹鏹鐙鑊鐳鐶鐲鐮鐿鑔鑣鑞鑲長門閂閃閆閈閉問闖閏闈閑閎間閔閌悶閘鬧閨聞闥閩閭闓閥閣閡閫鬮閱閬闍閾閹閶鬩閿閽閻閼闡闌闃闠闊闋闔闐闒闕闞闤隊陽陰陣階際陸隴陳陘陝隉隕險隨隱隸雋難雛讎靂霧霽黴靄靚靜靨韃鞽韉韝韋韌韍韓韙韞韜韻頁頂頃頇項順須頊頑顧頓頎頒頌頏預顱領頗頸頡頰頲頜潁熲頦頤頻頮頹頷頴穎顆題顒顎顓顏額顳顢顛顙顥纇顫顬顰顴風颺颭颮颯颶颸颼颻飀飄飆飆飛饗饜飣饑飥餳飩餼飪飫飭飯飲餞飾飽飼飿飴餌饒餉餄餎餃餏餅餑餖餓餘餒餕餜餛餡館餷饋餶餿饞饁饃餺餾饈饉饅饊饌饢馬馭馱馴馳驅馹駁驢駔駛駟駙駒騶駐駝駑駕驛駘驍罵駰驕驊駱駭駢驫驪騁驗騂駸駿騏騎騍騅騌驌驂騙騭騤騷騖驁騮騫騸驃騾驄驏驟驥驦驤髏髖髕鬢魘魎魚魛魢魷魨魯魴魺鮁鮃鯰鱸鮋鮓鮒鮊鮑鱟鮍鮐鮭鮚鮳鮪鮞鮦鰂鮜鱠鱭鮫鮮鮺鯗鱘鯁鱺鰱鰹鯉鰣鰷鯀鯊鯇鮶鯽鯒鯖鯪鯕鯫鯡鯤鯧鯝鯢鯰鯛鯨鯵鯴鯔鱝鰈鰏鱨鯷鰮鰃鰓鱷鰍鰒鰉鰁鱂鯿鰠鼇鰭鰨鰥鰩鰟鰜鰳鰾鱈鱉鰻鰵鱅鰼鱖鱔鱗鱒鱯鱤鱧鱣鳥鳩雞鳶鳴鳲鷗鴉鶬鴇鴆鴣鶇鸕鴨鴞鴦鴒鴟鴝鴛鴬鴕鷥鷙鴯鴰鵂鴴鵃鴿鸞鴻鵐鵓鸝鵑鵠鵝鵒鷳鵜鵡鵲鶓鵪鶤鵯鵬鵮鶉鶊鵷鷫鶘鶡鶚鶻鶿鶥鶩鷊鷂鶲鶹鶺鷁鶼鶴鷖鸚鷓鷚鷯鷦鷲鷸鷺鸇鷹鸌鸏鸛鸘鹺麥麩黃黌黶黷黲黽黿鼂鼉鞀鼴齇齊齏齒齔齕齗齟齡齙齠齜齦齬齪齲齷龍龔龕龜誌製谘隻裏係範鬆冇嚐嘗鬨麵準鐘彆閒乾儘臟拚',
);

/**
 * UTF-8繁简转换类
 */
class utf8_chinese {
    private $utf8_gb2312;
    private $utf8_big5;
    
    public function __construct($data) {
        $this->utf8_gb2312 = $data['sim'];
        $this->utf8_big5   = $data['tra'];
    }
    
    /**
     * 简体转繁体
     */
    public function gb2312_big5($str) {
        $str_t = '';
        $len = strlen($str);
        $a = 0;
        while ($a < $len) {
            if (ord($str[$a]) >= 224 && ord($str[$a]) <= 239) {
                $ch = substr($str, $a, 3);
                if (($temp = strpos($this->utf8_gb2312, $ch)) !== false) {
                    $str_t .= substr($this->utf8_big5, $temp, 3);
                    $a += 3;
                    continue;
                }
            }
            $str_t .= $str[$a];
            $a += 1;
        }
        return $str_t;
    }
    
    /**
     * 繁体转简体
     */
    public function big5_gb2312($str) {
        $str_t = '';
        $len = strlen($str);
        $a = 0;
        while ($a < $len) {
            if (ord($str[$a]) >= 224 && ord($str[$a]) <= 239) {
                $ch = substr($str, $a, 3);
                if (($temp = strpos($this->utf8_big5, $ch)) !== false) {
                    $str_t .= substr($this->utf8_gb2312, $temp, 3);
                    $a += 3;
                    continue;
                }
            }
            $str_t .= $str[$a];
            $a += 1;
        }
        return $str_t;
    }
}

/**
 * 深度清洗函数（包含繁简转换）
 */
 function normalize($str) 
{
    global $_UTF8_TRA;
    if (empty($str)) return '';

    // 【新增核心修正】：强制进行 Unicode 标准化 (NFC)
    // 这能把所有“长得一样但字节码不同”的汉字强制对齐
    if (class_exists('Normalizer')) {
        $str = Normalizer::normalize($str, Normalizer::FORM_C);
    }
	
    
    // 1. 繁体转简体
    static $chineseConverter = null;
    if ($chineseConverter === null) {
        $chineseConverter = new utf8_chinese($_UTF8_TRA);
    }
    $str = $chineseConverter->big5_gb2312($str);
    
    // 2. 转换全角字母、数字、符号为半角
    $str = mb_convert_kana($str, 'as', 'UTF-8');
    
    // 3. 特殊全角字符转换
    $fullwidthSymbols = ['　', '！', '＂', '＃', '＄', '％', '＆', '＇', '（', '）', '＊', '＋', '，', '－', '．', '／', '：', '；', '＜', '＝', '＞', '？', '＠', '［', '＼', '］', '＾', '＿', '｀', '｛', '｜', '｝', '～', '｟', '｠', '｡', '｢', '｣', '､', '･'];
    $halfwidthSymbols = [' ', '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/', ':', ';', '<', '=', '>', '?', '@', '[', '\\', ']', '^', '_', '`', '{', '|', '}', '~', '(', ')', '.', '"', '"', ',', '·'];
    $str = str_replace($fullwidthSymbols, $halfwidthSymbols, $str);
    
    // 4. 中文全角符号转半角
    $chineseSymbols = ['，', '。', '！', '？', '；', '：', '「', '」', '『', '』', '【', '】', '《', '》', '（', '）', '［', '］', '｛', '｝', '～', '、'];
    $englishSymbols = [',', '.', '!', '?', ';', ':', '"', '"', '"', '"', '[', ']', '<', '>', '(', ')', '[', ']', '{', '}', '~', ','];
    $str = str_replace($chineseSymbols, $englishSymbols, $str);
    
    // 5. 转换为小写
    $str = mb_strtolower($str, 'UTF-8');
    
    // 6. 移除所有空格和标点符号
    $removeChars = [
        ' ', "\t", "\n", "\r",   // 空白字符
        '-', '_', '.',           // 连字符和下划线
        '·', '•', '‧',           // 特殊点号
        '(', ')', '[', ']', '{', '}',  // 括号
        ':', ';', ',', '!', '?', '@', '#', '$', '%', '^', '&', '*',  // 标点
        '=', '`', '|', '\\', '/', '"', "'", '<', '>', '~'  // 更多标点'+',
    ];
    
    return str_replace($removeChars, '', $str);
}

function isHighRes($str) 
{
    return preg_match('/4k|8k|4K|8K|超高清|uhd/i', (string)$str);
}

function cleanTvgTags($str) 
{
	if($str == "CCTV4K" || $str == "CCTV8K")
		return $str;
    return trim(preg_replace('/\s?(4k|8k|4K|8K)$/i', '', (string)$str));
}

/**
 * 解析 EXTINF 标签
 */
function parseExtInf($line) 
{
    $info = [
        'name' => '', 
        'tags' => [], 
        'raw'  => $line
    ];
    
    if (preg_match('/#EXTINF:.*?,(.*)$/', $line, $m)) 
    {
        $info['name'] = trim($m[1]);
    }
    
    preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $line, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $m) 
    {
        $info['tags'][$m[1]] = $m[2];
    }
    
    return $info;
}

// --- [ 1. 加载源并构建索引 ] ---
$channelPool = [];
$m3uHeaders = []; // 用于存储原始的所有头行

logMsg("正在拉取源并构建原子索引...", "SUCCESS");

foreach ($sourceUrls as $srcIdx => $sUrl) 
{
    $parts = explode('#UA=', $sUrl);
    $url = trim($parts[0]); 
    $sourceDefaultUA = $parts[1] ?? $defaultUA; 
    
	$needDoubleFetch = in_array($url, $doubleFetchUrls);
	
	$retryCount = 0;
    $maxRetries = 2; // 失败后再尝试 2 次
    $content = false;

	// 如果需要两次拉取，先执行第一次（鉴权）
    if ($needDoubleFetch) {
        logMsg("检测到需两次拉取的源 [#$srcIdx]，执行首次鉴权拉取...", "TEST", 1);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $sourceDefaultUA
        ]);
        $firstFetch = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($firstFetch !== false) {
            logMsg("首次鉴权成功，等待2秒后执行第二次拉取...", "INFO", 1);
            sleep(2); // 等待服务器记录IP
        } else {
            logMsg("首次鉴权拉取失败，但继续尝试第二次拉取...", "WARNING", 1);
        }
    }

    while ($retryCount <= $maxRetries && $content === false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,          // 增加到 30 秒
            CURLOPT_CONNECTTIMEOUT => 10,   // 连接超时 10 秒
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // 强制 IPv4
            CURLOPT_ENCODING => '',         // 处理 GZIP
            CURLOPT_USERAGENT => $sourceDefaultUA
        ]);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content !== false && $httpCode == 200 ) break;
        
        $retryCount++;
        if ($retryCount <= $maxRetries) {
            logMsg("源 [#$srcIdx] | 地址: $url 加载失败，正在进行第 $retryCount 次重试...", "TEST");
            sleep(1); // 等待 1 秒再重试
        }
    }

    if (!$content) { 
        logMsg("源 [#$srcIdx] 彻底加载失败 (重试 $maxRetries 次均无效) | 地址: $url", "ERROR"); 
        continue; 
    }

    $lines = explode("\n", str_replace("\r", "", $content));
    
    // --- 核心修正：直接拷贝原始头行 ---
    if (isset($lines[0]) && stripos(trim($lines[0]), '#EXTM3U') === 0) {
        $rawHeader = trim($lines[0]);
        if (!in_array($rawHeader, $m3uHeaders)) {
            $m3uHeaders[] = $rawHeader;
            logMsg("提取头信息: $rawHeader", "INFO", 1);
        }
    }

    $count = 0;
    for ($i = 0; $i < count($lines); $i++) 
    {
        $line = trim($lines[$i]);
        if (stripos($line, '#EXTINF') === 0) 
        {
            $info = parseExtInf($line);
			// --- [ 核心修改：拦截广播频道 ] ---
			$forbiddenWords = ['广播', '廣播', 'audio', 'radio'];
			$isRadio = false;

			// 检查所有可能出现“广播”字样的字段
			$checkStr = $info['name'] . 
						($info['tags']['tvg-name'] ?? '') . 
						($info['tags']['tvg-id'] ?? '') . 
						($info['tags']['group-title'] ?? '');

			foreach ($forbiddenWords as $word) {
				if (stripos($checkStr, $word) !== false) {
					$isRadio = true;
					break;
				}
			}

			if ($isRadio) {
				// 如果是广播，直接跳过，不存入 $channelPool
				continue; 
			}
			// --- [ 拦截结束 ] ---
			
            // --- 核心修正：抓取中间可能存在的 #KODIPROP 属性行 ---
            $props = [];
            $nextIdx = $i + 1;
            
            while (isset($lines[$nextIdx]) && (strpos(trim($lines[$nextIdx]), '#') === 0)) 
	    // 如果是 #KODIPROP 开头的，保存下来
            {
                $subLine = trim($lines[$nextIdx]);
                if (stripos($subLine, '#KODIPROP') === 0) $props[] = $subLine;
                $nextIdx++;
            }
            
            $streamUrl = isset($lines[$nextIdx]) ? trim($lines[$nextIdx]) : '';
            if (!$streamUrl || strpos($streamUrl, 'http') !== 0) continue;

            $entry = [
                'url'     => $streamUrl, 
                'src_idx' => $srcIdx, 
                'ua'      => $info['tags']['http-user-agent'] ?? $sourceDefaultUA,
                'raw'     => $line, 
                'tags'    => $info['tags'], 
                'name'    => $info['name'], 
                'props'   => $props,
				'raw_block'=> implode("\n", $props), // 新增：把所有 #KODIPROP 行拼在一起
                'is_res'  => isHighRes($info['name'] . ($info['tags']['tvg-name']??'') . ($info['tags']['tvg-id']??''))
            ];
            
            $sId    = normalize($info['tags']['tvg-id'] ?? '');
            $sName  = normalize($info['tags']['tvg-name'] ?? '');
            $sTitle = normalize($info['name']);

            if ($sId) $channelPool[$srcIdx]['tvg-id'][$sId][] = $entry;
            if ($sName) $channelPool[$srcIdx]['tvg-name'][$sName][] = $entry;
            if ($sTitle) $channelPool[$srcIdx]['频道名称'][$sTitle][] = $entry;
            
            $count++;
            $i = $nextIdx; // 跳过已处理的属性行和URL行
        }
    }
    logMsg("源 [#$srcIdx] 加载成功 | 有效频道: $count | 默认UA: $sourceDefaultUA | 地址: $url", "SUCCESS");
}

// --- [ 2. 严格原子交叉匹配 ] ---
$suffix    = $aliasData['__suffix'] ?? [];
$tplLines  = explode("\n", str_replace("\r", "", file_get_contents($templateFile)));
$allCandidates = [];

logMsg("启动【严格顺序】交叉匹配逻辑...", "INFO");

foreach ($tplLines as $tLine) 
{
    if (stripos(trim($tLine), '#EXTINF') !== 0) continue;
    
    $tpl = parseExtInf($tLine);
    $targetSource = $tpl['tags']['origin-url'] ?? '';
    $tplIsRes = isHighRes($tpl['name'] . ($tpl['tags']['tvg-name']??'') . ($tpl['tags']['tvg-id']??''));

    // --- 别名获取逻辑：优先 tvg-id，不存在则尝试 频道名称 ---
    $aliasKey   = $tpl['tags']['tvg-id'] ?? $tpl['name'];
    $rawAliases = $aliasData[$aliasKey] ?? ($aliasData[$tpl['name']] ?? []);

    $demoWords = [
        'tvg-id'   => normalize($tpl['tags']['tvg-id'] ?? ''),
        'tvg-name' => normalize($tpl['tags']['tvg-name'] ?? ''),
        '频道名称'   => normalize($tpl['name']),
        '别名'     => array_map('normalize', (array)$rawAliases)
    ];

    $foundForThisTpl = [];
    foreach ($sourceUrls as $sIdx => $sUrl) 
    {
        $cleanSUrl = explode('#UA=', $sUrl)[0];
 	// 关键逻辑：如果设定了 origin-url 且不匹配当前源，跳过
        if ($targetSource && strpos($cleanSUrl, $targetSource) === false) continue;

        $matchInSrc = [];
        $rules = [1=>'tvg-id', 2=>'tvg-name', 3=>'频道名称', 4=>'别名', 5=>'tvg-id', 6=>'tvg-name', 7=>'频道名称', 8=>'别名'];
        $sourceOrder = ['tvg-id', 'tvg-name', '频道名称'];

        for ($lv = 1; $lv <= 8; $lv++) 
        {
            $field = $rules[$lv];
            $words = (array)$demoWords[$field];
            if ($lv > 4) 
            {
                $nw = []; 
                foreach($words as $w) { foreach($suffix as $s) { $nw[] = $w . normalize($s); } }
                $words = $nw;
            }

            foreach (array_unique(array_filter($words)) as $w) 
            {
	    	// 原子级顺序匹配：在这个词下，必须按 ID -> Name -> Title 的顺序找
                foreach ($sourceOrder as $sf) 
                {
                    if (isset($channelPool[$sIdx][$sf][$w])) 
                    {
                        foreach ($channelPool[$sIdx][$sf][$w] as $item) 
                        {
                            if ($tplIsRes !== $item['is_res']) continue; 
                            $item['debug']    = "第 $lv 级命中: Demo[$field] -> 源[$sf] | 词: [$w]";
                            $item['tpl_name'] = $tpl['name']; 
                            $item['tpl_tags'] = $tpl['tags'];
                            $matchInSrc[$item['url']] = $item;
                        }
                        break 2; 
                    }
                }
            }
            if (!empty($matchInSrc)) break; 
        }
        foreach ($matchInSrc as $m) $foundForThisTpl[] = $m;
    }

    if (!empty($foundForThisTpl)) 
    {
        logMsg("匹配成功: [".$tpl['name']."]" . ($targetSource ? " (锁定源: $targetSource)" : ""), "MATCH", 1);
        foreach ($foundForThisTpl as $c) 
        {
            logMsg(">>> 源[#$c[src_idx]] $c[debug] | UA: $c[ua]", "MATCH", 2);
            logMsg(">>> 原始行: $c[raw]", "MATCH", 2);
            $allCandidates[] = $c;
        }
    } 
    else 
    {
        $lockMsg = $targetSource ? " | 锁定源地址: $targetSource" : "";
        logMsg("匹配失败: [".$tpl['name']."]$lockMsg", "ERROR", 1);
    }
}

// --- [ 3. 测速阶段 - 滚动并发优化版 ] ---
logMsg("进入测速阶段 (滚动并发模式，并发数: $maxConcurrency)...", "TEST");

$results = [];
$queue = $allCandidates; // 待处理队列
$activeHandles = [];     // 正在运行的句柄池
$mh = curl_multi_init();

// 1. 初始化：预填满并发池
for ($i = 0; $i < $maxConcurrency && !empty($queue); $i++) {
    $c = array_shift($queue);
    $ch = curl_init($c['url']);
    curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => 1, 
		CURLOPT_RANGE => '0-1', 
		CURLOPT_TIMEOUT_MS => $testTimeout * 1000, 
		CURLOPT_CONNECTTIMEOUT_MS => $testTimeout * 1000 *0.6, 
		CURLOPT_FOLLOWLOCATION => 1, 
		CURLOPT_SSL_VERIFYPEER => 0, 
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
		CURLOPT_USERAGENT => $c['ua']
    ]);
    curl_multi_add_handle($mh, $ch);
    $activeHandles[(int)$ch] = $c;
}

// 2. 滚动处理：跑完一个补一个
do {
    // 执行 curl 任务
    while (($execStatus = curl_multi_exec($mh, $running)) === CURLM_CALL_MULTI_PERFORM);
    if ($execStatus !== CURLM_OK) break;

    // 检查是否有任务完成
    while ($done = curl_multi_info_read($mh)) {
        $ch = $done['handle'];
        $c = $activeHandles[(int)$ch];
        
        // 获取测速结果
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        if ($code >= 200 && $code < 400 && $time > 0) {
			// --- 【核心修改：注入物理分辨率】 ---
            // logMsg("正在物理探测分辨率: [{$c['tpl_name']}][源:#{$c['src_idx']}]", "INFO", 1);
            // $realRes = getRealResolution($c['url']);
			// $testUrl = $c['url'] ?? "未知URL";
			// if ($realRes > 0) 
			// {
				// $c['real_res'] = $realRes; 
				// $c['speed'] = $time;
				// logMsg("测速和分辨率探测成功: [{$c['tpl_name']}][源:#{$c['src_idx']}]| 地址: $testUrl | 分辨率: {$realRes}P | 耗时: {$time}s", "TEST", 1);

				// $results[] = $c;
			// }
			// else
			// {
				// logMsg("探测失败: [{$c['tpl_name']}][源:#{$c['src_idx']}]| 地址: $testUrl 分辨率为0或无法解析，已过滤", "ERROR", 1);
			// }	
			$testUrl = $c['url'] ?? "未知URL";	
			$c['real_res'] = 0; // 初始设为0，留到最后探测
			$c['speed'] = $time;			
			logMsg("测速成功: [{$c['tpl_name']}][源:#{$c['src_idx']}]| 地址: $testUrl | 耗时: {$time}s", "TEST", 1);
			$results[] = $c;
        } else {
            $reason = ($time >= $testTimeout) ? "超时" : "状态码: $code";
			$testUrl = $c['url'] ?? "未知URL";
			logMsg("测速跳过: [{$c['tpl_name']}][源:#{$c['src_idx']}] 原因: $reason | 地址: $testUrl", "ERROR", 1);
        }

        // 移除旧句柄并补充新任务
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        unset($activeHandles[(int)$ch]);

        if (!empty($queue)) {
            $next = array_shift($queue);
            $nch = curl_init($next['url']);
            curl_setopt_array($nch, [
                CURLOPT_RETURNTRANSFER => 1, 
                CURLOPT_RANGE => '0-1', 
                CURLOPT_TIMEOUT_MS => $testTimeout * 1000, 
				CURLOPT_CONNECTTIMEOUT_MS => $testTimeout * 1000 *0.6, 
                CURLOPT_FOLLOWLOCATION => 1, 
                CURLOPT_SSL_VERIFYPEER => 0, 
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT => $next['ua']
            ]);
            curl_multi_add_handle($mh, $nch);
            $activeHandles[(int)$nch] = $next;
        }
    }
    
    // 关键：避免 CPU 空转
    if ($running) curl_multi_select($mh, 0.1);

} while ($running || !empty($queue));

curl_multi_close($mh);

// --- [ 4. 合并保存 & 延迟物理探测版 ] ---
$grouped = [];
foreach ($results as $r) { 
    $grouped[$r['tpl_name']][] = $r; 
}

// 核心修正：直接输出去重后的原始头行
$m3u = $m3uHeaders; 

// 【逻辑修正】：通过遍历 tplLines 确保输出顺序与 demo.m3u 一致
foreach ($tplLines as $tLine) 
{
    if (stripos(trim($tLine), '#EXTINF') !== 0) continue;
    $tpl = parseExtInf($tLine);
    $name = $tpl['name'];

    if (!isset($grouped[$name])) continue; // 如果该频道没有测速通过的线路，跳过
    
    $list = $grouped[$name];

    // 1. 【初步筛选】先按纯测速耗时排序，取前几个作为候选（比最大保留数多取2个，防止探测失败）
    usort($list, function($a, $b) {
        return $a['speed'] <=> $b['speed'];
    });
    $candidates = array_slice($list, 0, $maxLinksPerChannel + 2);

    // 2. 【方案A：延迟物理探测】仅对入选的候选者进行 ffprobe 探测
    $finalForChannel = [];
    foreach ($candidates as $item) {
        logMsg("对最优线路执行物理探测: [{$name}] -> {$item['url']}", "INFO", 1);
        
        // 执行 ffprobe 获取真实高度
		$allLinesContext = $item['raw_block'] ?? '';
        $resResult = getRealResolution($item['url'], $item['ua'], $allLinesContext);
        
        if (is_array($resResult) && isset($resResult['width'], $resResult['height'])) 
		{
            $width = $resResult['width'];
            $height = $resResult['height'];
            $resolution = $resResult['resolution'];
            
            // 过滤低分辨率（例如高度小于400的）
            if($height <= 400) 
			{
                logMsg("探测成功但过滤: 分辨率={$resolution} | 探测前测速耗时={$item['speed']}s", "SUCCESS", 2);
            } 
			else {
                $item['real_width'] = $width;
                $item['real_height'] = $height;
                $item['real_resolution'] = $resolution;
                $item['real_res'] = $height; // 保持向后兼容
                logMsg("探测成功: 分辨率={$resolution} (宽:{$width}, 高:{$height}) | 探测前测速耗时={$item['speed']}s", "SUCCESS", 2);
                $finalForChannel[] = $item;
            }
        } 
		elseif ($resResult === 479) {
            // 特殊过滤，保活
            $item['real_res'] = 479; // 特殊标记
            logMsg("探测返回特殊代码479: 保活线路 | 探测前测速耗时={$item['speed']}s", "SUCCESS", 2);
            $finalForChannel[] = $item;
        } 
		else 
		{
            // 探测失败删除
            logMsg("探测失败或超时，已彻底丢弃", "ERROR", 2);
        }
        
    }

    // 3. 【最终排序】根据探测到的真实分辨率和速度进行二次精准排序
    usort($finalForChannel, function($a, $b) {
        $heightA = $a['real_height'] ?? $a['real_res'] ?? 0;
        $heightB = $b['real_height'] ?? $b['real_res'] ?? 0;
        
        // 分辨率高的排前面（主要按高度排序）
        if ($heightA !== $heightB) {
            return $heightB <=> $heightA; 
        }
        
        // 如果高度相同，按宽度排序
        $widthA = $a['real_width'] ?? 0;
        $widthB = $b['real_width'] ?? 0;
        if ($widthA !== $widthB) {
            return $widthB <=> $widthA;
        }
        
        // 分辨率完全相同时，速度快的排前面
        return $a['speed'] <=> $b['speed'];
    });

    // 4. 【正式写入】取前 $maxLinksPerChannel 个写入最终数组
    $topLinks = array_slice($finalForChannel, 0, $maxLinksPerChannel);
    
    foreach ($topLinks as $m) 
    {
        // 1. 合并标签
        $finalTags = array_merge($m['tags'], $m['tpl_tags']);
        
        // 2. 清洗特定的 tvg 标签
        if (isset($finalTags['tvg-id'])) $finalTags['tvg-id'] = cleanTvgTags($finalTags['tvg-id']);
        if (isset($finalTags['tvg-name'])) $finalTags['tvg-name'] = cleanTvgTags($finalTags['tvg-name']);
        
        // 3. 【核心修改】按照你要求的顺序提取并排列标签
        $order = ['tvg-id', 'tvg-name', 'tvg-logo', 'group-title'];
        $tagStr = "";

        // 首先按顺序排前四个核心标签
        foreach ($order as $key) {
            if (isset($finalTags[$key])) {
                $tagStr .= " $key=\"" . $finalTags[$key] . "\"";
                unset($finalTags[$key]); // 提取后从原数组删除，方便后续处理“其他”标签
            }
        }

        // 排除掉不显示的 origin-url
        if (isset($finalTags['origin-url'])) unset($finalTags['origin-url']);

        // 接着排剩下的其他所有标签
        foreach ($finalTags as $k => $v) {
            $tagStr .= " $k=\"$v\"";
        }

        // 4. 组合成最终行：#EXTINF:-1 标签, 频道名称
        $m3u[] = "#EXTINF:-1$tagStr," . $name;
        
        // 处理 KODIPROP 属性
        if (!empty($m['props'])) foreach ($m['props'] as $p) $m3u[] = $p;
        
        // 写入 URL
        $m3u[] = $m['url'];
    }
    // 关键：处理完一个频道后，从分组中删除，避免模板中有重复频道名时导致线路重复输出
    unset($grouped[$name]);
}
file_put_contents($outputFile, implode("\n", $m3u));
chmod($outputFile, 0666); // [新增] 确保 root 生成的文件，普通用户也能访问

// --- [ 5. 自动清理 7 天前的旧日志 ] ---
$logFiles = glob("log_*.log");
$retentionDays = 7;
$now = time();

foreach ($logFiles as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= $retentionDays * 86400) {
            unlink($file);
            logMsg("清理旧日志文件: $file", "INFO");
        }
    }
}

$scriptEndTime = microtime(true);
$totalDuration = $scriptEndTime - $scriptStartTime;

// 格式化耗时：如果超过60秒就显示“分秒”，否则显示“秒”
$timeString = ($totalDuration >= 60) 
    ? floor($totalDuration / 60) . " 分 " . round($totalDuration % 60, 2) . " 秒" 
    : round($totalDuration, 2) . " 秒";

// 直接调用你现有的 logMsg 函数
logMsg("任务结束！成功聚合并精简线路。总耗时: {$timeString}", "SUCCESS");
