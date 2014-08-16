<?php
namespace sprite\plugin;

/**
 * fan.yoka 用的分页条
 * @author liweiwei
 *
 */
class PageLink {
	
	/**
	 * 生成分页模版html
	 * @param string $url 分页基本url
	 * @param unknown_type $pid 当前页码
	 * @param unknown_type $pageSize 每页条数
	 * @param unknown_type $total 数据总条数
	 * @return string 输出的分页条字符串
	 */
	public function getPageLink($url, $pid, $pageSize, $total) {
		$url = (strpos($url, '?')>0)? $url.'&':$url.'?';
		$pages = ceil($total/$pageSize);
		if ($pid<1)
			$pid = 1;
		if ($pid>$pages)
			$pid = $pages;
		
		$relPages = ($pages<$this->pages)? $pages:$this->pages;
		if ($relPages<=1)
			return '';
		$harf = ceil($this->pages-1)/2;
		$p2 = $pid-$harf-1;
		$p3 = $pages-$this->pages;
		for ($i=2; $i<$this->pages; $i++) {
			$n1[$i] = $i;
			$n2[$i] = $p2+$i;
			$n3[$i] = $p3+$i;
		}
		
		if (in_array($pid+1, $n1))
			$n = $n1;
		else if (in_array($pid-1, $n3))
			$n = $n3;
		else 
			$n = $n2;
		
		
		if ($n[2]!=2)
			$n[2] = $this->tpl_more;
		
		if (isset($n[$relPages-1]) && $n[$relPages-1] != $pages-1)
			$n[$relPages-1] = $this->tpl_more;
		
		$n[0] = ($pid==1)? $this->tpl_pre_disable:str_replace('@i@', $pid-1, $this->tpl_pre);
		$n[1] = 1;
		$n[$relPages] = $pages;
		$n[$relPages+1] = ($pid==$pages)? $this->tpl_next_disable:str_replace('@i@', $pid+1, $this->tpl_next);
 
		for($i=0; $i<=$relPages+1; $i++) {
			$items[$i] = $n[$i];
			
			if (!is_string($items[$i])) {
				if ($pid==$n[$i]) {
					$items[$i] = str_replace('@i@', $items[$i], $this->tpl_item_current);
				} else {
					$items[$i] = str_replace('@i@', $items[$i], $this->tpl_item);
				}
			}
			$items[$i] = str_replace('@url@', $url, $items[$i]);
			
		}
		
		return $this->page_start.implode('', $items).$this->page_end;
	}
	
	/**
	 * 设置fan用的模版
	 */
	public function setFanTpl() {
		$this->pages = 9; //只能是单数
		$this->page_start = '<div class="pagebox"><div>';
		$this->page_end = '</div></div>';
		$this->tpl_pre = '<a hasusercardevent="1" class="prepage" href="@url@p=@i@">上一页</a><i></i>';
		$this->tpl_pre_disable = '<span class="prepage"></span><i></i>';
		$this->tpl_next = '<i></i><a hasusercardevent="1" class="nextpage" href="@url@p=@i@">下一页</a>';
		$this->tpl_next_disable = '<i></i><span class="nextpage"></span>';
		$this->tpl_item = '<a hasusercardevent="1" href="@url@p=@i@">@i@</a>';
		$this->tpl_item_current = '<span class="pager_cur">@i@</span>';
		$this->tpl_more = '<i>...</i>';
	}
	
}

/*
$a = new PageLink();
$a->setFanTpl();
echo $a->getPageLink('/', 8, 10, 150);
*/
