$(function($) {
	var header = '<div class="navbar navbar-inverse navbar-fixed-top">'
      + '<div class="navbar-inner">'
        + '<div class="container">'
          + '<div class="nav-collapse collapse">'
            + '<ul class="nav">'
              + '<li>'
                + '<a href="/">首页</a>'
              + '</li>'
              + '<li>'
                + '<a data-bootstro-step="0" data-bootstro-placement="bottom" data-bootstro-title="商品管理页面。" class="bootstro" href="">商品管理</a>'
              + '</li>'
              + '<li>'
                + '<a data-bootstro-step="1" data-bootstro-placement="bottom" data-bootstro-title="订单管理页面。" class="bootstro" href="">订单管理</a>'
              + '</li>'
              + '<li>'
                + '<a data-bootstro-step="2" data-bootstro-placement="bottom" class="bootstro" href="#">店铺设置</a>'
              + '</li>'
              + '<li>'
                + '<a data-bootstro-step="3" data-bootstro-placement="bottom" class="bootstro" href="#">联系我们</a>'
              + '</li>'
            + '</ul>'
          + '</div>'
        + '</div>'
      + '</div>'
    + '</div>';
	
	$("body").prepend(header);
});


