<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service;
use \app\dao\GoodsDao;
use \app\dao\GoodsStatisticsDao;
use \app\dao\GoodsImageDao;
use \app\dao\GcategoryDao;
use \app\dao\BrandDao;
use \sprite\cache\CacheManager;

class GoodsSrv extends BaseSrv {

    /**
     * @param $info 商品的信息
     * @param array $file_ids 对应的文件上传id
     * @throws \Exception
     */
    public function add($info, $file_ids = array()) {
        try{
            if(!$info['default_image'] && $file_ids) {//处理默认图片
                $image = GoodsImageDao::getSlaveInstance()->find($file_ids[0]);
                $info['default_image'] = $image ? $image['image_url'] : '';
            }

            if(!$info['default_image'])
                $info['default_image'] = DEFAULT_IMAGE;

            GoodsDao::getMasterInstance()->beginTransaction();
            $goods_id = GoodsDao::getMasterInstance()->add($info);

            $extm = array('goods_id'=>$goods_id);
            GoodsStatisticsDao::getMasterInstance()->add($extm);

            if($file_ids) {
                $where = 'image_id in (' . implode(',', $file_ids) . ')';
                GoodsImageDao::getMasterInstance()->editByWhere(array('goods_id'=>$goods_id), $where);
            }

            GoodsDao::getMasterInstance()->commit();
            return $goods_id;
        }
        catch(\Exception $e) {
            GoodsDao::getMasterInstance()->rollBack();
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $info
     * @throws \Exception
     * @desc 商品的编辑
     */
    public function edit($id, $info) {
        try{
            GoodsDao::getMasterInstance()->edit($id, $info);
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id
     * @return array
     * @desc 返回商品详细信息
     */
    public function info($id) {
    	
    	
        $cache = CacheManager::getInstance();

        $key = 'ymall_goods_'.$id;
        $ret = $cache->get($key);
    	if(!$ret)  //add memcache
    	{
	        $info = GoodsDao::getSlaveInstance()->info($id);
	
	        if($info) {
	            $image = GoodsImageDao::getSlaveInstance()->getAll($id);
	            foreach($image as $i) {
	                $info['images'][] = array('image_url'=>CDN_YMALL .$i['image_url']);
	            }
				//$info['images'][] = array('image_url'=>'http://mp1.yokacdn.com/data/files/mobile/2013/10/14/13817465667967.jpg');
	
	            $tmp = $info['more_property'];
	            $info['more_property'] = array();
	            foreach($tmp as $k=>$v) {
	                $info['more_property'][] = array('key'=>$k, 'value'=>$v ) ;
	            }
	
	            $tmp = $info['more_sale'];
	            $info['more_sale'] = array();
	
	            $_idx = date('YmdH', time());
	            foreach($tmp as $k=>$v) {
	                //if($k == '发货时间' && ($_idx >= '2013093018' && $_idx < '2013100800') )
	                //    $v = '十一国庆假日1-7号暂停发货，8号恢复正常';
	
	                $info['more_sale'][] = array('key'=>$k, 'value'=>$v ) ;
	            }
	
	            $info['share_title'] = $info['goods_name'];
	            $left = round($info['market_price'] - $info['price']);
	            if($left>0)
	                $info['share_title'] .= '（节省:￥'.number_format($left).'）';
	
	            $info['default_image'] = CDN_YMALL . $info['default_image'];
	            $info['default_thumb'] = CDN_YMALL . $info['default_thumb'];
	
	            $tmp = GcategoryDao::getSlaveInstance()->find($info['cate_id_1']);
	            $info['cate_name_1'] = $tmp ? $tmp['cate_name'] : '';
	
	            $tmp = GcategoryDao::getSlaveInstance()->find($info['cate_id_2']);
	            $info['cate_name_2'] = $tmp ? $tmp['cate_name'] : '';
	
	            $tmp = BrandDao::getSlaveInstance()->find($info['brand_id']);
	            $info['brand_name'] = $tmp ? $tmp['brand_name'] : '';
	        }
	        $ret = $info;
	        $cache->set($key, $ret, 1, 5*60);
    	}

        return $ret;
    }

    public function getSaleType() {
        $cache = CacheManager::getInstance();
        $key = 'sale_type_arr';
        $ret = $cache->get($key);
        if(!$ret) {
            $ret = array();
            $ukey = 'sale_type_arr';
            $data = \app\dao\SettingDao::getSlaveInstance()->find($ukey);
            $list = unserialize($data['uvalue']);
			
			
			if($list) //list is not null
			{
				foreach($list as $r) {
					$r['big']['img'] = CDN_YMALL . $r['big']['img'];
					$r['small']['img'] = CDN_YMALL . $r['small']['img'];
					$_t = @getimagesize($r['big']['img']);
					$r['big']['w'] = $_t[0];
					$r['big']['h'] = $_t[1];
	
					$_t = @getimagesize($r['small']['img']);
					$r['small']['w'] = $_t[0];
					$r['small']['h'] = $_t[1];
	
					$ret[$r['key']] = $r;
				}
			}
            $cache->set($key, $ret, 1, 10*60);
        }
        return $ret;
    }
}