<?php
/*
Plugin Name: Denglu评论
Author: 水脉烟香
Author URI: http://www.smyx.net/
Plugin URI: http://wordpress.org/extend/plugins/denglu/
Description: 灯鹭提供的社会化评论框，使用新浪微博、QQ、人人、360、Google、Twitter、Facebook等20家合作网站帐号登录并评论。
Version: 1.6.6
*/

$wptm_basic = get_option('wptm_basic');
$wptm_comment = get_option('wptm_comment');

add_action('admin_notices', 'denglu_comments_warning');
function denglu_comments_warning() {
	if (version_compare(WP_CONNECT_VERSION, '2.0', '>')) {
		echo '<div class="updated">';
		echo "<p><strong>检测到您正在使用“WordPress连接微博”插件，请直接使用“WordPress连接微博”的评论设置功能，无需另外安装 灯鹭社会化评论 插件，谢谢您的支持！</strong></p>";
		echo '</div>';
	}
}

add_action('admin_menu', 'denglu_comments_add_page');
function denglu_comments_add_page() {
	if (version_compare(WP_CONNECT_VERSION, '2.0', '<')) {
		add_options_page('Denglu评论', 'Denglu评论', 'manage_options', 'denglu', 'denglu_comments_do_page');
		global $wptm_basic, $wptm_comment;
		if ($wptm_basic['appid'] && $wptm_basic['appkey'] && current_user_can('manage_options')) {
			add_object_page('灯鹭评论管理', '灯鹭评论管理', 'moderate_comments', 'denglu_admin', 'denglu_ocomment5',  plugins_url('denglu') .'/images/logo_small.gif');
		} 
	} 
} 

if (!function_exists('default_values')) { // 设置默认值
	function default_values($key, $vaule, $array) {
		if (!is_array($array)) {
			return true;
		} else {
			if ($array[$key] == $vaule || !array_key_exists($key, $array)) {
				return true;
		    }
		}
	} 
}

if (!function_exists('denglu_comments') && $wptm_comment['enable_comment'] && $wptm_basic['appid'] && $wptm_basic['appkey']) {
	define("MY_PLUGIN_URL" , plugins_url('denglu'));
	include_once(dirname(__FILE__) . '/denglu.func.php'); // 灯鹭自定义函数
	if (!$wptm_comment['manual']) {
		add_filter('comments_template', 'denglu_comments');
		function denglu_comments($file) {
			global $post;
			if (comments_open()) {
				return dirname(__FILE__) . '/comments.php';
			} 
		} 
	} 
}

