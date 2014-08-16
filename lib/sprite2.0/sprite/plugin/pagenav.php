<?php
namespace sprite\plugin;

/**
 * @author liweiwei
 * 分页
 *
 */
class PageNav {
	
	/**
	 * 分页条
	 * @param string $pageurl
	 * @param int $currentpage
	 * @param int $pagesize
	 * @param int $totalrecords
	 * @return Ambigous <string, unknown>
	 */
	public static function link($pageurl, $currentpage, $pagesize, $totalrecords) {
		return self::getNavLink(self::getPageNavTemplate($pageurl), $currentpage, $pagesize, $totalrecords);
	}
	
	/**
	 * 指定分页条模版
	 * @param string $template
	 * @param int $currentpage
	 * @param int $pagesize
	 * @param int $totalrecords
	 * @return string|unknown
	 */
	public static function getNavLink($template, $currentpage, $pagesize, $totalrecords) {
		if (($pagesize <= 0) || ($totalrecords <= 0)) {
			return '';
		}
	
		//计算总页数
		$totalpages = ceil($totalrecords / $pagesize);
		if ($totalpages <= 1) return '';
	
		//更正当前页范围
		if ($currentpage > $totalpages) $currentpage = $totalpages;
		if ($currentpage < 1) $currentpage = 1;
	
		//计算前后页
		$prevpage = $currentpage - 1;
		$nextpage = $currentpage + 1;
	
		//处理静态数据
		$t1 = array("'##TOTALPAGES##'i", "'##TOTALRECORDS##'i");
		$t2 = array($totalpages, $totalrecords);
		$template = preg_replace($t1, $t2, $template);
	
		//处理前一页
		if ($prevpage > 0) {
			$pr = null;
			while (preg_match("/\\{##PREVPAGELINK:[^\\}]*\\}/i", $template, $pr) > 0) {
				$temp = preg_replace("'##PAGE##'i", $prevpage, $pr[0]);
				$temp = preg_replace("/\\{##PREVPAGELINK:([^\\}]*)\\}/i","\\1", $temp);
				$template = str_replace($pr[0], $temp, $template);
			}
		} else {
			$template = preg_replace("/\\{##PREVPAGELINK:[^\\}]*\\}/i", "", $template);
		}
	
		//处理下一页
		if ($nextpage <= $totalpages) {
			while (preg_match("/\\{##NEXTPAGELINK:[^\\}]*\\}/i", $template, $pr) > 0) {
				$temp = preg_replace("'##PAGE##'i", $nextpage, $pr[0]);
				$temp = preg_replace("/\\{##NEXTPAGELINK:([^\\}]*)\\}/i","\\1", $temp);
				$template = str_replace($pr[0], $temp, $template);
			}
		} else {
			$template = preg_replace("/\\{##NEXTPAGELINK:[^\\}]*\\}/i", "", $template);
		}
	
		//处理首页
		if ($currentpage != 1) {
			while (preg_match("/\\{##FIRSTPAGELINK:[^\\}]*\\}/i", $template, $pr) > 0) {
				$temp = preg_replace("'##PAGE##'i", 1, $pr[0]);
				$temp = preg_replace("/\\{##FIRSTPAGELINK:([^\\}]*)\\}/i","\\1", $temp);
				$template = str_replace($pr[0], $temp, $template);
			}
		} else {
			$template = preg_replace("/\\{##FIRSTPAGELINK:[^\\}]*\\}/i", "", $template);
		}
	
		//处理最后页
		if ($currentpage != $totalpages) {
			$pr = null;
			while (preg_match("/\\{##LASTPAGELINK:[^\\}]*\\}/i", $template, $pr) > 0) {
				$temp = preg_replace("'##PAGE##'i", $totalpages, $pr[0]);
				$temp = preg_replace("/\\{##LASTPAGELINK:([^\\}]*)\\}/i","\\1", $temp);
				$template = str_replace($pr[0], $temp, $template);
			}
		} else {
			$template = preg_replace("/\\{##LASTPAGELINK:[^\\}]*\\}/i", "", $template);
		}
	
		//处理页条
		while (preg_match("/(\\{##PAGELINK:(\\d+):(\\d+):(\\d+):([^:]*):([^\\}]*)\\})/i", $template, $pr) > 0) {
			//第一块结束页
			$r2 = $pr[2];
			//第二块起始页
			$r3 = $currentpage - ceil(($pr[3] - 1) / 2);
			//第二块结束页
			$r4 = $currentpage + floor(($pr[3] - 1) / 2);
			//第三块起始页
			$r5 = $totalpages - $pr[4] + 1;
	
			//修正第二块起始结束位置(当前页处于起始或结束区域内时)
			if ($r3 <= 0) {
				$r4 -= ($r3 - 1);
				$r3 = 1;
			}
			if ($r4 > $totalpages) {
				$r3 -= ($r4 - $totalpages);
				$r4 = $totalpages;
			}
	
			$temp = '';
			$i = 0;
			while ($i < $totalpages) {
				$i++;
				if ((($i >= 1) && ($i <= $r2)) || (($i >= $r3) && ($i <= $r4)) || (($i >= $r5) && ($i <= $totalpages))) {
					//显示页码
					if ($i == $currentpage) {
						$pr2 = null;
						preg_match("/\\{##CURRENTPAGELINK:[^\\}]*\\}/i", $template, $pr2);
						$temp .= preg_replace("/\\{##CURRENTPAGELINK:([^\\}]*)\\}/i","\\1", preg_replace("'##PAGE##'i", $currentpage, $pr2[0]));
					} else {
						$temp .= preg_replace("'##PAGE##'i", $i, $pr[6]);
					}
				} elseif (($i > $r2) && ($i < $r3)) {
					//跳过第一段至第二段, 显示...
					if  ($r2 > 0) {
						$temp .= '<strong>...</strong>';
					}
					$i = $r3 - 1;
				} elseif (($i > $r4) && ($i < $r5)) {
					//跳过第二段至第三段, 显示...
					if ($r5 <= $totalpages) {
						$temp .= '<strong>...</strong>';
					}
					$i = $r5 - 1;
				}
			}
			$template = preg_replace("/\\{##CURRENTPAGELINK:[^\\}]*\\}/i", "", $template);
			$template = str_replace($pr[0], $temp, $template);
		}
	
		$template = preg_replace("/##PAGE##/i", $currentpage, $template);
		return $template;
	}

	
	public static function getPageNavTemplate($pageurl, $n1 = 2, $n2 = 8, $n3 = 1, $forward = true, $postfix = '') {
		$temp  = '<div id="pg">';
		$temp .= '{##PrevPageLink:<a href="'.$pageurl.'##page##'.$postfix.'" id="pg-prev">上一页</a>}&nbsp;';
		$temp .= '{##CurrentPageLink:<strong>##page##</strong>}';
		$temp .= '{##PageLink:'."$n1:$n2:$n3:".'##page##:<a href="'.$pageurl.'##page##'.$postfix.'">##page##</a>}&nbsp;';
		$temp .= '&nbsp;{##NextPageLink:<a href="'.$pageurl.'##page##'.$postfix.'" id="pg-next">下一页</a>}';
		//$temp .= '&nbsp;<a class="p_text">共&nbsp;##totalpages##&nbsp;页</a>&nbsp;';
		if ($forward && false) {
			$temp .= '<a class="p_text">转到</a>';
			$temp .= '<input size="2" type="text" name="custompage" onkeydown="if(event.keyCode==13) {window.location=\''.$pageurl.'\'+this.value+\''.$postfix.'\'; return false;}" />';
			$temp .= '<button onclick="window.location=\''.$pageurl.'\'+document.getElementsByName(\'custompage\').item(0).value+\''.$postfix.'\'; return false;">GO</button>';
		}
		$temp .= '</div>';
		return $temp;
	}
	
	//手机分页模板
	public static function getPageWapTemplate($pageurl, $n1 = 2, $n2 = 5, $n3 = 1, $forward = true, $postfix = ''){
		$temp  = '<div class="p_bar">';
		$temp .= '{##PrevPageLink:<a href="'.$pageurl.'##page##'.$postfix.'" class="p_num">上一页</a>}&nbsp;';
		$temp .= '&nbsp;<a class="p_text">第&nbsp;##page##&nbsp;页/共&nbsp;##totalpages##&nbsp;页</a>&nbsp;';
		$temp .= '&nbsp;{##NextPageLink:<a href="'.$pageurl.'##page##'.$postfix.'" class="p_num">下一页</a>}';
		$temp .= '</div>';
		return $temp;
	}
	
}
?>
