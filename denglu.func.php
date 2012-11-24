<?php
if (!function_exists('class_http')) {
	function close_curl() {
		if (!extension_loaded('curl')) {
			return " <span style=\"color:blue\">请在php.ini中打开扩展extension=php_curl.dll</span>";
		} else {
			$func_str = '';
			if (!function_exists('curl_init')) {
				$func_str .= "curl_init() ";
			} 
			if (!function_exists('curl_setopt')) {
				$func_str .= "curl_setopt() ";
			} 
			if (!function_exists('curl_exec')) {
				$func_str .= "curl_exec()";
			} 
			if ($func_str)
				return " <span style=\"color:blue\">不支持 $func_str 等函数，请在php.ini里面的disable_functions中删除这些函数的禁用！</span>";
		} 
	} 
	// SSL
	function http_ssl($url) {
		$arrURL = parse_url($url);
		$r['ssl'] = $arrURL['scheme'] == 'https' || $arrURL['scheme'] == 'ssl';
		$is_ssl = isset($r['ssl']) && $r['ssl'];
		if ($is_ssl && !extension_loaded('openssl'))
			return wp_die('您的主机不支持openssl，请查看<a href="' . MY_PLUGIN_URL . '/check.php" target="_blank">环境检查</a>');
	} 
	function class_http($url, $params = array()) {
		if ($params['http']) {
			$class = 'WP_Http_' . ucfirst($params['http']);
		} else {
			if (!close_curl()) {
				$class = 'WP_Http_Curl';
			} else {
				http_ssl($url);
				if (@ini_get('allow_url_fopen') && function_exists('fopen')) {
					$class = 'WP_Http_Streams';
				} elseif (function_exists('fsockopen')) {
					$class = 'WP_Http_Fsockopen';
				} else {
					return wp_die('没有可以完成请求的 HTTP 传输器，请查看<a href="' . MY_PLUGIN_URL . '/check.php" target="_blank">环境检查</a>');
				} 
			} 
		} 
		$http = new $class;
		$response = $http -> request($url, $params);
		if (!is_array($response)) {
			if ($params['method'] == 'GET' && @ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
				return file_get_contents($url . '?' . $params['body']);
			} 
			$errors = $response -> errors;
			$error = $errors['http_request_failed'][0];
			if (!$error)
				$error = $errors['http_failure'][0];
			if ($error == "couldn't connect to host" || strpos($error, 'timed out') !== false) {
				return;
			} 
			wp_die('出错了: ' . $error . '<br /><br />可能是您的主机不支持，请查看<a href="' . MY_PLUGIN_URL . '/check.php" target="_blank">环境检查</a>');
		} 
		return $response['body'];
	} 
    // GET
	function get_url_contents($url, $timeout = 30) {
		if (!close_curl()) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			$content = curl_exec($ch);
			curl_close($ch);
			return $content;
		} else {
			$params = array();
			if (@ini_get('allow_url_fopen')) {
				if (function_exists('file_get_contents')) {
					return file_get_contents($url);
				} 
				if (function_exists('fopen')) {
					$params['http'] = 'streams';
				} 
			} elseif (function_exists('fsockopen')) {
				$params['http'] = 'fsockopen';
			} else {
				return wp_die('没有可以完成请求的 HTTP 传输器，请查看<a href="' . MY_PLUGIN_URL . '/check.php" target="_blank">环境检查</a>');
			} 
			$params += array("method" => 'GET',
				"timeout" => $timeout,
				"sslverify" => false
				);
			return class_http($url, $params);
		} 
	} 

	function get_url_array($url) {
		return json_decode(get_url_contents($url), true);
	} 
}

if (!function_exists('ifabc')) {
	function ifab($a, $b) {
		return $a ? $a : $b;
	} 
	function ifb($a, $b) {
		return $a ? $b : '';
	} 
	function ifac($a, $b, $c) {
		return $a ? $a : ($b ? $c : '');
	} 
	function ifabc($a, $b, $c) {
		return $a ? $a : ($b ? $b : $c);
	} 
	function ifold($str, $old, $new) { // 以旧换新
		return (empty($str) || $str == $old) ? $new : $str;
	}
} 
// 根据键名、键值对比,得到数组的差集 array_diff_assoc  < 4.3.0
if (!function_exists('array_diff_assoc')) {
	function array_diff_assoc($a1, $a2) {
		foreach($a1 as $key => $value) {
			if (isset($a2[$key])) {
				if ((string) $value !== (string) $a2[$key]) {
					$r[$key] = $value;
				} 
			} else {
				$r[$key] = $value;
			} 
		} 
		return $r;
	} 
} 
// 从数组中取出一段，保留键值 array_slice  < 5.0.2
if (!function_exists('php_array_slice')) {
	function php_array_slice($array, $offset, $length = null, $preserve_keys = false) {
		if (!$preserve_keys || version_compare(PHP_VERSION, '5.0.1', '>')) {
			return array_slice($array, $offset, $length, $preserve_keys);
		} 
		if (!is_array($array)) {
			user_error('The first argument should be an array', E_USER_WARNING);
			return;
		} 
		$keys = array_slice(array_keys($array), $offset, $length);
		$ret = array();
		foreach ($keys as $key) {
			$ret[$key] = $array[$key];
		} 
		return $ret;
	} 
}

/**
 * WordPress连接微博 自定义函数
 */ 