// 设置
function denglu_comments_do_page() {
	if (isset($_POST['basic_options'])) {
		update_option("wptm_basic", array('appid'=>trim($_POST['appid']), 'appkey'=>trim($_POST['appkey'])));
	} elseif (isset($_POST['comment_options'])) {
		update_option("wptm_comment", array('enable_comment' => trim($_POST['enable_comment']), 'manual' => trim($_POST['manual']), 'comments_open' => trim($_POST['comments_open']), 'dcToLocal' => trim($_POST['dcToLocal']), 'comment_avatar' => trim($_POST['comment_avatar']), 'time' => trim($_POST['time']), 'latest_comments' => trim($_POST['latest_comments']), 'enable_seo' => trim($_POST['enable_seo'])));
	} elseif (isset($_POST['comment_delete'])) {
		delete_option("wptm_basic");
		delete_option("wptm_comment");
	} elseif (isset($_POST['importComment'])) { // 评论导入到灯鹭
		if (function_exists('denglu_importComment')) {
			denglu_importComment();
			echo '<div class="updated"><p><strong>评论导入成功！</strong></p></div>';
		} else {
			echo '<div class="updated"><p><strong>请先开启社会化评论，并填写APP ID和APP Key</strong></p></div>';
		}
	}
	$wptm_basic = get_option('wptm_basic');
	$wptm_comment = get_option('wptm_comment');
?>
<div class="wrap">
  <h2>Denglu评论</h2>
      <p style="color:green"><strong>使用前，请先在 <a href="http://open.denglu.cc/codes/getCodes.jsp?siteType=3" target="_blank">灯鹭控制台</a> 注册帐号，并创建站点，之后在下面填写APP ID 和 APP Key ，评论的相关设置及管理，请在灯鹭控制台操作。<br />如果您还需要使用合作网站登录及同步功能，请直接下载 <a href="http://www.denglu.cc/source/wordpress2.0.html" target="_blank">WordPress连接微博</a> V2插件（集成了社会化评论），谢谢您的支持！</strong></p>
      <form method="post" action="">
        <?php wp_nonce_field('basic-options');?>
        <h3>站点设置</h3>
	    <table class="form-table">
		    <tr>
			    <td width="25%" valign="top">APP ID: </td>
			    <td><label><input type="text" name="appid" size="32" value="<?php echo $wptm_basic['appid'];?>" /></label> (必填)</td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">APP Key: </td>
			    <td><label><input type="text" name="appkey" size="32" value="<?php echo $wptm_basic['appkey'];?>" /></label> (必填)</td>
		    </tr>
        </table>
        <p class="submit">
          <input type="submit" name="basic_options" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
      </form>
      <form method="post" action="">
        <?php wp_nonce_field('comment-options');?>
        <h3>评论设置</h3>
	    <table class="form-table">
            <tr>
                <td width="25%" valign="top">功能开启</td>
                <td><label><input name="enable_comment" type="checkbox" value="1" <?php if($wptm_comment['enable_comment']) echo "checked "; ?>> 开启“社会化评论”功能</label></td>
            </tr>
		    <tr>
			    <td width="25%" valign="top">自定义函数</td>
			    <td><label><input name="manual" type="checkbox" value="1" <?php if($wptm_comment['manual']) echo "checked "; ?> /> 自己在主题添加函数（不推荐使用）</label><code>&lt;?php dengluComments();?&gt;</code></td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">单篇文章评论开关</td>
			    <td><label><input name="comments_open" type="checkbox" value="1" <?php if(default_values('comments_open', 1, $wptm_comment)) echo "checked ";?> /> 继承WordPress已有的评论开关，即当某篇文章关闭评论时，也不使用社会化评论功能。</label></td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">同步评论到本地</td>
			    <td><label><input name="dcToLocal" type="checkbox" value="1" <?php if(default_values('dcToLocal', 1, $wptm_comment)) echo "checked ";?> /> 灯鹭评论内容保存一份在WordPress本地评论数据库</label> <label>(每 <input name="time" type="text" size="1" maxlength="3" value="<?php echo ($wptm_comment['time']) ? $wptm_comment['time'] : '5'; ?>" onkeyup="value=value.replace(/[^0-9]/g,'')" /> 分钟更新一次)</label></td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">保存评论者头像到本地</td>
			    <td><label><input name="comment_avatar" type="checkbox" value="<?php echo (!$wptm_comment['comment_avatar']) ? 1 : 2; ?>"<?php if($wptm_comment['comment_avatar']) echo "checked "; ?> /> 会创建一个新的数据库表(wp_comments_avatar)来保存</label></td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">最新评论</td>
			    <td><label><input name="latest_comments" type="checkbox" value="1" <?php if($wptm_comment['latest_comments']) echo "checked ";?> /> 开启侧边栏“最新评论”功能 (开启后到<a href="widgets.php">小工具</a>拖拽激活)</label></td>
		    </tr>
		    <tr>
			    <td width="25%" valign="top">SEO支持</td>
			    <td><label><input name="enable_seo" type="checkbox" value="1" <?php if(!$wptm_comment || $wptm_comment['enable_seo']) echo "checked "; ?> /> 评论支持SEO，让搜索引擎能爬到评论数据</label></td>
		    </tr>
        </table>
        <p class="submit">
          <input type="submit" name="comment_options" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
      </form>
	  <h3>导入导出</h3>
	  <p>导入数据到灯鹭平台。导入后，您原有的网站评论将在“Denglu评论”的评论框内显示。</p>
	  <p><form method="post" action=""><span class="submit"><input type="submit" name="importComment" value="评论导入" /> (可能需要一些时间，请耐心等待！)</span></form></p>
	  <h3>卸载插件</h3>
	  <p>假如您要使用“WordPress连接微博” V2.x插件，可以不必卸载本插件，现有的设置不变！</p>
      <form method="post" action="">
	    <?php wp_nonce_field('comment-delete');?>
		<span class="submit"><input type="submit" name="comment_delete" value="卸载插件" onclick="return confirm('您确定要卸载社会化评论？')" /></span>
	  </form>
</div>
<?php
}