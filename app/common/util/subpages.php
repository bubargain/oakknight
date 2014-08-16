<?php

namespace app\common\util;

class SubPages {
	var $m_nRows; // 每页显示的行数
	var $m_nCurRows; // 当前页能显示的行数
	var $m_nCount; // 总行数
	var $m_nCurPage; // 当前要显示的页数
	var $m_nPageCount; // 页面数量
	var $m_nMaxRow; // 当前页的最大行号
	var $m_nMinRow; // 当前页的最小行号
	var $m_bFirst; // 首页按钮是否有效
	var $m_bPrev; // 上一页按钮是否有效
	var $m_bNext; // 下一页按钮是否有效
	var $m_bLast; // 尾页按钮是否有效
	var $m_nLength;
	var $m_bStatic = false; // 是否静态化发布
	var $m_sUrl = ""; // m_sUrl地址头
	var $m_sClick = ""; // 当Ajax调用时，a href标记响应的是onclick事件
	var $m_sPageName = "page"; // page标签，用来控制m_sUrl页。比如说xxx.php?page=2中的参数Page
	var $m_sNextName = '>'; // 下一页
	var $m_sPrevName = '<'; // 上一页
	var $m_sFirstName = 'First'; // 首页
	var $m_sLastPage = 'Last'; // 尾页
	var $m_sPrevBar = '<<'; // 上一分页条
	var $m_sNextBar = '>>'; // 下一分页条
	var $m_sNumLeft = ''; // 页数左边的格式化字符例如：[1] [2] [3] 的左边是[
	var $m_sNumRight = ''; // 页数右边的格式化字符例如：[1] [2] [3] 的左边是]
	function __construct($sUrl, $nRows, $nCount, $nCurPage = 1, $click = "") {
		$nCurPage = intval ( $nCurPage );
		if ($nCurPage <= "0") {
			$nCurPage = 1;
		}
		if ($sUrl == null || $sUrl == "") {
			echo "sUrl不能为空!";
			$this->SetEmpty ();
			return;
		}
		$this->m_sUrl = $sUrl;
		
		$this->m_sClick = $click;
		
		$this->m_nCount = intval ( $nCount );
		if ($this->m_nCount < 1) {
			$this->SetEmpty ();
			return;
		}
		$this->m_nRows = intval ( $nRows );
		if (! $nCurPage) {
			$this->m_nCurPage = 1;
		} else {
			$this->m_nCurPage = intval ( $nCurPage );
		}
		$this->m_nPageCount = ceil ( $this->m_nCount / $this->m_nRows );
		if ($this->m_nCurPage < 1) {
			$this->m_nCurPage = 1;
		}
		if ($this->m_nCurPage > $this->m_nPageCount) {
			$this->m_nCurPage = $this->m_nPageCount;
		}
		// 然后计算当前页的最小行号和最大行号
		$this->m_nMinRow = $this->m_nRows * ($this->m_nCurPage - 1) + 1;
		$this->m_nMaxRow = $this->m_nMinRow + $this->m_nRows - 1;
		if ($this->m_nMaxRow > $this->m_nCount) {
			$this->m_nMaxRow = $this->m_nCount;
		}
		$this->m_nCurRows = $this->m_nMaxRow - $this->m_nMinRow + 1;
		// 再判断4个按钮是否有效
		$this->m_bFirst = false; // 首页按钮是否有效
		$this->m_bPrev = false; // 上一页按钮是否有效
		$this->m_bNext = false; // 下一页按钮是否有效
		$this->m_bLast = false; // 尾页按钮是否有效
		if ($this->m_nPageCount > 1) {
			if ($this->m_nCurPage == 1) {
				$this->m_bNext = true;
				$this->m_bLast = true;
			} else if ($this->m_nCurPage == $this->m_nPageCount) {
				$this->m_bFirst = true;
				$this->m_bPrev = true;
			} else {
				$this->m_bFirst = true;
				$this->m_bPrev = true;
				$this->m_bNext = true;
				$this->m_bLast = true;
			}
		}
		$this->m_nLength = 10;
	}
	
	// 析构函数
	function __destruct() {
		unset( $this->m_nRows );
		unset( $this->m_nCount );
		unset( $this->m_nCurPage );
		unset( $this->m_nPageCount );
		unset( $this->m_nMaxRow );
		unset( $this->m_nMinRow );
		unset( $this->m_bFirst );
		unset( $this->m_bPrev );
		unset( $this->m_bNext );
		unset( $this->m_bLast );
	}
	
