<?php

/*
	(C) Copyright 2009-2010 myTinyTodo by Max Pozdeev <maxpozdeev@gmail.com>
	(C) Copyright 2017      fork myTDX by Jérémie FRANCOIS <jeremie.francois@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

function get_version()
{
    return trim(file_get_contents('version.txt'));
}

function htmlarray($a, $exclude=null)
{
	htmlarray_ref($a, $exclude);
	return $a;
}

function htmlarray_ref(&$a, $exclude=null)
{
	if(!$a) return;
	if(!is_array($a)) {
		$a = htmlspecialchars($a);
		return;
	}
	reset($a);
	if($exclude && !is_array($exclude)) $exclude = array($exclude);
	foreach($a as $k=>$v)
	{
		if(is_array($v)) $a[$k] = htmlarray($v, $exclude);
		elseif(!$exclude) $a[$k] = htmlspecialchars($v);
		elseif(!in_array($k, $exclude)) $a[$k] = htmlspecialchars($v);
	}
	return;
}

function _post($param,$defvalue = '')
{
	if(!isset($_POST[$param])) 	{
		return $defvalue;
	}
	else {
		return $_POST[$param];
	}
}

function _get($param,$defvalue = '')
{
	if(!isset($_GET[$param])) {
		return $defvalue;
	}
	else {
		return $_GET[$param];
	}
} 

class Config
{
	public static $params = array(
		'db' => array('default'=>'sqlite', 'type'=>'s'),
		'mysql.host' => array('default'=>'localhost', 'type'=>'s'),
		'mysql.db' => array('default'=>'mytinytodo', 'type'=>'s'),
		'mysql.user' => array('default'=>'user', 'type'=>'s'),
		'mysql.password' => array('default'=>'', 'type'=>'s'),
		'prefix' => array('default'=>'', 'type'=>'s'),
		'url' => array('default'=>'', 'type'=>'s'),
		'mtt_url' => array('default'=>'', 'type'=>'s'),
		'title' => array('default'=>'', 'type'=>'s'),
		'lang' => array('default'=>'en', 'type'=>'s'),
		'password' => array('default'=>'', 'type'=>'s'),
		'totp_enabled' => array('default'=>0, 'type'=>'i'),
		'totp_secret' => array('default'=>'', 'type'=>'s'),
		'smartsyntax' => array('default'=>1, 'type'=>'i'),
		'markdown' => array('default'=>1, 'type'=>'i'),
		'timezone' => array('default'=>'UTC', 'type'=>'s'),
		'autotag' => array('default'=>1, 'type'=>'i'),
		'duedateformat' => array('default'=>1, 'type'=>'i'),
		'firstdayofweek' => array('default'=>1, 'type'=>'i'),
		'session' => array('default'=>'files', 'type'=>'s', 'options'=>array('files','default')),
		'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
		'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
		'dateformat2' => array('default'=>'n/j/y', 'type'=>'s'),
		'dateformatshort' => array('default'=>'j M', 'type'=>'s'),
		'template' => array('default'=>'default', 'type'=>'s'),
		'showdate' => array('default'=>0, 'type'=>'i'),
		'alientags' => array('default'=>0, 'type'=>'i'),
		'taskxrefs' => array('default'=>0, 'type'=>'i'),
		'dbbackup' => array('default'=>30, 'type'=>'i', 'options'=>array(-1,0,1,7,30,365)),
	);

	public static $config;

	public static function loadConfig($config)
	{
		self::$config = $config;
	}

	public static function get($key)
	{
		if(isset(self::$config[$key])) return self::$config[$key];
		elseif(isset(self::$params[$key])) return self::$params[$key]['default'];
		else return null;
	}

	public static function set($key, $value)
	{
		self::$config[$key] = $value;
	}

	public static function save()
	{
		$s = '';
		foreach(self::$params as $param=>$v)
		{
			if(!isset(self::$config[$param])) $val = $v['default'];
			elseif(isset($v['options']) && !in_array(self::$config[$param], $v['options'])) $val = $v['default'];
			else $val = self::$config[$param];
			if($v['type']=='i') {
				$s .= "\$config['$param'] = ".(int)$val.";\n";
			}
			else {
				$s .= "\$config['$param'] = '".str_replace(array("\\","'"),array("\\\\","\\'"),$val)."';\n";
			}
		}
		$f = fopen(MTTPATH. 'db/config.php', 'w');
		if($f === false) throw new Exception("Error while saving config file");
		fwrite($f, "<?php\n\$config = array();\n$s?>");
		fclose($f);
	}
}

function formatDate3($format, $ay, $am, $ad, $lang)
{
	# F - month long, M - month short
	# m - month 2-digit, n - month 1-digit
	# d - day 2-digit, j - day 1-digit
	$ml = $lang->get('months_long');
	$ms = $lang->get('months_short');
	$Y = $ay;
	$y = $Y < 2010 ? '0'.($Y-2000) : $Y-2000;
	$n = $am;
	$m = $n < 10 ? '0'.$n : $n;
	$F = $ml[$am-1];
	$M = $ms[$am-1];
	$j = $ad;
	$d = $j < 10 ? '0'.$j : $j;
	return strtr($format, array('Y'=>$Y, 'y'=>$y, 'F'=>$F, 'M'=>$M, 'n'=>$n, 'm'=>$m, 'd'=>$d, 'j'=>$j));
}

function url_dir($url)
{
	if(false !== $p = strpos($url, '?')) $url = substr($url,0,$p); # to avoid parse errors on strange query strings
	$p = parse_url($url, PHP_URL_PATH);
	if($p == '') return '/';
	if(substr($p,-1) == '/') return $p;
	if(false !== $pos = strrpos($p,'/')) return substr($p,0,$pos+1);
	return '/';
}

function escapeTags($s)
{
	$c1 = chr(1);
	$c2 = chr(2);
	$s = preg_replace("~<b>([\s\S]*?)</b>~i", "${c1}b${c2}\$1${c1}/b${c2}", $s);
	$s = preg_replace("~<i>([\s\S]*?)</i>~i", "${c1}i${c2}\$1${c1}/i${c2}", $s);	
	$s = preg_replace("~<u>([\s\S]*?)</u>~i", "${c1}u${c2}\$1${c1}/u${c2}", $s);
	$s = preg_replace("~<s>([\s\S]*?)</s>~i", "${c1}s${c2}\$1${c1}/s${c2}", $s);
	$s = str_replace(array($c1, $c2), array('<','>'), htmlspecialchars($s));
	return $s;
}

/* found in comments on http://www.php.net/manual/en/function.uniqid.php#94959 */
function generateUUID()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

class DBConnection
{
	protected static $instance;

	public static function init($instance)
	{
		self::$instance = $instance;
		return $instance;
	}

	public static function instance()
	{
        if (!isset(self::$instance)) {
			//$c = __CLASS__;
			$c = 'DBConnection';
			self::$instance = new $c;
        }
		return self::$instance;	
	}
}

function are_files_identical($fn1, $fn2)
{
	if(filesize($fn1) !== filesize($fn2)) return FALSE;
	if(filetype($fn1) !== filetype($fn2)) return FALSE;
	if(!$fp1 = fopen($fn1, 'rb')) return FALSE;
	if(!$fp2 = fopen($fn2, 'rb')) { fclose($fp1); return FALSE; }
	$same = TRUE;
	while(!feof($fp1) and !feof($fp2))
		if(fread($fp1, 16384) !== fread($fp2, 16384)) { $same = FALSE; break; }
	if(feof($fp1) !== feof($fp2)) $same = FALSE;
	fclose($fp1);
	fclose($fp2);
	return $same;
}

?>
