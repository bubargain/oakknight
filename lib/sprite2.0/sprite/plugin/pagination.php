<?php
/**
 * @date 2013-3-15
 */
namespace sprite\plugin;

class PagiNation {
    
//function to return the pagination string
    public static function getPagination($targetpage = "/", $page = 1, $limit = 15, $totalitems, $adjacents = 1, $pagestring = "p") {
        $pagestring = (strpos($targetpage, '?')>0)? "&$pagestring=":'?$pagestring=';
        $page = ($page<1)? 1:$page;
        //other vars
        $prev = $page - 1;                                  //previous page is page - 1
        $next = $page + 1;                                  //next page is page + 1
        $lastpage = ceil($totalitems / $limit);             //lastpage is = total items / items per page, rounded up.
        $lpm1 = $lastpage - 1;                              //last page minus 1
    
        /*
            Now we apply our rules and draw the pagination object.
        We're actually saving the code to a variable in case we want to draw it more than once.
        */
        $pagination = "";
        if ($lastpage > 1) {
            $pagination .= "<div class='pagination'";
            if(isset($margin) || isset($padding)) {
                $pagination .= " style='";
                if($margin)
                    $pagination .= "margin: $margin;";
                if($padding)
                    $pagination .= "padding: $padding;";
                $pagination .= "'";
            }
            $pagination .= ">";
    
            //previous button
            if ($page > 1)
                $pagination .= "<a class='pg_prev' href='$targetpage$pagestring$prev'>上一页</a>";
            else
                $pagination .= "<span class='disabled'>上一页</span>";
    
            //pages
            if ($lastpage < 8 + ($adjacents * 2)) {   //not enough pages to bother breaking it up
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<span class='current'>$counter</span>";
                    else
                        $pagination .= "<a href='" . $targetpage . $pagestring . $counter . "'>$counter</a>";
                }
            } else if($lastpage >= 7 + ($adjacents * 2)) {  //enough pages to hide some
                //close to beginning; only hide later pages
                if($page <= 2 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                        if ($counter == $page)
                            $pagination .= "<span class='current'>$counter</span>";
                        else
                            $pagination .= "<a href='" . $targetpage . $pagestring . $counter . "'>$counter</a>";
                    }
                    $pagination .= "<span class='elipses'>...</span>";
                    $pagination .= "<a href='" . $targetpage . $pagestring . $lpm1 . "'>$lpm1</a>";
                    $pagination .= "<a href='" . $targetpage . $pagestring . $lastpage . "'>$lastpage</a>";
                } elseif ($page > $lastpage - (2 + ($adjacents * 2))) {
                	$pagination .= "<a href='" . $targetpage . $pagestring . "1'>1</a>";
                	$pagination .= "<a href='" . $targetpage . $pagestring . "2'>2</a>";
                	$pagination .= "<span class='elipses'>...</span>";
                	for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++) {
	                	if ($counter == $page)
	                		$pagination .= "<span class='current'>$counter</span>";
	                	else
	                		$pagination .= "<a href='" . $targetpage . $pagestring . $counter . "'>$counter</a>";
	                }
                }  else { //in middle; hide some front and some back
                    $pagination .= "<a href='" . $targetpage . $pagestring . "1'>1</a>";
                    $pagination .= "<a href='" . $targetpage . $pagestring . "2'>2</a>";
                    $pagination .= "<span class='elipses'>...</span>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<span class='current'>$counter</span>";
                        else
                            $pagination .= "<a href='" . $targetpage . $pagestring . $counter . "'>$counter</a>";
                    }
                    $pagination .= "<span class='elipses'>...</span>";
                    $pagination .= "<a href='" . $targetpage . $pagestring . $lpm1 . "'>$lpm1</a>";
                    $pagination .= "<a href='" . $targetpage . $pagestring . $lastpage . "'>$lastpage</a>";
                }
                //close to end; only hide early pages                
            }
    
            //next button
            if ($page < $lastpage)
                $pagination .= "<a href='" . $targetpage . $pagestring . $next . "' class='pg_next'>下一页</a>";
            else
                $pagination .= "<span class='disabled'>下一页</span>";
            $pagination .= "</div>\n";
        }
    
        return $pagination;
	}
}



/*
 echo PagiNation::getPagination('/t.php?a=', $_GET['p'], 10, 200, 2);
 
 <style>    
    div.pagination {
        padding: 3px;
        margin: 3px;
    }
    
    div.pagination a {
        padding: 2px 5px 2px 5px;
        margin: 2px;
        border: 1px solid #AAAADD;
        zoom: 100%;
        text-decoration: none; 
        color: #000099;
    }
    div.pagination a:hover, div.pagination a:active {
        border: 1px solid #000099;

        color: #000;
    }
    div.pagination span.current {
        padding: 2px 5px 2px 5px;
        margin: 2px;
        border: 1px solid #000099;
        
        * zoom: 100%; 
        
        font-weight: bold;
        background-color: #000099;
        color: #FFF;
    }
    div.pagination span.disabled {
        padding: 2px 5px 2px 5px;
        margin: 2px;
        border: 1px solid #EEE;
        
        * zoom: 100%;
        
        color: #DDD;
    }
    
    * span.elipsis {zoom:100%}
</style> 
<script type="text/javascript">
//键盘翻页支持
//jquery is needed
document.body.onkeydown = window.onkeydown = function(event){
    if (event.keyCode==37 && $('.pg_prev').attr('href')!=undefined) window.location.href=$('.pg_prev').attr('href');
    if (event.keyCode==39 && $('.pg_next').attr('href')!=undefined) window.location.href=$('.pg_next').attr('href');
}
</script>
 */
