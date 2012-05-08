<?php
//$_SESSION['wp_url_back'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
$wptm_basic = get_option('wptm_basic');
$wptm_comment = get_option('wptm_comment');
if (is_object($post)) {
	$media_url = preg_match_media_url($post -> post_content, $post -> ID);
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
}?>
    _dl_comment_widget.show(param);
</script>
<?php
// 搜索引擎爬虫
if ($wptm_comment['enable_seo'] && preg_match("/(Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla)/i", $_SERVER['HTTP_USER_AGENT']) && have_comments()) : ?>
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
<?php endif; ?>