<?php

/*
	(C) Copyright 2009-2010 myTinyTodo by Max Pozdeev <maxpozdeev@gmail.com>
	(C) Copyright 2017      fork myTDX by Jérémie FRANCOIS <jeremie.francois@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

require_once('./init.php');

$lang = Lang::instance();

if($needAuth && !is_logged())
{
	die("Access denied!<br/> Disable password protection or Log in.");
}

if(isset($_POST['save']))
{
	$t = array();
	$langs = getLangs();
	Config::$params['lang']['options'] = array_keys($langs);
	Config::set('lang', _post('lang'));
	
	// in Demo mode we can set only language by cookies
	if(defined('MTTDEMO')) {
		setcookie('lang', Config::get('lang'), 0, url_dir(Config::get('url')=='' ? $_SERVER['REQUEST_URI'] : Config::get('url')));
		$t['saved'] = 1;
		jsonExit($t);
	}
	
	if(isset($_POST['password']) && $_POST['password'] != '') Config::set('password', $_POST['password']);
	elseif(!_post('allowpassword')) Config::set('password', '');
	
	// Handle 2FA settings
	if(isset($_POST['enable_2fa']) && _post('enable_2fa') == '1') {
		Config::set('totp_enabled', 1);
		// Generate new secret if needed
		if(_post('totp_secret') == '' && !isset($_POST['generate_2fa'])) {
			require_once(MTTPATH. 'class.totp.php');
			Config::set('totp_secret', TOTP::generateSecret());
		}
	} elseif(isset($_POST['enable_2fa']) && _post('enable_2fa') == '0') {
		Config::set('totp_enabled', 0);
	}
	
	// Allow manual secret entry for advanced users
	if(isset($_POST['totp_secret']) && $_POST['totp_secret'] != '') {
		Config::set('totp_secret', trim($_POST['totp_secret']));
	}
	
	Config::set('smartsyntax', (int)_post('smartsyntax'));
	Config::set('markdown', (int)_post('markdown'));
	// Do not set invalid timezone
	try {
	    $tz = trim(_post('timezone'));
	    $testTZ = new DateTimeZone($tz); //will throw Exception on invalid timezone
	    Config::set('timezone', $tz);
	}
	catch (Exception $e) {
	}
	Config::set('autotag', (int)_post('autotag'));
	Config::set('session', _post('session'));
	Config::set('firstdayofweek', (int)_post('firstdayofweek'));
	Config::set('clock', (int)_post('clock'));
	Config::set('dateformat', _post('dateformat'));
	Config::set('dateformat2', _post('dateformat2'));
	Config::set('dateformatshort', _post('dateformatshort'));
	Config::set('title', trim(_post('title')));
	Config::set('showdate', (int)_post('showdate'));
	Config::set('alientags', (int)_post('alientags'));
	Config::set('taskxrefs', (int)_post('taskxrefs'));
	Config::set('dbbackup', (int)_post('dbbackup'));
	Config::save();
	$t['saved'] = 1;
	jsonExit($t);
}


function _c($key)
{
	return Config::get($key);
}

function getLangs($withContents = 0)
{
    if (!$h = opendir(MTTPATH. 'lang')) return false;
    $a = array();
    while(false !== ($file = readdir($h)))
	{
		if(preg_match('/(.+)\.php$/', $file, $m) && $file != 'class.default.php') {
			$a[$m[1]] = $m[1];
			if($withContents) {
			    $a[$m[1]] = getLangDetails(MTTPATH. 'lang'. DIRECTORY_SEPARATOR. $file, $m[1]);
			    $a[$m[1]]['name'] = $a[$m[1]]['original_name'];
			    $a[$m[1]]['title'] = $a[$m[1]]['language'];
			}
		}
    }
    closedir($h);
    return $a;
}

function getLangDetails($filename, $default)
{
    $contents = file_get_contents($filename);
    $a = array('language'=>$default, 'original_name'=>$default);
    if(preg_match("|/\\*\s*myTinyTodo language pack([\s\S]+?)\\*/|", $contents, $m))
	{
	    $str = $m[1];
	 	if(preg_match("|Language\s*:\s*(.+)|i", $str, $m)) {
			$a['language'] = trim($m[1]);
		}
		if(preg_match("|Original name\s*:\s*(.+)|i", $str, $m)) {
			$a['original_name'] = trim($m[1]);
		}
	}
	return $a;
}

