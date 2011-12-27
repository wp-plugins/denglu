<?php
/*
Plugin Name: 灯鹭
Author: denglu
Author URI: http://denglu.cc/
Plugin URI: http://www.denglu.cc/source/wordpress.html
Description: <strong>灯鹭WordPress插件 已经跟 WordPress连接微博 插件合并，您可以直接在后台搜索新插件并安装，关键字: WP Connect ,安装 <a href="http://wordpress.org/extend/plugins/wp-connect/">WordPress连接微博</a> 后，到插件页面升级一下数据即可兼容新插件，然后您可以删除灯鹭V1.1插件，谢谢您的支持和谅解！</strong>
Version: 1.1
*/

if (function_exists('wp_nonce_url')) {
	function www_denglu_cc_warning() {
		$url = (is_multisite()) ? 'network/' : '';
		$url .= 'update.php?action=install-plugin&plugin=wp-connect';
		$deactivate_url = self_admin_url(wp_nonce_url($url, 'install-plugin_wp-connect'));
		echo '<div class="updated">';
		echo '<strong>灯鹭WordPress插件 已经跟 WordPress连接微博 插件合并，您可以直接 <a href="' . $deactivate_url . '">点击这里安装</a>，或者在后台搜索新插件并安装，关键字: WP Connect ,安装 <a href="http://wordpress.org/extend/plugins/wp-connect/" target="_blank">WordPress连接微博</a> 后，到插件页面升级一下数据即可兼容新插件，然后您可以删除灯鹭V1.1插件，谢谢您的支持和谅解！</strong>';
		echo '</div>';
	} 
	add_action('admin_notices', 'www_denglu_cc_warning');
} 

?>