	function SetEmpty() {
		$this->m_nCurRows = 0;
		$this->m_nCurPage = 1;
		$this->m_nPageCount = 1;
		$this->m_nMaxRow = 0;
		$this->m_nMinRow = 0;
		$this->m_bFirst = false;
		$this->m_bLast = false;
		$this->m_bNext = false;
		$this->m_bPrev = false;
		$this->m_nLength = 10;
	}
	function GetLimit() {
		$sRet = "";
		if ($this->m_nCount > 0) {
			$sRet = " " . ($this->m_nMinRow - 1) . "," . $this->m_nRows;
		} else {
			$sRet = " 0,0";
		}
		return $sRet;
	}
	
	// 显示第1 - 21 条记录，共2172条 << 上一页 1 2 3 4 5 6 7 8 9 10 下一页 >> 到第页 ，共104页
	function GetPageHtml() {
		if ($this->m_nPageCount > 1) {
			$pageNumber = "显示第" . $this->m_nMinRow . " - " . $this->m_nMaxRow . " 条记录，共" . $this->m_nCount . "条\n";
			if ($this->m_nCurPage == 0) {
				$pageNumber .= "<<<B>上一页</B>" . $this->m_sNumLeft . "<b>&nbsp;1&nbsp;</b>" . $this->m_sNumRight;
			} else if ($this->m_nCurPage > 1) {
				// 第一页 //First page
				$pageNumber .= "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=1>&nbsp;<<&nbsp;</A> </B> \n";
				// 前一页 //Previous page
				$pageNumber .= "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . ($this->m_nCurPage - 1) . ">上一页</A> </B> \n";
			}
			// The start number is the first number of all pages which show on
			// the current page.
			$startNumber = intval ( $this->m_nCurPage / $this->m_nLength ) * $this->m_nLength;
			// Prev N page //交界处
			if ($this->m_nCurPage >= $this->m_nLength) {
				$pageNumber .= $this->m_sNumLeft . "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . ($startNumber - 1) . ">&nbsp;" . ($startNumber - 1) . "&nbsp;</A></B>" . $this->m_sNumRight . "... \n";
			}
			$leftPageNumber = 0;
			for($i = $startNumber; $i <= $this->m_nPageCount; $i ++) {
				if ($i == 0)
					continue;
				if ($i - $startNumber < $this->m_nLength) {
					if ($i == $this->m_nCurPage) {
						$pageNumber .= $this->m_sNumLeft . "<b>&nbsp;$i&nbsp;</b>" . $this->m_sNumRight . " \n";
					} else {
						$pageNumber .= $this->m_sNumLeft . "<A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . $i . ">&nbsp;" . $i . "&nbsp;</A>" . $this->m_sNumRight . " \n";
					}
				} else {
					$leftPageNumber = $this->m_nPageCount - $i + 1;
					break;
				}
			}
			// 显示下一个分页列表
			if ($leftPageNumber >= 1) {
				$pageNumber .= "..." . $this->m_sNumLeft . "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . ($startNumber + $this->m_nLength) . ">&nbsp;" . ($startNumber + $this->m_nLength) . "&nbsp;</A></B>" . $this->m_sNumRight . " \n";
			}
			if ($this->m_nCurPage != $this->m_nPageCount) {
				// Next page
				$pageNumber .= "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . ($this->m_nCurPage + 1) . ">下一页</A> </B> \n";
				// Last page
				$pageNumber .= "<B><A HREF=" . $this->m_sUrl . "&" . $this->m_sPageName . "=" . $this->m_nPageCount . ">&nbsp;>>&nbsp;</A> </B> \n";
			}
			// 到第页 ，共123页
			$pageNumber .= "  到第<input name='nowpage' style='text-align:center; padding-left:4px; padding-right:4px;' id='nowpage' type='text' size='" . strlen ( $this->m_nPageCount ) . "' value='" . $this->m_nCurPage . "' \n";
			// $pageNumber.=" onKeyPress='alert(this.value);' >";
			$pageNumber .= " onKeyPress=\"if((window.event?event.keyCode:event.which)==13){window.location='" . $this->m_sUrl . "&page=' + this.value;}\"> \n";
			$pageNumber .= "页，共 \n";
			$pageNumber .= $this->m_nPageCount;
			$pageNumber .= "页 \n";
			$this->pageNumber = $pageNumber;
			return $this->pageNumber;
		} else {
			$pageNumber = "显示第" . $this->m_nMinRow . " - " . $this->m_nMaxRow . " 条记录，共" . $this->m_nCount . "条\n";
			$pageNumber .= "<B>&nbsp;" . $this->m_nPageCount . "</B> \n";
			$pageNumber .= "页，共 \n";
			$pageNumber .= $this->m_nPageCount;
			$pageNumber .= "页 \n";
			$this->pageNumber = $pageNumber;
			return $this->pageNumber;
		}
	}
}