function selectOptions($a, $value, $default=null)
{
	if(!$a) return '';
	$s = '';
	if($default !== null && !isset($a[$value])) $value = $default;
	foreach($a as $k=>$v) {
		$s .= '<option value="'.htmlspecialchars($k).'" '.($k===$value?'selected="selected"':'').'>'.htmlspecialchars($v).'</option>';
	}
	return $s;
}

/*
    @param array $a             array of id=>array(name, optional title)
    @param mixed $key           Key of OPTION to be selected
    @param mixed $default       Default key if $key is not present in $a
*/
function selectOptionsA($a, $key, $default=null)
{
	if(!$a) return '';
	$s = '';
	if($default !== null && !isset($a[$key])) $key = $default;
	foreach($a as $k=>$v) {
		$s .= '<option value="'.htmlspecialchars($k).'" '.($k===$key?'selected="selected"':'').
		    (isset($v['title']) ? ' title="'.htmlspecialchars($v['title']).'"' : '').
		    '>'.htmlspecialchars($v['name']).'</option>';
	}
	return $s;
}

function timezoneIdentifiers()
{
    $zones = DateTimeZone::listIdentifiers();
    $a = array();
    foreach($zones as $v) {
        $a[$v] = $v;
    }
    return $a;
}

header('Content-type:text/html; charset=utf-8');
?>

<div><a href="#" class="mtt-back-button"><?php _e('go_back');?></a></div>

<h3><?php _e('set_header');?></h3>

<div id="settings_msg" style="display:none"></div>

<form id="settings_form" method="post" action="settings.php">

<table class="mtt-settings-table">

<tr>
<th><?php _e('set_title');?>:<br/><span class="descr"><?php _e('set_title_descr');?></span></th>
<td> <input name="title" value="<?php echo htmlspecialchars(_c('title'));?>" class="in350" /> </td>
</tr>

<tr>
<th><?php _e('set_language');?>:</th>
<td> <select name="lang"><?php echo selectOptionsA(getLangs(1), _c('lang')); ?></select> </td>
</tr>

<tr>
<th><?php _e('set_protection');?>:</th>
<td>
 <label><input type="radio" name="allowpassword" value="1" <?php if(_c('password')!='') echo 'checked="checked"'; ?> onclick='$(this.form).find("input[name=password]").attr("disabled",false)' /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="allowpassword" value="0" <?php if(_c('password')=='') echo 'checked="checked"'; ?> onclick='$(this.form).find("input[name=password]").attr("disabled","disabled")' /><?php _e('set_disabled');?></label> <br/>
</td></tr>

<tr>
<th><?php _e('set_newpass');?>:<br/><span class="descr"><?php _e('set_newpass_descr');?></span></th>
<td> <input type="password" name="password" <?php if(_c('password')=='') echo "disabled"; ?> /> </td>
</tr>

<tr>
<th>Two-Factor Authentication (2FA):<br/><span class="descr">Add TOTP-based 2FA for enhanced security</span></th>
<td>
<?php
	$totp_enabled = (int)_c('totp_enabled');
	$totp_secret = _c('totp_secret');
	$hasSecret = $totp_secret != '';