// 匹配视频,图片
if (!function_exists('preg_match_media_url')) {
	function preg_match_media_url($content, $post_ID = '') {
		preg_match_all('/<embed[^>]+src=[\"\']{1}(([^\"\'\s]+)\.swf)[\"\']{1}[^>]+>/isU', $content, $video);
		preg_match_all('/<img[^>]+src=[\'"](http[^\'"]+)[\'"].*>/isU', $content, $image);
		$v_sum = count($video[1]);
		if ($v_sum > 0) {
			$v = $video[1][0];
		} else {
			$content = str_replace(array("[/", "</"), "\n", $content);
			preg_match_all('/http:\/\/(v.youku.com\/v_show|www.tudou.com\/(programs\/view|albumplay|listplay))+(?(?=[\/])(.*))/', $content, $match);
			if (count($match[0]) > 0) $v = trim($match[0][0]);
		} 
		$p_sum = count($image[1]);
		if ($p_sum > 0) {
			$p = $image[1][0];
		} elseif (is_numeric($post_ID) && function_exists('has_post_thumbnail') && has_post_thumbnail($post_ID)) { // 特色图像 WordPress v2.9.0
			$image_url = wp_get_attachment_image_src(get_post_thumbnail_id($post_ID), 'full');
			$p = $image_url[0];
		} 
		if ($p || $v)
			return array($p, $v);
	} 
}
// 得到图片url
if (!function_exists('get_content_pic')) {
	function get_content_pic($content) {
		preg_match_all('/<img[^>]+src=[\'"](http[^\'"]+)[\'"].*>/isU', $content, $image);
		return $image[1][0];
	} 
}
// 保存wp_comments表某个字段
if (!function_exists('wp_update_comment_key')) {
	function wp_update_comment_key($comment_ID, $comment_key, $vaule) {
		global $wpdb;
		$$comment_key = $vaule;
		$result = $wpdb -> update($wpdb -> comments, compact($comment_key), compact('comment_ID'));
		return $result;
	} 
} 
// 判断是否启用了某个插件
if (!function_exists('is_plugin_activate')) {
	function is_plugin_activate($plugin) {
		if (in_array($plugin, (array) get_option('active_plugins', array()))) {
			return true;
		} elseif (is_multisite()) {
			$plugins = get_site_option('active_sitewide_plugins');
			if (isset($plugins[$plugin]))
				return true;
		} 
		return false;
	} 
}
// 判断是否安装了 WordPress连接微博 V1
function wp_connect_v1() {
	if (is_plugin_activate('wp-connect/wp-connect.php')) {
		$wptm_version = get_option('wptm_version');
		if ($wptm_version && version_compare($wptm_version, '2.0', '<')) {
			return true;
		} 
	} 
}
// 灯鹭控制台
if (!function_exists('denglu_admin')) {
	function denglu_admin($vaule) {
		global $wptm_basic;
		$appid = $wptm_basic['appid'];
		$appkey = $wptm_basic['appkey'];
		$timestamp = BJTIMESTAMP . '000';
		$sign_type = "md5";
		$version = "1.0";
		$sign = md5('appid=' . $appid . 'sign_type=' . $sign_type . 'timestamp=' . $timestamp . 'version=' . $version . $appkey);
		$denglu_url = 'http://open.denglu.cc/' . $vaule . '?open=' . base64_encode("appid=" . $appid . "&timestamp=" . $timestamp . "&version=" . $version . "&sign_type=" . $sign_type . "&sign=" . $sign);
		echo '<p><strong>您可以在“评论管理”页面对评论进行删除/修改操作，也会同步到本地数据库噢。 <a href="' . $denglu_url . '" target="_blank">请新窗口打开</a></strong></p>';
		echo '<p><iframe width="100%" height="550" frameborder="0" src="' . $denglu_url . '"></iframe></p>';
	} 
	function denglu_ocomment5() {
		return denglu_admin('setup/ocomment5');
	} 
}
// 得到微博头像
if (!function_exists('wp_get_weibo_head')) {
	function wp_get_weibo_head($comment, $size, $email, $author_url) {
		$tname = array('@weibo.com' => '3',
			'@t.qq.com' => '4',
			'@t.sohu.com' => '5',
			'@renren.com' => '7',
			'@kaixin001.com' => '8',
			'@douban.com' => '9',
			'@qzone.qq.com' => '13',
			'@baidu.com' => '19',
			'@tianya.cn' => '17',
			'@twitter.com' => '28'
			);
		$tmail = strstr($email, '@');
		if ($mediaID = $tname[$tmail]) {
			$weibo_uid = str_replace($tmail, '', $email);
			if ($mediaID == 3) {
				$out = 'http://tp' . rand(1, 4) . '.sinaimg.cn/' . $weibo_uid . '/50/0/1';
			} elseif ($mediaID == 9) {
				$out = 'http://img' . rand(1, 5) . '.douban.com/icon/u' . $weibo_uid . '-1.jpg';
			} elseif ($mediaID == 13) {
				$out = 'http://qzapp.qlogo.cn/qzapp/' . $weibo_uid . '/50';
			} elseif ($mediaID == 17) {
				$out = 'http://tx.tianyaui.com/logo/small/' . $weibo_uid;
			} elseif (function_exists('get_avatar_url')) {
				$out = get_avatar_url($weibo_uid, $mediaID);
				if ($out) {
					if ($mediaID == 4) {
						$out = 'http://app.qlogo.cn/mbloghead/' . $out . '/50';
					} elseif ($mediaID == 19) {
						$out = 'http://himg.bdimg.com/sys/portraitn/item/' . $out . '.jpg';
					} 
				} 
			} 
			if ($out) {
				$avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
				if ($author_url) {
					$avatar = "<a href='{$author_url}' rel='nofollow' target='_blank'>$avatar</a>";
				} 
				return $avatar;
			} 
		} 
	} 
}
// 显示新浪微博用户头像
add_filter("get_avatar", "denglu_avatar", 9, 3);
function denglu_avatar($avatar, $id_or_email = '', $size = '32') {
	global $comment;
	if (is_object($comment)) {
		$uid = $comment -> user_id;
		$email = $comment -> comment_author_email;
		$author_url = $comment -> comment_author_url;
		if ($avatar1 = wp_get_weibo_head($comment, $size, $email, $author_url)) { // V2.4
			return $avatar1;
		} 
	} 
	return $avatar;
}

/**
 * 评论函数 v2.3.5
 */
