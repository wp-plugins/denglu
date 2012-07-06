=== Denglu评论 ===
Contributors: smyx
Donate link: http://www.denglu.cc/source/denglu-comments.html
Tags: wp connect,twitter,qq,sina,taobao,msn,tianya,baidu,netease,sohu,digu,douban,baidu,fanfou,renjian,zuosa,follow5,renren,kaixin001,wbto,google,yahoo,connect,WordPress连接微博,腾讯微博,新浪微博,搜狐微博,网易微博,人人网,开心网,人间网,豆瓣,天涯,百度,淘宝网,嘀咕,饭否,做啥,微博通,登录,登陆,连接,同步,qq机器人,gtalk机器人,灯鹭,社会化评论,Denglu,Denglu评论,评论,Denglu Comment,disqus,pinglunla,uyan,youyan,duoshuo,widget,sina,tencent,qq,qzone,Share
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.6.6

灯鹭提供的社会化评论框，使用新浪微博、QQ、人人、360、Google、Twitter、Facebook等20家合作网站帐号登录并评论。

== Description ==

[WordPress连接微博](http://wordpress.org/extend/plugins/wp-connect/) —— 集成了Denglu评论（还包括同步文章到微博、合作网站帐号登录WordPress等实用功能）

= 详细描述 =

1、支持使用20家合作网站帐号登录评论框并发表评论。 （支持QQ空间、腾讯微博、新浪微博、搜狐微博、网易微博、人人网、开心网、豆瓣、淘宝网、支付宝、百度、天涯、MSN、Google、Yahoo、网易通行证、Twitter、Facebook、360、天翼189等帐号登录。）

2、同步评论到微博/SNS。 （同步到新浪微博、腾讯微博、QQ空间、网易微博、搜狐微博、人人网，天涯微博、MSN ）

3、评论数据会保存一份在WordPress本地数据库，不必担心评论丢失。

4、灯鹭控制台"评论管理"页面的评论状态（待审核、垃圾评论、回收站、永久删除）也会同步到本地数据库。

5、评论支持SE0。

6、支持网站原有评论导入到灯鹭云端服务器，并在社会化评论框显示。

7、支持调用最新评论（小工具）

8、从新浪微博抓取回来的评论同步到本地时，评论者可以使用新浪微博头像。点击头像链接还能进入TA的微博主页。

= 产品介绍 =

1、完善的评论框及评论管理、评论自定义设置，减少您的开发成本。

2、提供丰富的数据分析功能，让你随时掌握网站动态。

3、精准的垃圾评论过滤，展示优质的评论内容，提高网站的整体质量。

4、跟微博、SNS紧密结合，集成登录、评论、一键分享，用户同步的评论可以抓取回来显示在您的网站上，为网站带来人气、回流。

5、支持自定义评论模板，方便您根据自身的网站风格设计漂亮的评论界面。

>**技术支持**

>客服QQ：2499106838、1130488327、1390654016

>QQ交流群：77434617  联系电话：15110089672

>新浪微博：[ @水脉烟香](http://weibo.com/smyx) 、[ @灯鹭](http://weibo.com/idenglu) 

>技术论坛：[ http://bbs.denglu.cc/forum-27-1.html ](http://bbs.denglu.cc/forum-27-1.html) 官方网站：[ http://www.denglu.cc ](http://www.denglu.cc)

== Installation ==

1. Upload the `denglu-comment` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the settings using "WP Connect" under Settings panel.

下载 社会化评论 插件，上传denglu-comment目录及其文件到 "/wp-content/plugins/" 插件目录，在后台管理中激活插件，到设置页面开启功能并设置等.

== Screenshots ==

1. 登录社会化评论 后台界面

== Changelog ==

= 1.6.6 =

修复一处回复重复导入的BUG

去掉灯鹭管理平台，改成“灯鹭评论管理”。

= 1.6.5 =

新增：保存评论者头像到本地，会创建一个新的数据库表(wp_comments_avatar)来保存（评论设置开启），可以在后台评论页面、pinterest主题或者最新评论显示头像。

新增：在WordPress后台增加灯鹭管理平台，方便您的管理操作。

优化：对评论数据本地化进行优化，减少服务器压力。

优化：对大部分代码进行重写，效率更高。

= 1.6.4 =

修正了部分函数不支持导致插件不能正常使用的错误。

= 1.6.3 =

新增：灯鹭评论内容保存一份在WordPress本地评论数据库，新增更新时间控制，最少1分钟。（评论设置）

修改：继承WordPress已有的评论开关，即当某篇文章关闭评论时，也不使用社会化评论功能，但是会显示以前的评论。

修正：网站原有评论导入到灯鹭评论框时会出现的bug。

修正：评论自定义函数加载无效的bug。

= 1.6.0 =

新增：从新浪微博抓取回来的评论同步到本地时，评论者可以使用新浪微博头像。点击头像链接还能进入TA的微博主页。

优化部分代码

= 1.5.0 =

新增：从灯鹭服务器导到本地的评论被回复了，可以再把这条回复导入到灯鹭服务器。

= 1.4.0 =
*2012/04/09

新增：评论数据会保存一份在WordPress本地数据库，不必担心评论丢失。

新增：灯鹭控制台"评论管理"页面的评论状态（待审核、垃圾评论、回收站、永久删除）也会同步到本地数据库。

新增：评论支持SE0。

删除：灯鹭评论数调用，因为加入评论数据同步，不再需要浪费网络资源，直接调用本地评论数即可。

= 1.3.0 =
*2012/03/05
优化：文章评论数和最新评论的获取方式，显示速度更快。

= 1.2.0 =
*2012/03/01
新增文章评论数和最新评论（小工具拖拽）功能。

= 1.1.0 =
*2012/02/22
增加评论导入功能。

= 1.0.0 =
*2012/01/16
初始版本