?>
<label><input type="radio" name="enable_2fa" value="1" <?php if($totp_enabled) echo 'checked="checked"'; ?> /> Enable 2FA</label> <br/>
<label><input type="radio" name="enable_2fa" value="0" <?php if(!$totp_enabled) echo 'checked="checked"'; ?> /> Disable 2FA</label> <br/>
<?php if($hasSecret): ?>
<span style="color:green;">2FA is configured</span>
<?php else: ?>
<span style="color:orange;">2FA not configured</span>
<?php endif; ?>
<input type="hidden" name="totp_secret" id="totp_secret" value="<?php echo htmlspecialchars($totp_secret); ?>" />
<button type="button" id="btn_generate_2fa" <?php if($totp_enabled && !$hasSecret) echo 'style="display:none;"'; ?>>Generate New Secret</button>
<button type="button" id="btn_show_qr" <?php if(!$hasSecret) echo 'style="display:none;"'; ?>>Show QR Code</button>
<div id="2fa_qr_container" style="display:none; margin-top:10px;"></div>
<div id="2fa_code" style="display:none; margin-top:10px; font-family:monospace; font-size:18px; letter-spacing:3px;"></div>
</td>
</tr>

<tr>
<th><?php _e('set_smartsyntax');?>:<br/><span class="descr"><?php _e('set_smartsyntax_descr');?></span></th>
<td>
 <label><input type="radio" name="smartsyntax" value="1" <?php if(_c('smartsyntax')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="smartsyntax" value="0" <?php if(!_c('smartsyntax')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_markdown');?>:<br/></th>
<td>
 <label><input type="radio" name="markdown" value="1" <?php if(_c('markdown')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="markdown" value="0" <?php if(!_c('markdown')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_autotag');?>:<br/><span class="descr"><?php _e('set_autotag_descr');?></span></th>
<td>
 <label><input type="radio" name="autotag" value="1" <?php if(_c('autotag')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="autotag" value="0" <?php if(!_c('autotag')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_sessions');?>:</th>
<td>
 <label><input type="radio" name="session" value="default" <?php if(_c('session')=='default') echo 'checked="checked"'; ?> /><?php _e('set_sessions_php');?></label> <br/>
 <label><input type="radio" name="session" value="files" <?php if(_c('session')=='files') echo 'checked="checked"'; ?> /><?php _e('set_sessions_files');?></label> <span class="descr">(&lt;mytinytodo_dir&gt;/tmp/sessions)</span>
</td></tr>

<tr>
<th><?php _e('set_timezone');?>:</th>
<td>
 <select name="timezone"><?php echo selectOptions(timezoneIdentifiers(), _c('timezone')); ?></select>
</td></tr>

<tr>
<th><?php _e('set_firstdayofweek');?>:</th>
<td>
 <select name="firstdayofweek"><?php echo selectOptions(__('days_long'), _c('firstdayofweek')); ?></select>
</td></tr>

<tr>
<th><?php _e('set_date');?>:</th>
<td>
 <input name="dateformat" value="<?php echo htmlspecialchars(_c('dateformat'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformat.value=this.value;">
 <?php echo selectOptions(array('F j, Y'=>formatTime('F j, Y'), 'M d, Y'=>formatTime('M d, Y'), 'j M Y'=>formatTime('j M Y'), 'd F Y'=>formatTime('d F Y'),
	'n/j/Y'=>formatTime('n/j/Y'), 'd.m.Y'=>formatTime('d.m.Y'), 'j. F Y'=>formatTime('j. F Y'), 0=>__('set_custom')), _c('dateformat'), 0); ?>
 </select>
</td></tr>

<tr>
<th><?php _e('set_date2');?>:</th>
<td>
 <input name="dateformat2" value="<?php echo htmlspecialchars(_c('dateformat2'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformat2.value=this.value;">
 <?php echo selectOptions(array('Y-m-d'=>'yyyy-mm-dd ('.date('Y-m-d').')',
       'n/j/y'=>'m/d/yy ('.date('n/j/y').')',
       'd.m.y'=>'dd.mm.yy ('.date('d.m.y').')',
       'd/m/y'=>'dd/mm/yy ('.date('d/m/y').')', 0=>__('set_custom')), _c('dateformat2'), 0); ?>
 </select>
</td></tr>

<tr>
<th><?php _e('set_shortdate');?>:</th>
<td>
 <input name="dateformatshort" value="<?php echo htmlspecialchars(_c('dateformatshort'));?>" />
 <select onchange="if(this.value!=0) this.form.dateformatshort.value=this.value;">
 <?php echo selectOptions(array('M d'=>formatTime('M d'), 'j M'=>formatTime('j M'), 'n/j'=>formatTime('n/j'), 'd.m'=>formatTime('d.m'), 0=>__('set_custom')), _c('dateformatshort'), 0); ?>
 </select>
</td></tr>

<tr>
<th><?php _e('set_clock');?>:</th>
<td>
 <select name="clock"><?php echo selectOptions(array(12=>__('set_12hour').' ('.date('g:i A').')', 24=>__('set_24hour').' ('.date('H:i').')'), _c('clock')); ?></select>
</td></tr>

<tr>
<th><?php _e('set_showdate');?>:</th>
<td>
 <label><input type="radio" name="showdate" value="1" <?php if(_c('showdate')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="showdate" value="0" <?php if(!_c('showdate')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td>
</tr>

<tr>
<th><?php _e('set_alientags');?>:</th>
<td>
 <label><input type="radio" name="alientags" value="1" <?php if(_c('alientags')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="alientags" value="0" <?php if(!_c('alientags')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td>
</tr>

<tr>
<th><?php _e('set_taskxrefs');?>:</th>
<td>
 <label><input type="radio" name="taskxrefs" value="1" <?php if(_c('taskxrefs')) echo 'checked="checked"'; ?> /><?php _e('set_enabled');?></label> <br/>
 <label><input type="radio" name="taskxrefs" value="0" <?php if(!_c('taskxrefs')) echo 'checked="checked"'; ?> /><?php _e('set_disabled');?></label>
</td>
</tr>

<tr>
<th><?php _e('set_dbbackup');?>:<br/><span class="descr"><?php _e('set_dbbackup_descr');?></span></th>
<td>
 <select name="dbbackup"><?php echo selectOptions(array(
		0=>__('set_always'),
		1=>__('set_24hour'),
		7=>__('set_7day'),
	 	30=>__('set_1month'),
	 	365=>__('set_1year'),
		-1=>__('set_nobackup')),
		 _c('dbbackup')); ?></select>
</td></tr>

<tr><td colspan="2" class="form-buttons">

<input type="submit" value="<?php _e('set_submit');?>" />
<input type="button" class="mtt-back-button" value="<?php _e('set_cancel');?>" />

</td></tr>
</table>

</form>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
    // 2FA management
    var totpSecret = $('#totp_secret').val();

    // Generate new 2FA secret
    $('#btn_generate_2fa').click(function() {
        $.get('ajax.php?generate_2fa_secret', function(data) {
            if(data.error) {
                alert(data.error);
                return;
            }
            $('#totp_secret').val(data.secret);
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.uri);
            $('#2fa_qr_container').html('<img src="' + qrUrl + '" alt="2FA QR Code" style="display:block; margin:10px auto;" />');
            $('#2fa_code').text(data.secret.match(/.{1,4}/g).join(' '));
            $('#2fa_code').show();
            $('#2fa_qr_container').show();
            $('#btn_generate_2fa').hide();
            $('#btn_show_qr').show();
        });
    });

    // Show QR code (in case it was hidden)
    $('#btn_show_qr').click(function() {
        var secret = $('#totp_secret').val();
        if(!secret) {
            alert('No secret configured. Generate one first.');
            return;
        }
        var uri = 'otpauth://totp/myTDX:user?secret=' + secret.replace(/=/g,'') + '&issuer=myTDX';
        var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(uri);
        $('#2fa_qr_container').html('<img src="' + qrUrl + '" alt="2FA QR Code" style="display:block; margin:10px auto;" />');
        $('#2fa_code').text(secret.match(/.{1,4}/g).join(' '));
        $('#2fa_qr_container').show();
        $('#2fa_code').show();
        $(this).hide();
    });

    // Enable/disable 2FA radio buttons
    $('input[name="enable_2fa"]').change(function() {
        var enabled = $(this).val() == '1';
        if(enabled && !$('#totp_secret').val()) {
            if(confirm('You need a 2FA secret first. Generate one now?')) {
                $('#btn_generate_2fa').click();
            }
        }
    });
});
//]]>
</script>