if (!function_exists('dengluComments')) {
	function dlComments_open($open, $post_id = null) {
		global $wptm_comment;
		if (empty($wptm_comment['comments_open']) || (!empty($wptm_comment['comments_open']) && $open)) {
			return true;
		} 
		return false;
	}
	add_filter('comments_open', 'dlComments_open', 10, 2);
	function dengluComments() {
		global $post;
		if (comments_open()) {
	        $wptm_basic = get_option('wptm_basic');
	        $wptm_comment = get_option('wptm_comment');
			$user = wp_get_current_user();
			if ($user->ID) {
				$head = get_content_pic(get_avatar($user->ID, 50));
				if (strpos($head, "gravatar.com/avatar") !== false) $head = "";
				$userinfo = base64_encode($user->display_name.','.$user->user_email.','.$head);
			} 
			if (is_object($post)) {
				$media_url = preg_match_media_url($post -> post_content, $post -> ID);
			} 
?>
<script type='text/javascript' charset='utf-8' src='http://open.denglu.cc/connect/commentcode?appid=<?php echo $wptm_basic['appid'];?>&v=1.0.1'></script>
<script type="text/javascript" charset='utf-8'>
    var param = {};
    param.title = "<?php echo rawurlencode(get_the_title());?>"; // 文章标题
    param.postid = "<?php the_ID();?>"; // 文章ID
<?php
	if ($media_url) { // 是否有视频、图片
    echo "param.image = \"" . $media_url[0] ."\";\n"; // 需要同步的图片地址
    echo "param.video = \"" . $media_url[1] ."\";\n"; // 需要同步的视频地址，支持土豆优酷等
    }
	echo (!is_user_logged_in()) ? "" :"param.userinfo = \"".$userinfo."\";param.login = true;\n"; // 是否已经登录
	echo "param.exit = \"".urlencode(wp_logout_url(get_permalink()))."\";\n"; // 退出链接
?>
    _dl_comment_widget.show(param);
</script>
<?php if ($wptm_comment['enable_seo'] && have_comments()) { ?>
<div id="dengluComments">
	<h3 id="comments"><?php	printf( '《%2$s》有 %1$s 条评论', number_format_i18n( get_comments_number() ), '<em>' . get_the_title() . '</em>' );?></h3>
	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
	</div>
	<ol class="commentlist">
	<?php wp_list_comments();?>
	</ol>
	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
	</div>
</div>
<script type="text/javascript">
    document.getElementById('dengluComments').style.display="none";
</script>
<?php }}}}

/**
 * 评论导入 v2.1.2
 */
