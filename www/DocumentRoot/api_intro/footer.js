$(function($) {
	var footer = '<footer class="footer">'
		+ '<div class="container">'
		+ '	<ul>'
        + '  <li><a href="/">首页</a></li>'
        + '  <li><a href="/index.php?app=help">关于我们</a></li>'		  
        + '  <li><a href="/index.php?app=help">联系我们</a></li>'
		+ '  <li><a target="_blank" href="http://comment.yoka.com/guest/?m=guest">留言反馈</a></li>'
        + '</ul>'
		+ '<p>&copy; 2013 YMALL COPY RIGHT │京ICP证070512号│京公网安备1101050667</p>'
      + '</div>'
    + '</footer>';
	
	$("body").append(footer);
});