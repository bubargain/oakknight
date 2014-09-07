<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */
/**
 * @author Daniel Ma
 * @time  2014-08-16
 * @desc
 */

namespace app\service;

//简单版搜索yinqing

class SearchSrv extends BaseSrv {
	
	public function search ($params, $sort = 'default',$page=1,$skip=10)
	{
		$limit= ($page-1)*skip .','.$skip;
		$sql= "select * from ym_goods left join ym_goods_statistics on ym_goods.goods_id = ym_goods_statistics.goods_id where status=12";
		//$list = \app\dao\GoodsDao::getSlaveInstance ()->getList($params,$limit);
		$list = \app\dao\GoodsDao::getSlaveInstance ()->getpdo()->getRows($sql);
		//var_dump($list);die();
		return array('count'=>count($list), 'list'=>$list);
	}
	
}

//原始的搜索引擎

class SearchSrvBAK extends BaseSrv {
    const TIME_OUT = 5;
    const SEARCH_URL = 'http://10.0.1.136:8081/ymallsearch/ymall_apps';

    const SEARCH_FIELDS = 'id,goods_name,brand_name,brand_id,cate_id,cate_name,tags,default_thumb,default_image,cate_id_1,cate_id_2,price,market_price,stock,sale_type,if_codpay,wishes,views,orders,sales,weight';
    const SEARCH_Provider_URL = '/select/?facet=on&facet.field=brand_name_auto&facet.limit=200&facet.field=brand_id&fl=brand_id,cate_id_1,brand_name&wt=json&indent=on&rows=0';
    /**
     * @param $info 商品的信息
     * @param array $file_ids 对应的文件上传id
     * @throws \Exception
     */
    public function search($params, $sort = 'default', $page = 1, $skip = 20) {
        try{
            if($page < 1)
                $page = 1;

            $limit = ($page - 1) * $skip;

            $sort = self::getSort($sort);

            $r = self::doSearch($params, $sort, $limit, $skip);

            if($r) {
                $r =json_decode($r);

                if($r->response) {
                    //result count
                    $goods_count = $r->response->numFound;


                    $goodsSrv = new \app\service\GoodsSrv();
                    $type_arr = $goodsSrv->getSaleType();

                    //read result goods_data
                    if($goods_count) {
                        foreach($r->response->docs as $row) {
                            $list[] = array(
                                'goods_id'=> (string)$row->id,
                                'goods_name'=> $row->goods_name,
                                'brand_name'=> $row->brand_name,
                                'cate_name'=> $row->cate_name,
                                'sale_type'=> $row->sale_type,
                                'sale_type_info'=> $row->sale_type ? $type_arr[$row->sale_type] : array(),
                                'tags'=> $row->tags ? $row->tags[0] : null,
                                'price'=> (float)$row->price,
                                'market_price'=> (float)$row->market_price,
                                'stock'=> (int)$row->stock,
                                'wishes'=> (int)$row->wishes,
                                'weight'=> (int)$row->weight,
                                'default_thumb'=> preg_match('/^http:\/\//', $row->default_thumb) ? $row->default_thumb : CDN_YMALL . $row->default_thumb,
                                'default_image'=> preg_match('/^http:\/\//', $row->default_image) ? $row->default_image : CDN_YMALL . $row->default_image,
                            );
                        }
                    }
                }
            }
            return array('count'=>$goods_count, 'list'=>$list);
        }
        catch(\Exception $e) { throw $e; }
    }

    private function getSort($key) {
        $sortArr = array(
            'default'=>'weight desc ',
            'score'=>'score desc ',
        );
        return isset($sortArr[$key]) ? $sortArr[$key] : $sortArr['default'];
    }

    private function doSearch($params, $sort, $limit, $skip) {
        $qfs = array();

        if(isset($params['cate_id']) && intval($params['cate_id']) > 0) {
            $cate_id = intval($params['cate_id']);
            $qfs[] = "(cate_id:$cate_id OR cate_id_1:$cate_id OR cate_id_2:$cate_id)";
        }

        if(isset($params['cate_name'])) {
            $cate_name = trim($params['cate_name']);
            $qfs[] = "(cate_name:$cate_name OR cate_name_1:$cate_name OR cate_name_2:$cate_name)";
        }

        if(isset($params['brand_id']) && intval($params['brand_id']) > 0) {
            $brand_id = intval($params['brand_id']);
            $qfs[] = "(brand_id:$brand_id)";
        }

        if(isset($params['goods_name'])) {
            $goods_name = trim($params['goods_name']);
            $qfs[] = "(goods_name:$goods_name)";
        }
        if(isset($params['sale_type'])) {
            $sale_type = trim($params['sale_type']);
            $qfs[] = "(sale_type:$sale_type)";
        }

        if(isset($params['tags'])) {
            $tags = trim($params['tags']);
            $qfs[] = "(tags:$tags)";
        }

        if( isset($params['price']) ) {
            $_p = explode(':', $params['price']);
            if($_p[0] < $_p[1])
                $qfs[] = "( price:[".intval($_p[0])." TO ".intval($_p[1])." ] )";
            else
                $qfs[] = "( price:[".intval($_p[1])." TO ".intval($_p[0])." ] )";
        }
        //( price:[{$params['price']['min']} TO {$params['price']['max']} ] )

        if(isset($params['keyword'])) {
            $keyword = trim($params['keyword']);
            $qfs[] = '((goods_name:'.$keyword.')  OR ( cate_name:'.$keyword.' )  OR ( brand_name:'.$keyword.' ) OR  ( cate_name_1:'.$keyword.' ) OR ( cate_name_2:'.$keyword.' ) OR ( tags:' . $keyword .') OR (title_desc:'.$keyword.') )';
        }

        if(isset($params['ids'])) {
            $_ids = array();
            foreach($params['ids'] as $_id) {
                $_ids[] = "id:$_id";
            }
            $qfs[] = "(".implode(' OR ', $_ids).")";
        }

        if(!$qfs)
            $qfs[] = "*:*";

        $q = urlencode(implode(' AND ',$qfs));

        $sort = urlencode($sort);
        $search_query  = '/select?indent=on&wt=json&version=2.2&facet=on&q=' . $q . '&start='.$limit.'&rows='.$skip .'&sort='. $sort .'&fl=' . self::SEARCH_FIELDS;

        //send http request
        $ch = null;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIME_OUT);
        curl_setopt($ch, CURLOPT_URL, self::SEARCH_URL .$search_query);
        \sprite\lib\Debug::log('search_url', self::SEARCH_URL .$search_query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str_json = curl_exec($ch);
        curl_close($ch);
        if (empty($str_json)) {
            return null;
        }

        return $str_json;
    }

    public function searchCnt($params) {
        return $this->doSearch($params, '', '', '');
    }

}