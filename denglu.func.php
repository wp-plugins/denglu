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
	function http_ssl($url) {
		$arrURL = parse_url($url);
		$r['ssl'] = $arrURL['scheme'] == 'https' || $arrURL['scheme'] == 'ssl';
		$is_ssl = isset($r['ssl']) && $r['ssl'];
		if ($is_ssl && !extension_loaded('openssl'))
			return wp_die('您的主机不支持openssl');
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
					return wp_die('没有可以完成请求的 HTTP 传输器');
				} 
			} 
		} 
		$http = new $class;
		$response = $http -> request($url, $params);
		if (!is_array($response)) {
			$errors = $response -> errors;
			$error = $errors['http_request_failed'][0];
			if (!$error)
				$error = $errors['http_failure'][0];
			if ($error == "couldn't connect to host") {
				return;
			} 
			wp_die('出错了: ' . $error . '<br /><br />可能是您的主机不支持。');
		} 
		return $response['body'];
	} 
} 

/**
 * 评论函数 v2.3
 */
if (!function_exists('dengluComments')) {
	function dengluComments() {
		global $post;
	    $_SESSION['wp_url_bind'] = '';
	    $_SESSION['wp_url_back'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	    $wptm_basic = get_option('wptm_basic');
	    $wptm_comment = get_option('wptm_comment');
	    if (empty($wptm_comment['comments_open']) || (!empty($wptm_comment['comments_open']) && comments_open())) {
			$wptm_connect = get_option('wptm_connect');
			if (is_object($post)) {
				$media_url = wp_multi_media_url($post -> post_content, $post -> ID);
			} 
?>
<script type='text/javascript' charset='utf-8' src='http://open.denglu.cc/connect/commentcode?appid=<?php echo $wptm_basic['appid'];?>&v=1.0.1'></script>
<script type="text/javascript" charset='utf-8'>
    var param = {};
    param.title = "<?php echo urlencode(get_the_title());?>"; // 文章标题
    param.postid = "<?php the_ID();?>"; // 文章ID
<?php
	if ($media_url) { // 是否有视频、图片
    echo "param.image = \"" . $media_url[0] ."\";\n"; // 需要同步的图片地址
    echo "param.video = \"" . $media_url[1] ."\";\n"; // 需要同步的视频地址，支持土豆优酷等
    }
	if ($wptm_connect['enable_connect']) { // 是否开启了社会化登录
	echo (!is_user_logged_in()) ? "param.login = false;\n":"param.login = true;\n"; // 是否已经登录
	echo "param.exit = \"".urlencode(wp_logout_url(get_permalink()))."\";\n"; // 退出链接
}?>
    _dl_comment_widget.show(param);
</script>
<?php
	} 
	// 搜索引擎爬虫
		if ($wptm_comment['enable_seo'] && preg_match("/(Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla)/i", $_SERVER['HTTP_USER_AGENT']) && have_comments()) { ?>
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
<?php }}}

/**
 * 评论导入 v2.1.2
 */
if (!function_exists('denglu_importComment')) {
	// 通过Userid或者email获取tid
	function get_usertid($email, $user_id = '') {
		if ($last_login = get_user_meta($user_id, 'last_login', true)) {
			return $last_login;
		} 
		$mail = strstr($email, '@');
		if ($mail == '@t.sina.com.cn') {
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
		} elseif (get_user_meta($user_id, 'qqtid', true)) {
			return 'qqtid';
		} elseif (get_user_meta($user_id, 'tbtid', true)) {
			return 'tbtid';
		}
	} 

	function get_row_userinfo($uid, $tid) {
		$user = get_userdata($uid);
		if ($tid == 'gtid') {
			return ($user -> gmid) ? array('mediaUserID' => $user -> gmid) : '';
		} elseif ($tid == 'mtid') {
			return ($user -> mmid) ? array('mediaUserID' => $user -> mmid) : (($user -> msnid) ? array('mediaID' => '2', 'mediaUID' => $user -> msnid, 'email' => $user -> user_email) : '');
		} elseif ($tid == 'stid') {
			return ($user -> smid) ? array('mediaUserID' => $user -> smid) : (($user -> stid) ? array('mediaID' => '3', 'mediaUID' => $user -> stid, 'profileImageUrl' => 'http://tp2.sinaimg.cn/' . $user -> stid . '/50/0/1', 'oauth_token' => ifac($user -> login_sina[0], $user -> tdata['tid'] == 'stid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_sina[1], $user -> tdata['tid'] == 'stid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid == 'qtid') {
			return ($user -> qmid) ? array('mediaUserID' => $user -> qmid) : (($user -> qtid) ? array('mediaID' => '4', 'mediaUID' => ifab($user -> tqqid , $user -> user_login), 'profileImageUrl' => $user -> qtid, 'oauth_token' => ifac($user -> login_qq[0], $user -> tdata['tid'] == 'qtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_qq[1], $user -> tdata['tid'] == 'qtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid == 'shtid') {
			return ($user -> shmid) ? array('mediaUserID' => $user -> shmid) : (($user -> shtid) ? array('mediaID' => '5', 'mediaUID' => ifab($user -> sohuid , $user -> user_login), 'profileImageUrl' => $user -> shtid, 'oauth_token' => ifac($user -> login_sohu[0], $user -> tdata['tid'] == 'shtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_sohu[1], $user -> tdata['tid'] == 'shtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid == 'ntid') {
			return ($user -> nmid) ? array('mediaUserID' => $user -> nmid) : ((is_numeric($user -> neteaseid) && $user -> neteaseid < 0) ? array('mediaID' => '6', 'mediaUID' => $user -> neteaseid, 'profileImageUrl' => $user -> ntid, 'oauth_token' => ifac($user -> login_netease[0], $user -> tdata['tid'] == 'ntid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_netease[1], $user -> tdata['tid'] == 'ntid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid == 'rtid') {
			return ($user -> rmid) ? array('mediaUserID' => $user -> rmid) : (($user -> rtid) ? array('mediaID' => '7', 'mediaUID' => ifab($user -> renrenid , $user -> user_login), 'profileImageUrl' => $user -> rtid):'');
		} elseif ($tid == 'ktid') {
			return ($user -> kmid) ? array('mediaUserID' => $user -> kmid) : (($user -> ktid) ? array('mediaID' => '8', 'mediaUID' => ifab($user -> kaxinid , $user -> user_login), 'profileImageUrl' => $user -> ktid):'');
		} elseif ($tid == 'dtid') {
			return ($user -> dmid) ? array('mediaUserID' => $user -> dmid) : (($user -> dtid) ? array('mediaID' => '9', 'mediaUID' => $user -> dtid, 'profileImageUrl' => 'http://t.douban.com/icon/u' . $user -> dtid . '-1.jpg', 'oauth_token' => ifac($user -> login_douban[0], $user -> tdata['tid'] == 'dtid', $user -> tdata['oauth_token']), 'oauth_token_secret' => ifac($user -> login_douban[1], $user -> tdata['tid'] == 'dtid', $user -> tdata['oauth_token_secret'])) : '');
		} elseif ($tid == 'ytid') {
			return ($user -> ymid) ? array('mediaUserID' => $user -> ymid) : '';
		} elseif ($tid == 'qqtid') {
			return ($user -> qqmid) ? array('mediaUserID' => $user -> qqmid) : (($user -> qqid) ? array('mediaID' => '13', 'mediaUID' => $user -> qqid, 'profileImageUrl' => $user -> qqtid, 'oauth_token' => $user -> qqid):'');
		} elseif ($tid == 'tbtid') {
			return ($user -> tbmid) ? array('mediaUserID' => $user -> tbmid) : (($user -> tbtid && is_numeric($user -> user_login)) ? array('mediaID' => '16', 'mediaUID' => $user -> user_login, 'email' => $user -> user_email, 'profileImageUrl' => $user -> tbtid):'');
		} elseif ($tid == 'tytid') {
			return ($user -> tymid) ? array('mediaUserID' => $user -> tymid) : (($user -> tytid) ? array('mediaID' => '17', 'mediaUID' => $user -> tytid, 'profileImageUrl' => 'http://tx.tianyaui.com/logo/small/' . $user -> tytid, 'oauth_token' => $user -> login_tianya[0], 'oauth_token_secret' => $user -> login_tianya[1]):'');
		} elseif ($tid == 'alitid') {
			return ($user -> alimid) ? array('mediaUserID' => $user -> alimid) : '';
		} elseif ($tid == 'bdtid') {
			return ($user -> bdmid) ? array('mediaUserID' => $user -> bdmid) : (($user -> bdtid) ? array('mediaID' => '19', 'mediaUID' => ifab($user -> baiduid , $user -> user_login), 'profileImageUrl' => 'http://himg.bdimg.com/sys/portraitn/item/' . $user -> bdtid . '.jpg'):'');
		} elseif ($tid == 'wytid') { // 网易通行证
			return ($user -> wymid) ? array('mediaUserID' => $user -> wymid) : '';
		} elseif ($tid == 'guard360tid') { // 360
			return ($user -> guard360mid) ? array('mediaUserID' => $user -> guard360mid) : '';
		} elseif ($tid == 'tyitid') { // 天翼
			return ($user -> tyimid) ? array('mediaUserID' => $user -> tyimid) : '';
		} elseif ($tid == 'fbtid') { // Facebook
			return ($user -> fbmid) ? array('mediaUserID' => $user -> fbmid) : '';
		} elseif ($tid == 'ttid') { // twitter
			return ($user -> tmid) ? array('mediaUserID' => $user -> tmid) : '';
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
		$comment_agent = $wpdb -> get_var("SELECT comment_agent FROM $wpdb->comments WHERE comment_ID = {$comment_ID} AND comment_agent not like '%Denglu%'");
		if ($comment_agent) {
			return wp_update_comment_key($comment_ID, 'comment_agent', trim($comment_agent . ' Denglu_' . $cid));
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
 * WordPress连接微博 自定义函数
 */
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
// 匹配视频,图片
if (!function_exists('preg_match_media_url')) {
	function preg_match_media_url($content, $post_ID = '') {
		preg_match_all('/<embed[^>]+src=[\"\']{1}(([^\"\'\s]+)\.swf)[\"\']{1}[^>]+>/isU', $content, $video);
		preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"].*>/isU', $content, $image);
		$v_sum = count($video[1]);
		if ($v_sum > 0) {
			$v = $video[1][0];
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

/**
 * 最新评论 v2.3
 */
if (!function_exists('denglu_recent_comments')) {
	// 获取最新评论
	function get_denglu_recent_comments($count = '') {
		$recentComments = get_option('denglu_recentComments');
		if ($recentComments['comments'] && time() - $recentComments['time'] > 300) {
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
			$recentComments['time'] = time() + 200;
			update_option('denglu_recentComments', $recentComments);
			return $recentComments;
		} 
	} 
	// 设置cookies
	function denglu_recent_comments_cookie() {
		global $wptm_comment;
		if (!is_admin()) {
			if ($wptm_comment['latest_comments'] && used_widget('wp-connect-comment-widget') && !$_COOKIE["denglu_recent_comments"]) {
				if ($comments = get_denglu_recent_comments()) {
					setcookie("denglu_recent_comments", json_encode($comments['comments']), time() + 300); //缓存5分钟
				} 
			} 
		} 
	} 
	add_action('init', 'denglu_recent_comments_cookie', 0);
	function denglu_recent_comments($comments) {
		if (is_array($comments)) {
			echo '<ul id="denglu_recentcomments">';
			foreach($comments as $comment) {
				echo "<li>" . $comment['name'] . ": <a href=\"{$comment['url']}\">" . $comment['content'] . "...</a></li>";
			} 
			echo '</ul>';
		} 
	} 

	if (!function_exists('used_widget')) {
		function used_widget($widget) {
			$vaule = get_option('widget_' . $widget);
			if (is_array($vaule) && count($vaule) > 1) {
				return true;
			} 
		} 
	} 

	if ($wptm_comment['latest_comments']) {
		include_once(dirname(__FILE__) . '/comments-widgets.php'); // 最新评论 小工具
	} 
}

/**
 * 1.评论保存到本地服务器
 * 2.评论状态同步到本地服务器。
 * 3.从灯鹭服务器导入到本地的评论被回复了，再把这条回复导入到灯鹭服务器 V2.3.3
 * add_V2.3, edit_V2.3.3
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
			'13' => array('qq'),
			'15' => array('ali'),
			'16' => array('tb'),
			'17' => array('ty', 'tytid', '@tianya.cn', 'http://my.tianya.cn/'),
			'19' => array('bd'),
			'21' => array('wy'),
			'23' => array('guard360'),
			'26' => array('tyi'),
			'27' => array('fb'),
			'28' => array('t', 'ttid', '@twitter.com', 'http://twitter.com/'),
			);
		return $o[$name];
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
	// 通过灯鹭cid得到WordPress评论ID
	function get_commentID($cid) {
		global $wpdb;
		return $wpdb -> get_var("SELECT comment_ID FROM $wpdb->comments WHERE comment_agent = 'Denglu_{$cid}' LIMIT 1");
	} 
	// 判断是否安装了 WordPress连接微博 V1
	function wp_connect_v1() {
		if (defined('WP_CONNECT_VERSION') && version_compare(WP_CONNECT_VERSION, '2.0', '<')) {
			return true;
		} 
	} 
	$wp_connect_v1 = wp_connect_v1(); 
	// 保存单条评论
	function save_dengluComment($comment, $parent = 0) {
		global $wpdb, $wp_connect_v1;
		if ($commentID = $comment['sourceID']) // 以前导入到灯鹭服务器记录的本地评论ID
			return $commentID;
		$cid = $comment['cid'];
		if ($ret = get_commentID($cid))
			return $ret;
		$weiboinfo = get_weiboInfo($comment['mediaID']);
		$mid = $weiboinfo[0] . 'mid';
		$id = $weiboinfo[1];
		if (empty($comment['email'])) {
			if (in_array($comment['mediaID'], array(3, 4, 5, 6, 7, 8, 9, 17)) && $comment['url']) {
				$weibo_uid = str_replace($weiboinfo[3], '', $comment['url']);
			} 
			$email = ($weiboinfo[2]) ? $weibo_uid . $weiboinfo[2] : $comment['uid'] . '@denglu.cc';
			$user_id = ($wp_connect_v1) ? get_user_by_meta_value($id, $weibo_uid) : '';
		} else {
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
			'comment_approved' => dcState($comment['state']),
			);
		$commentID = get_commentID($cid);
		if (!$commentID) {
			$commentID = wp_insert_comment($commentdata);
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
	// 保存所有评论
	function save_dcToLocal($denglu_last_id) {
		$cid = $denglu_last_id['cid'];
		$comments = get_comments_by_denglu($cid);
		if ($comments) {
			$number = count($comments) - 1;
			$last_cid = $comments[$number]['commentID'];
			update_option('denglu_last_id', array('cid' => $last_cid, 'time' => time()));
			foreach ($comments as $comment) {
				save_dengluComments(array('postid' => $comment['postid'], 'mediaID' => $comment['mediaID'], 'uid' => $comment['mediaUserID'], 'nick' => $comment['userName'], 'email' => $comment['userEmail'], 'url' => $comment['homepage'], 'cid' => $comment['commentID'], 'sourceID' => $comment['sourceID'], 'content' => $comment['content'], 'state' => $comment['state'], 'ip' => $comment['ip'], 'date' => $comment['createTime']), ($c = $comment['parent']) ? array('postid' => $c['postid'], 'mediaID' => $c['mediaID'], 'uid' => $c['mediaUserID'], 'nick' => $c['userName'], 'email' => $c['userEmail'], 'url' => $c['homepage'], 'cid' => $c['commentID'], 'commentID' => $c['cid'], 'content' => $c['content'], 'state' => $c['state'], 'ip' => $c['ip'], 'date' => $c['createTime']):'');
			} 
			save_dcToLocal(array('cid' => $last_cid));
		} else {
			$denglu_last_id['time'] = time();
			update_option('denglu_last_id', $denglu_last_id);
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
	// 删除3天内重复的评论，保留comment_ID最小的一条
	function delete_same_comments() {
		global $wpdb;
		$comments = $wpdb -> get_results("SELECT comment_ID FROM $wpdb->comments b WHERE NOT EXISTS(SELECT a.comment_ID FROM (SELECT min(comment_ID) comment_ID FROM $wpdb->comments WHERE TO_DAYS(NOW()) - TO_DAYS(comment_date_gmt) < 3 group by comment_content,comment_agent,comment_author_email) a WHERE a.comment_ID = b.comment_ID) AND TO_DAYS(NOW()) - TO_DAYS(comment_date_gmt) < 3", "ARRAY_A");
		if ($comments) {
			foreach($comments as $comment) {
				wp_delete_comment($comment['comment_ID'], true);
			} 
		} 
	} 
	// 通过WordPress评论ID获取灯鹭评论ID
	function get_dengluCommentID($comment_ID) {
		global $wpdb;
		$ret = $wpdb -> get_var("SELECT comment_agent FROM $wpdb->comments WHERE comment_ID = {$comment_ID} AND comment_agent like '%Denglu_%' LIMIT 1");
		if ($ret) {
			return ltrim(strstr($ret, 'Denglu_'), 'Denglu_');
		} 
	} 
	// 在WP后台评论处回复，并且父级为灯鹭评论ID
	function get_replyComments() {
		global $wpdb;
		$comments = $wpdb -> get_results("SELECT comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_parent, user_id FROM $wpdb->comments WHERE TO_DAYS(NOW()) - TO_DAYS(comment_date_gmt) < 3 AND comment_parent > 0 AND comment_approved=1 AND comment_agent not like '%Denglu%' LIMIT 20", "ARRAY_A");
		$ret = array();
		if ($comments) {
			foreach($comments as $comment) {
				$get_dlCommentID = get_dengluCommentID($comment['comment_parent']);
				if ($get_dlCommentID) {
					if ($comment['user_id']) {
						if ($tid = get_usertid($comment['comment_author_email'], $comment['user_id'])) {
							$user = get_row_userinfo($comment['user_id'], $tid);
							if (is_array($user)) {
								$comment = array_merge($user, $comment);
							} 
						} 
					} 
					$result[] = array('cid' => $get_dlCommentID, 'children' => array($comment));
				} 
			} 
			return $result;
		} 
	} 
	// 从灯鹭服务器导入到本地的评论被回复了，再把这条回复导入到灯鹭服务器 V2.3.3
	function denglu_importReplyComment() {
		@ini_set("max_execution_time", 300);
		$data = get_replyComments();
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
					if (is_array($comment['children'])) {
						foreach ($comment['children'] as $children) {
							if ($children['id']) wp_update_comment_agent($children['comment_ID'], $children['id']);
						} 
					} 
				} 
				denglu_importReplyComment();
			}
		} 
	} 
	// 触发动作
	function dcToLocal() {
		global $wptm_comment;
		$denglu_last_id = get_option('denglu_last_id'); //读取数据库
		$denglu_commentState = get_option('denglu_commentState'); //读取数据库 
		//if (!$denglu_last_id['time'] || time() - $denglu_last_id['time'] > 300) { // 5min
			save_dcToLocal($denglu_last_id); // 同步评论到本地服务器
			save_dcStateToLocal($denglu_commentState, $denglu_last_id['time']); // 同步评论状态到本地服务器
			delete_same_comments(); // 删除相同评论
		    denglu_importReplyComment(); // 从灯鹭服务器导入到本地的评论被回复了，再把这条回复导入到灯鹭服务器
		//} 
	} 
	if (default_values('dcToLocal', 1, $wptm_comment)) {
		add_action('init', 'dcToLocal');
	} 
} 
?>