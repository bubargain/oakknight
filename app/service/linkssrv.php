<?php 
namespace app\service;

/**
 * 
 * 友情链接查询
 * @author daniel
 *
 */
class LinksSrv extends BaseSrv{
	/**
	 * 
	 * search all friendly links
	 */
	public function searchAll($number)
	{
		try {
			$rnumber = $number ? $number : 3;
			$sql ="select * from ym_links order by `sort` desc limit $rnumber";
			$data= \app\dao\LinksDao::getSlaveInstance()->getPdo()->getRows($sql);
			for($i=0;$i<count($data);$i++)
			{
				$data[$i]['img'] = CDN_YMALL .$data[$i]['img'];
			}
			return $data;
		} catch (Exception $e) {
			throw new \Exception('SQL query error',200011);
		}
	}
}