if (!function_exists('denglu_importComment')) {
	// 通过uid或者email获取tid，以便获取用户的社交关联信息 V2.4
	function get_usertid($email, $uid = '') {
		$mail = strstr($email, '@');
		if ($mail == '@weibo.com' || $mail == '@t.sina.com.cn') {
			return 'stid';
		} elseif ($mail == '@t.qq.com') {
			return 'qtid';
		} elseif ($mail == '@t.sohu.com') {
			return 'shtid';
		} elseif ($mail == '@t.163.com') {
			return 'ntid';
		} elseif ($mail == '@renren.com') {
			return 'rtid';
		} elseif ($mail == '@kaixin001.com') {
			return 'ktid';
		} elseif ($mail == '@douban.com') {
			return 'dtid';
		} elseif ($mail == '@tianya.cn') {
			return 'tytid';
		} elseif ($mail == '@baidu.com') {
			return 'bdtid';
		} elseif ($mail == '@twitter.com') {
			return 'ttid';
		} elseif ($uid) {
			$user = get_userdata($uid);
			if ($user -> last_login) {
				return $user -> last_login;
			} elseif ($user -> stid) {
				return 'stid';
			} elseif ($user -> qtid) {
				return 'qtid';
			} elseif ($user -> qqtid) {
				return 'qqtid';
			} elseif ($user -> rtid) {
				return 'rtid';
			} elseif ($user -> ktid) {
				return 'ktid';
			} elseif ($user -> dtid) {
				return 'dtid';
			} elseif ($user -> gmid) {
				return 'gtid';
			} elseif ($user -> mmid || $user -> msnid) {
				return 'mtid';
			} elseif ($user -> shtid) {
				return 'shtid';
			} elseif ($user -> tbtid) {
				return 'tbtid';
			} elseif ($user -> tytid) {
				return 'tytid';
			} elseif ($user -> bdtid) {
				return 'bdtid';
			} elseif ($user -> alimid) {
				return 'alitid';
			} elseif ($user -> ymid) {
				return 'ytid';
			} elseif ($user -> wymid) { // 网易通行证
				return 'wytid';
			} elseif ($user -> guard360mid) { // 360
				return 'guard360tid';
			} elseif ($user -> tyimid) { // 天翼
				return 'tyitid';
			} elseif ($user -> fbmid) { // Facebook
				return 'fbtid';
			} elseif ($user -> tmid) { // twitter
				return 'ttid';
			} elseif ($user -> ntid) { // netease
				return 'ntid';
			} 
		} 
	} 
    // 获取用户的社交关联信息，用于识别评论者的社交帐号 V2.4
	function get_row_userinfo($uid, $tid) {
		$user = get_userdata($uid);
		if ($tid = 'stid') {
			return ($user -> smid) ? array('mediaUserID' => $user -> smid) : (($user -> stid) ? array('mediaID' => '3', 'mediaUID' => $user -> stid, 'profileImageUrl' => 'http://tp2.sinaimg.cn/' . $user -> stid . '/50/0/1', 'oauth_token' => ifac($user -> login_sina[0], $user -> tdata['tid'] == 'stid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_sina[1], $user -> tdata['tid'] == 'stid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid = 'qtid') {
			return ($user -> qmid) ? array('mediaUserID' => $user -> qmid) : (($user -> qtid) ? array('mediaID' => '4', 'mediaUID' => ifab($user -> tqqid , $user -> user_login), 'profileImageUrl' => $user -> qtid, 'oauth_token' => ifac($user -> login_qq[0], $user -> tdata['tid'] == 'qtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_qq[1], $user -> tdata['tid'] == 'qtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid = 'qqtid') {
			return ($user -> qqmid) ? array('mediaUserID' => $user -> qqmid) : (($user -> qqid) ? array('mediaID' => '13', 'mediaUID' => $user -> qqid, 'profileImageUrl' => $user -> qqtid, 'oauth_token' => $user -> qqid):'');
		} elseif ($tid = 'rtid') {
			return ($user -> rmid) ? array('mediaUserID' => $user -> rmid) : (($user -> rtid) ? array('mediaID' => '7', 'mediaUID' => ifab($user -> renrenid , $user -> user_login), 'profileImageUrl' => $user -> rtid):'');
		} elseif ($tid = 'ktid') {
			return ($user -> kmid) ? array('mediaUserID' => $user -> kmid) : (($user -> ktid) ? array('mediaID' => '8', 'mediaUID' => ifab($user -> kaxinid , $user -> user_login), 'profileImageUrl' => $user -> ktid):'');
		} elseif ($tid = 'dtid') {
			return ($user -> dmid) ? array('mediaUserID' => $user -> dmid) : (($user -> dtid) ? array('mediaID' => '9', 'mediaUID' => $user -> dtid, 'profileImageUrl' => 'http://t.douban.com/icon/u' . $user -> dtid . '-1.jpg', 'oauth_token' => ifac($user -> login_douban[0], $user -> tdata['tid'] == 'dtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_douban[1], $user -> tdata['tid'] == 'dtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid = 'gtid') {
			return array('mediaUserID' => $user -> gmid);
		} elseif ($tid = 'mtid') {
			return ($user -> mmid) ? array('mediaUserID' => $user -> mmid) : (($user -> msnid) ? array('mediaID' => '2', 'mediaUID' => $user -> msnid, 'email' => $user -> user_email) : '');
		} elseif ($tid = 'shtid') {
			return ($user -> shmid) ? array('mediaUserID' => $user -> shmid) : (($user -> shtid) ? array('mediaID' => '5', 'mediaUID' => ifab($user -> sohuid , $user -> user_login), 'profileImageUrl' => $user -> shtid, 'oauth_token' => ifac($user -> login_sohu[0], $user -> tdata['tid'] == 'shtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_sohu[1], $user -> tdata['tid'] == 'shtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid = 'ntid') {
			return ($user -> nmid) ? array('mediaUserID' => $user -> nmid) : ((is_numeric($user -> neteaseid) && $user -> neteaseid < 0) ? array('mediaID' => '6', 'mediaUID' => $user -> neteaseid, 'profileImageUrl' => $user -> ntid, 'oauth_token' => ifac($user -> login_netease[0], $user -> tdata['tid'] == 'ntid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_netease[1], $user -> tdata['tid'] == 'ntid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid = 'tbtid') {
			return ($user -> tbmid) ? array('mediaUserID' => $user -> tbmid) : (($user -> tbtid && is_numeric($user -> user_login)) ? array('mediaID' => '16', 'mediaUID' => $user -> user_login, 'email' => $user -> user_email, 'profileImageUrl' => $user -> tbtid):'');
		} elseif ($tid = 'tytid') {
			return ($user -> tymid) ? array('mediaUserID' => $user -> tymid) : (($user -> tytid) ? array('mediaID' => '17', 'mediaUID' => $user -> tytid, 'profileImageUrl' => 'http://tx.tianyaui.com/logo/small/' . $user -> tytid, 'oauth_token' => $user -> login_tianya[0], 'oauth_token_secret' => $user -> login_tianya[1]):'');
		} elseif ($tid = 'alitid') {
			return array('mediaUserID' => $user -> alimid);
		} elseif ($tid = 'bdtid') {
			return ($user -> bdmid) ? array('mediaUserID' => $user -> bdmid) : (($user -> bdtid) ? array('mediaID' => '19', 'mediaUID' => ifab($user -> baiduid , $user -> user_login), 'profileImageUrl' => 'http://himg.bdimg.com/sys/portraitn/item/' . $user -> bdtid . '.jpg'):'');
		} elseif ($tid = 'ytid') {
			return array('mediaUserID' => $user -> ymid);
		} elseif ($tid = 'wytid') { // 网易通行证
			return array('mediaUserID' => $user -> wymid);
		} elseif ($tid = 'guard360tid') { // 360
			return array('mediaUserID' => $user -> guard360mid);
		} elseif ($tid = 'tyitid') { // 天翼
			return array('mediaUserID' => $user -> tyimid);
		} elseif ($tid = 'fbtid') { // Facebook
			return array('mediaUserID' => $user -> fbmid);
		} elseif ($tid = 'ttid') { // twitter
			return array('mediaUserID' => $user -> tmid);
		} 
	} 
	// 回复
	function get_childrenComments($comment_id) {
		global $wpdb;
		$comments = $wpdb -> get_results("SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, user_id FROM $wpdb->comments WHERE comment_parent = $comment_id AND comment_approved=1 AND comment_agent not like '%Denglu%'", "ARRAY_A");
		$ret = array();
		if ($comments) {
			foreach($comments as $comment) {
				if ($comment['user_id']) {
					if ($tid = get_usertid($comment['comment_author_email'], $comment['user_id'])) {
						$user = get_row_userinfo($comment['user_id'], $tid);
						if (is_array($user)) {
							$comment = array_merge($user, $comment);
						} 
					} 
				} 
				$ret[] = $comment;
			} 
		} 
		return $ret;
	} 
	function get_descendantComments($comment_id) {
		$ret = array();
		$children = get_childrenComments($comment_id);
		foreach ($children as $child) {
			$grand_children = get_descendantComments($child['comment_ID']);
			$ret = array_merge($grand_children, $ret);
		} 
		$ret = array_merge($ret, $children);
		return $ret;
	} 
	// 评论，包括回复
	function import_comments_to_denglu() {
		global $wpdb;
		$comments = $wpdb -> get_results("SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, user_id FROM $wpdb->comments WHERE comment_parent = 0 AND comment_approved=1 AND comment_agent not like '%Denglu%' LIMIT 10", "ARRAY_A");
		foreach($comments as $comment) {
			if ($comment['user_id']) {
				if ($tid = get_usertid($comment['comment_author_email'], $comment['user_id'])) {
					$user = get_row_userinfo($comment['user_id'], $tid);
					if (is_array($user)) {
						$comment = array_merge($user, $comment);
					} 
				} 
			} 
			$result[] = array_merge($comment, array('comment_post_url' => get_permalink($comment['comment_post_ID']), 'children' => get_descendantComments($comment['comment_ID'])));
		} 
		return $result;
	} 

	function wp_update_comment_agent($comment_ID, $cid = '') {
		global $wpdb;
		$comments = $wpdb -> get_row("SELECT comment_agent FROM $wpdb->comments WHERE comment_ID = {$comment_ID} AND comment_agent not like '%Denglu%'", ARRAY_A);
		if ($comments) {
			return wp_update_comment_key($comment_ID, 'comment_agent', trim(substr($comments['comment_agent'], 0, 200) . ' Denglu_' . $cid));
		}
	}
	// 导入评论
	function denglu_importComment() {
		@ini_set("max_execution_time", 300);
		$data = import_comments_to_denglu();
		// return var_dump($data);
		if ($data) {
			$wptm_basic = get_option('wptm_basic');
			$data = json_encode($data);
			class_exists('Denglu') or require(dirname(__FILE__) . "/class/Denglu.php");
			$api = new Denglu($wptm_basic['appid'], $wptm_basic['appkey'], 'utf-8');
			try {
				$comments = $api -> importComment($data);
			} 
			catch(DengluException $e) { // 获取异常后的处理办法(请自定义)
				// return false;
				wp_die($e -> geterrorDescription()); //返回错误信息
			} 
			// return var_dump($comments);
			if (is_array($comments)) {
				foreach ($comments as $comment) {
					if ($comment['id']) wp_update_comment_agent($comment['comment_ID'], $comment['id']);
					if (is_array($comment['children'])) {
						foreach ($comment['children'] as $children) {
							if ($children['id']) wp_update_comment_agent($children['comment_ID'], $children['id']);
						} 
					} 
				} 
				denglu_importComment();
			} 
		} 
	} 
}

/**
 * 最新评论 V2.3 (V2.4.3)
 */
if (!function_exists('denglu_recent_comments')) {
	// 获取最新评论
	function get_denglu_recent_comments($count = '') {
		$recentComments = get_option('denglu_recentComments');
		if ($recentComments['comments'] && time() - $recentComments['time'] < 300) {
			return $recentComments;
		} 
		global $wptm_basic;
		class_exists('Denglu') or require(dirname(__FILE__) . "/class/Denglu.php");
		$api = new Denglu($wptm_basic['appid'], $wptm_basic['appkey'], 'utf-8');
		try {
			$output = $api -> latestComment($count);
		} 
		catch(DengluException $e) { // 获取异常后的处理办法(请自定义)
			// wp_die($e -> geterrorDescription()); //返回错误信息
		} 
		if ($output && is_array($output)) {
			update_option('denglu_recentComments', array('comments' => $output, 'time' => time()));
			return array('comments' => $output);
		} elseif ($recentComments['comments']) {
			$recentComments['time'] = time();
			update_option('denglu_recentComments', $recentComments);
			return $recentComments;
		} 
	} 

	function denglu_recent_comments($comments) {
		if (is_array($comments)) {
			echo '<ul id="denglu_recentcomments">';
			foreach($comments as $comment) {
				echo "<li>" . $comment['name'] . ": <a href=\"{$comment['url']}\">" . $comment['content'] . "</a></li>";
			} 
			echo '</ul>';
		} 
	} 

	if ($wptm_comment['latest_comments']) {
		include_once(dirname(__FILE__) . '/comments-widgets.php'); // 最新评论 小工具
	} 
} 

/**
 * 1.评论保存到本地服务器
 * 2.评论状态同步到本地服务器。
 * 3.从灯鹭服务器导入到本地的评论被回复了，再把这条回复导入到灯鹭服务器 V2.3.3 (V2.4)
 * V2.3 (V2.4.3)
 */
if (!function_exists('dcToLocal')) {
	function get_weiboInfo($name) {
		$o = array('1' => array('g'),
			'2' => array('m'),
			'3' => array('s', 'stid', '@weibo.com', 'http://weibo.com/'),
			'4' => array('q', 'tqqid', '@t.qq.com', 'http://t.qq.com/'),
			'5' => array('sh', 'sohuid', '@t.sohu.com', 'http://t.sohu.com/u/'),
			'6' => array('n', 'neteaseid', '@t.163.com', 'http://t.163.com/'),
			'7' => array('r', 'renrenid', '@renren.com', 'http://www.renren.com/profile.do?id='),
			'8' => array('k', 'kaixinid', '@kaixin001.com', 'http://www.kaixin001.com/home/?uid='),
			'9' => array('d', 'dtid', '@douban.com', 'http://www.douban.com/people/'),
			'12' => array('y'),
			'13' => array('qq', 'qqid', '@qzone.qq.com'),
			'15' => array('ali'),
			'16' => array('tb'),
			'17' => array('ty', 'tytid', '@tianya.cn', 'http://my.tianya.cn/'),
			'19' => array('bd', 'baiduid', '@baidu.com'),
			'21' => array('wy'),
			'23' => array('guard360'),
			'26' => array('tyi'),
			'27' => array('fb', 'facebookid', '@facebook.com', 'http://www.facebook.com/profile.php?id='),
			'28' => array('t', 'twitterid', '@twitter.com', 'http://twitter.com/'),
			);
		return $o[$name];
	} 
	if ($wptm_comment['comment_avatar']) {
		if ($wptm_comment['comment_avatar'] == 1) {
			add_action('init', 'weibo_avatar_install');
			$wptm_comment['comment_avatar'] = 2;
			update_option('wptm_comment', $wptm_comment);
			// 创建头像数据库表
			function weibo_avatar_install() {
				global $wpdb;
				$table_name = "wp_comments_avatar";
				if ($wpdb -> get_var("show tables like '$table_name'") != $table_name) {
					$sql = "CREATE TABLE " . $table_name . " (
			`account` varchar(100) NULL,
			`mediaID` tinyint(3) NOT NULL,
			`avatar_url` varchar(255) NULL,
		  PRIMARY KEY (`account`),
		  KEY `mediaID` (`mediaID`)
		)ENGINE=MyISAM DEFAULT CHARSET=utf8;";

					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);
				} 
			} 
		} else {
			add_action('save_denglu_comment', 'save_user_head', 10, 4);
		    add_action('get_weibo_head', 'wp_get_weibo_head', 10, 4);
		}
        // 获取用户头像
		function get_avatar_url($account, $mediaID) {
			global $wpdb;
			return $wpdb -> get_var($wpdb -> prepare("SELECT avatar_url FROM wp_comments_avatar WHERE account = %s AND mediaID = %s", $account, $mediaID));
		} 

		function update_avatar_url($account, $mediaID, $avatar_url) {
			global $wpdb;
			$cur = get_avatar_url($account, $mediaID);

			if (!$cur) {
				$wpdb -> insert('wp_comments_avatar', compact('account', 'mediaID', 'avatar_url'));
			} elseif ($cur -> avatar_url != $avatar_url) {
				$wpdb -> update('wp_comments_avatar', compact('avatar_url'), compact('account', 'mediaID'));
			} else {
				return false;
			} 
			return true;
		} 
		// 保存用户头像
		function save_user_head($commentID, $comment, $weibo_uid, $user_id) {
			if (!$user_id && $comment['head'] && in_array($comment['mediaID'], array(4, 5, 7, 8, 19, 28))) {
				if ($weibo_uid) {
					if ($comment['mediaID'] == 4) {
						$path = explode('/', $comment['head']);
					    $comment['head'] = $path[4];
					} 
					update_avatar_url($weibo_uid, $comment['mediaID'], $comment['head']);
				} else { // baidu
					update_avatar_url($comment['uid'], $comment['mediaID'], $comment['head']);
				} 
			} 
		} 
    }
	// 获取评论列表
	function get_comments_by_denglu($cid) {
		global $wptm_basic;
		class_exists('Denglu') or require(dirname(__FILE__) . "/class/Denglu.php");
		$api = new Denglu($wptm_basic['appid'], $wptm_basic['appkey'], 'utf-8');
		try {
			$ret = $api -> getComments($cid);
		} 
		catch(DengluException $e) { // 获取异常后的处理办法(请自定义)
			// wp_die($e->geterrorDescription()); //返回错误信息
		} 
		if (is_array($ret)) {
			return $ret;
		} 
	} 
	// 评论状态
	function dcState($state) {
		$s = array('1', '0', 'spam', 'trash');
		return $s[$state];
	} 
	// 通过WordPress评论ID获取灯鹭cid
	function get_dengluCommentID($comment_ID) {
		global $wpdb;
		$ret = $wpdb -> get_var("SELECT comment_agent FROM $wpdb->comments WHERE comment_ID = {$comment_ID} AND comment_agent like '%Denglu_%' LIMIT 1");
		if ($ret) {
			return ltrim(strstr($ret, 'Denglu_'), 'Denglu_');
		} 
	}
	// 通过灯鹭cid得到WordPress评论ID
	function get_commentID($cid) {
		global $wpdb;
		return $wpdb -> get_var("SELECT comment_ID FROM $wpdb->comments WHERE comment_agent = 'Denglu_{$cid}' LIMIT 1");
	} 
	// 保存单条评论
	function save_dengluComment($comment, $parent = 0) {
		global $wpdb;
		if ($commentID = $comment['sourceID']) // 以前导入到灯鹭服务器记录的本地评论ID
			return $commentID;
		$cid = $comment['cid'];
		if ($ret = get_commentID($cid))
			return $ret;
		$weiboinfo = get_weiboInfo($comment['mediaID']);
		$mid = $weiboinfo[0] . 'mid';
		$id = $weiboinfo[1];
		if (empty($comment['email'])) {
			if (in_array($comment['mediaID'], array(3, 4, 5, 6, 7, 8, 9, 13, 17, 28))) {
				if ($comment['url']) {
					$weibo_uid = str_replace($weiboinfo[3], '', $comment['url']);
				} elseif ($id = 'qqid') {
					$path = explode('/', $comment['head']);
					$weibo_uid = $path[4] . '/' . $path[5];
				} 
				$user_id = (wp_connect_v1()) ? get_user_by_meta_value($id, $weibo_uid) : '';
			}
			$domain = ifab($weiboinfo[2], '@denglu.cc');
			$email = ($weibo_uid) ? $weibo_uid . $domain : $comment['uid'] . $domain;
		} else {
			//if ($id = 'facebookid' && $comment['url']) {
			//	$weibo_uid = str_replace($weiboinfo[3], '', $comment['url']);
			//}
			$email = $comment['email'];
		} 
		$commentdata = array('comment_post_ID' => $comment['postid'],
			'comment_author' => $comment['nick'],
			'comment_author_email' => $email,
			'comment_author_url' => $comment['url'],
			'comment_content' => $comment['content'],
			'comment_type' => '',
			'comment_parent' => $parent,
			'user_id' => ($user_id) ? $user_id : 0,
			'comment_author_IP' => $comment['ip'],
			'comment_agent' => 'Denglu_' . $cid,
			'comment_date' => $comment['date'],
			'comment_approved' => dcState($comment['state'])
			);
		$commentID = get_commentID($cid);
		if (!$commentID) {
			$commentID = wp_insert_comment($commentdata);
			do_action('save_denglu_comment', $commentID, $comment, $weibo_uid, $user_id);
		} 
		return $commentID;
	} 
	// 保存评论，包括父级评论
	function save_dengluComments($children, $comment) {
		if ($comment) {
			$comment_ID = save_dengluComment($comment); //父级
		} 
		$children_ID = save_dengluComment($children, $comment_ID);
	} 
	// 保存所有评论 V2.4
	function save_dcToLocal($denglu_last_id) {
		$cid = $denglu_last_id['cid'];
		$comments = get_comments_by_denglu($cid);
		if ($comments) {
			$number = count($comments) - 1;
			$last_cid = $comments[$number]['commentID'];
			update_option('denglu_last_id', array('cid' => $last_cid, 'time' => time()));
			foreach ($comments as $comment) {
				save_dengluComments(array('postid' => $comment['postid'], 'mediaID' => $comment['mediaID'], 'uid' => $comment['mediaUserID'], 'nick' => $comment['userName'], 'email' => $comment['userEmail'], 'url' => $comment['homepage'], 'head' => $comment['userImage'], 'cid' => $comment['commentID'], 'sourceID' => $comment['sourceID'], 'content' => $comment['content'], 'state' => $comment['state'], 'ip' => $comment['ip'], 'date' => $comment['createTime']), ($c = $comment['parent']) ? array('postid' => $c['postid'], 'mediaID' => $c['mediaID'], 'uid' => $c['mediaUserID'], 'nick' => $c['userName'], 'email' => $c['userEmail'], 'url' => $c['homepage'], 'head' => $c['userImage'], 'cid' => $c['commentID'], 'commentID' => $c['cid'], 'content' => $c['content'], 'state' => $c['state'], 'ip' => $c['ip'], 'date' => $c['createTime']):'');
			} 
			save_dcToLocal(array('cid' => $last_cid));
		} else {
			//$denglu_last_id['time'] = time();
			//update_option('denglu_last_id', $denglu_last_id);
			return true;
		} 
	} 
	// 评论状态与本地对接
	function dc_setCommentsStatus($cid, $status) {
		switch ($status) {
			case "0":
				wp_set_comment_status($cid, 'approve'); //以获准
				break;
			case "1":
				wp_set_comment_status($cid, 'hold'); //待审
				break;
			case "2":
				wp_set_comment_status($cid, 'spam'); //垃圾评论
				break;
			case "3":
				wp_set_comment_status($cid, 'trash'); //回收站
				break;
			case "4":
				wp_delete_comment($cid, true); //永久删除
				break;
			default:
		} 
	} 
	// 获取评论状态
	function get_commentState_by_denglu($time) {
		global $wptm_basic;
		class_exists('Denglu') or require(dirname(__FILE__) . "/class/Denglu.php");
		$api = new Denglu($wptm_basic['appid'], $wptm_basic['appkey'], 'utf-8');
		try {
			$ret = $api -> getCommentState($time);
		} 
		catch(DengluException $e) { // 获取异常后的处理办法(请自定义)
			// wp_die($e->geterrorDescription()); //返回错误信息
		} 
		if (is_array($ret)) {
			return $ret;
		} 
	} 
	// 保存评论状态
	function save_dcStateToLocal($comments = '', $time = '') {
		global $wpdb;
		if ($time) {
			$time = (int) ($time / 3600 + 1); // 转为小时，并延长一小时
		} 
		$commentState = array();
		$commentState = get_commentState_by_denglu($time);
		if ($commentState) {
			if ($comments) { // 首次不必更新状态
				$comment_diff = array_diff_assoc($commentState, $comments);
				if ($comment_diff) {
					foreach ($comment_diff as $cid => $state) {
						$ret = get_commentID($cid);
						if ($ret) {
							dc_setCommentsStatus($ret, $state);
						} 
					} 
				} 
			} 
			update_option('denglu_commentState', $commentState);
		} 
	} 
	// 删除重复的评论，保留comment_ID最小的一条
	function delete_same_comments($cid = '') {
		global $wpdb;
		if ($cid) {
			if ($commentid = get_commentID($cid)) {
				$sql = "SELECT comment_ID FROM $wpdb->comments b WHERE NOT EXISTS(SELECT a.comment_ID FROM (SELECT min(comment_ID) comment_ID FROM $wpdb->comments WHERE comment_ID > $commentid group by comment_content,comment_agent,comment_author_email) a WHERE a.comment_ID = b.comment_ID) AND comment_ID > $commentid";
			}
		}
		if (!$commentid) { // 3天内
			$sql = "SELECT comment_ID FROM $wpdb->comments b WHERE NOT EXISTS(SELECT a.comment_ID FROM (SELECT min(comment_ID) comment_ID FROM $wpdb->comments WHERE TO_DAYS(NOW()) - TO_DAYS(comment_date_gmt) < 3 group by comment_content,comment_agent,comment_author_email) a WHERE a.comment_ID = b.comment_ID) AND TO_DAYS(NOW()) - TO_DAYS(comment_date_gmt) < 3";
		}
		$comments = $wpdb -> get_results($sql, "ARRAY_A");
		if ($comments) {
			foreach($comments as $comment) {
				wp_delete_comment($comment['comment_ID'], true);
			} 
		} 
	}
    // 从灯鹭服务器导入到本地的评论被回复了，再把这条回复导入到灯鹭服务器 V2.4
	add_action('wp_insert_comment','denglu_importReplyComment', 10, 2);
	function denglu_importReplyComment($comment_id, $comment) {
		if ($comment -> comment_approved != 1 || $comment -> comment_type == 'trackback' || $comment -> comment_type == 'pingback' || $comment -> comment_parent == 0  || strpos($comment -> comment_agent, 'Denglu_') !== false) {
			return $comment_id;
		} 
		$get_dlCommentID = get_dengluCommentID($comment -> comment_parent);
		if ($get_dlCommentID) {
			$comment = array('comment_ID' => $comment -> comment_ID, 'comment_post_ID' => $comment -> comment_post_ID, 'comment_author' => $comment -> comment_author, 'comment_author_email' => $comment -> comment_author_email, 'comment_author_url' => $comment -> comment_author_url, 'comment_author_IP' => $comment -> comment_author_IP, 'comment_date' => $comment -> comment_date, 'comment_content' => $comment -> comment_content, 'comment_parent' => $comment -> comment_parent, 'user_id' => $comment -> user_id);
			if ($comment['user_id']) {
				if ($tid = get_usertid($comment['comment_author_email'], $comment['user_id'])) {
					$user = get_row_userinfo($comment['user_id'], $tid);
					if (is_array($user)) {
						$comment = array_merge($user, $comment);
					} 
				} 
			} 
			$data[] = array('cid' => $get_dlCommentID, 'children' => array($comment));
			$data = json_encode($data);
			class_exists('Denglu') or require(dirname(__FILE__) . "/class/Denglu.php");
			global $wptm_basic;
			$api = new Denglu($wptm_basic['appid'], $wptm_basic['appkey'], 'utf-8');
			try {
				$comments = $api -> importComment($data);
			} 
			catch(DengluException $e) { // 获取异常后的处理办法(请自定义)
			} 
			// return var_dump($comments);
			if (is_array($comments)) {
				foreach ($comments as $comment) {
					if (is_array($comment['children'])) {
						foreach ($comment['children'] as $children) {
							if ($children['id']) wp_update_comment_agent($children['comment_ID'], $children['id']);
						} 
					} 
				} 
			} 
		} 
	}
	// 同步评论到本地数据库 V2.4
	function dcToLocal() {
		global $timestart, $wptm_comment;
		if (!$wptm_comment)
			$wptm_comment = get_option('wptm_comment');
		$time = ($wptm_comment['time'] > 0) ? $wptm_comment['time'] * 60 : 300; // 5min
		$denglu_last_time = get_option('denglu_last_time'); //读取数据库
		$time_diff = $timestart - $denglu_last_time['time'];
		if (!$denglu_last_time || $time_diff > $time) {
			if (update_option('dcToLocal_lock', 'locked') || $time_diff > $time * 2 + 5) {
				$denglu_last_time['time'] = $timestart;
				$denglu_last_time['lock'] = $timestart;
				update_option('denglu_last_time', $denglu_last_time);
				$denglu_last_id = get_option('denglu_last_id'); //读取数据库
				if (save_dcToLocal($denglu_last_id)) { // 同步评论到本地服务器
					after_save_dcToLocal($denglu_last_time, $denglu_last_id);
				} 
			} 
			update_option('dcToLocal_lock', '');
		} elseif ($denglu_last_time['lock'] && $timestart - $denglu_last_time['lock'] > 60) { // 某些性能不高的服务器无法返回的处理办法
			after_save_dcToLocal($denglu_last_time, get_option('denglu_last_id'));
		} 
	} 
	// 本地化成功之后运行（删除重复的评论、同步评论状态到本地）
	function after_save_dcToLocal($denglu_last_time, $denglu_last_id) {
		global $timestart;
		if ($denglu_last_id['cid']) {
			$denglu_last_time['lock'] = '';
			update_option('denglu_last_time', $denglu_last_time);
			if (!$denglu_last_time['last_time']) { // 删除相同评论
				update_option('denglu_last_time', array('time' => $timestart, 'last_time' => $timestart, 'next_id' => $denglu_last_id['cid']));
				delete_same_comments();
			} else { 
				if ($timestart - $denglu_last_time['last_time'] > 6 * 60 * 60) { // 6小时
					update_option('denglu_last_time', array('time' => $timestart, 'last_time' => $timestart, 'last_id' => $denglu_last_time['next_id'], 'next_id' => $denglu_last_id['cid']));
				} 
				delete_same_comments($denglu_last_time['last_id']);
			} 
			$denglu_commentState = get_option('denglu_commentState'); //读取数据库
			$denglu_last_time = ($denglu_last_time['time']) ? $denglu_last_time['time'] : $denglu_last_id['time'];
			save_dcStateToLocal($denglu_commentState, $denglu_last_time); // 同步评论状态到本地服务器
		} 
	} 
	// 后台评论页面提示 V2.4
    function dengluComment_notice() {
		echo '<div class="updated" style="background:#f0f8ff; border:1px solid #addae6;"><p><strong>WordPress连接微博</strong> 插件的社会化评论功能（<a href="options-general.php?page=wp-connect#comment">评论设置</a>开启）会将每一条评论保存到本地数据库，同时在<a href="http://open.denglu.cc" target="_blank">灯鹭控制台</a>“评论管理”处进行删除/修改操作也会同步到本地数据库。</p><p>您在本页做的任何删除/修改等操作，不会对灯鹭评论框上的评论起作用，但是对一条评论进行回复时会同步到灯鹭评论框。</p></div>';
	}
    function dcToLocal_comment() {
		dcToLocal();
		add_action('admin_notices', 'dengluComment_notice');
	}
    // 触发动作 V2.4.3
	if (default_values('dcToLocal', 1, $wptm_comment)) {
		function local_recent_comments($number, $avatar = '') { // 调用本地的最新评论 v2.4.4
			$comments = get_comments(apply_filters('widget_comments_args', array('number' => $number, 'status' => 'approve', 'post_status' => 'publish')));
			echo '<ul id="denglu_recentcomments">';
			if (!$avatar) {
				foreach((array) $comments as $comment) {
					echo '<li>' . $comment -> comment_author . ': <a href="' . esc_url(get_comment_link($comment -> comment_ID)) . '">' . $comment -> comment_content . '</a></li>';
				} 
			} else {
				echo "<style type=\"text/css\" media=\"screen\">#denglu_recentcomments li{margin-top:5px;display:block}#denglu_recentcomments .avatar{display:inline;float:left;margin-right:8px;border-radius:3px 3px 3px 3px;}#denglu_recentcomments .rc-info, #denglu_recentcomments .rc-content{overflow:hidden;text-overflow:ellipsis;-o-text-overflow:ellipsis;white-space:nowrap;}</style>";
				foreach((array) $comments as $comment) {
					echo '<li>' . get_avatar($comment, 36) . '<div class="rc-info"><a href="' . esc_url(get_comment_link($comment -> comment_ID)) . '">' . $comment -> comment_author . '</a></div><div class="rc-content">' . $comment -> comment_content . '&nbsp;</div></li>';
				} 
			} 
			echo '</ul>';
		} 
		if (!is_admin()) {
			add_action('init', 'dcToLocal');
		} else {
			add_action('load-edit-comments.php', 'dcToLocal_comment');
		} 
	} 
} 

?>