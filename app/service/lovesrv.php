<?php 
namespace app\service;
use app\dao\BrandDao;
use \app\dao\LoveDao;
use \app\dao\GoodsStatisticsDao;
use \app\dao\UserInfoDao;
use \app\service\BaseSrv;

/*
 * 心盒相关操作
 * @author:daniel
 */

class LoveSrv extends BaseSrv {
	
	/**
	 * 修改用户的心盒状态
	 * $is_delete : 0 喜欢 1不 喜欢
	 */
	public function setLoveByUid($user_id, $goods_id, $is_delete)
	{
		try {
            $params = array('goods_id'=>$goods_id, 'user_id'=>$user_id );
            $info = LoveDao::getSlaveInstance()->find($params);

            $is_change = false;

            try{
                LoveDao::getMasterInstance()->beginTransaction();
                if( $info ) {//update
                    if( $info['is_delete'] != $is_delete ) {
                        LoveDao::getMasterInstance()->edit($info['id'], array('is_delete'=>$is_delete, 'ctime'=>time() ) );
                        $is_change = true;
                    }
                }
                else {//insert
                    if($is_delete == 0) {
                        LoveDao::getMasterInstance()->add(array('user_id'=>$user_id, 'goods_id'=>$goods_id, 'ctime'=>time(), 'is_delete'=>0));
                        $is_change = true;
                    }
                }
                if($is_change) {
                    if($is_delete == 0) { //商品总喜欢数+1
                        GoodsStatisticsDao::getMasterInstance()->increment($goods_id, 'wishes', 1);
                        UserInfoDao::getMasterInstance()->increment($user_id, 'wishes', 1);
                    }
                    else { //商品总喜欢数-1
                        GoodsStatisticsDao::getMasterInstance()->decrement($goods_id, 'wishes', 1);
                        UserInfoDao::getMasterInstance()->decrement($user_id, 'wishes', 1);
                    }
                }
                LoveDao::getMasterInstance()->commit();

            }catch (\Exception $e) {
                LoveDao::getMasterInstance()->rollBack();
            }

            $res = GoodsStatisticsDao::getMasterInstance()->find($goods_id);
            return array( 'goods_id'=>$goods_id, 'status'=>$is_change, 'type'=>$is_delete, 'wishes'=>$res['wishes'] );

		} catch (Exception $e) {
			throw $e;
		}
	}

    public function checkLoved($goods_id, $user_id) {
        if($user_id == 0)
            return false;
        $info = LoveDao::getSlaveInstance()->find(array('user_id'=>$user_id, 'goods_id'=>$goods_id, 'is_delete'=>0)  );

        return $info ? true : false;
    }

    /**
     * @param $user_id
     * @param $limit
     * @param $sort
     * @return array
     * @desc 取得用户翻页列表
     */
    public function getMyList($user_id, $limit, $sort) {
        try {
            $list = LoveDao::getSlaveInstance()->getList( array('user_id'=>$user_id), $limit, $sort);
            foreach($list as $k=>$row) {
                $goods_ids[] = $row['goods_id'];
                $brand_ids[] = $row['brand_id'];
            }
            $goods_list = array();

            if($brand_ids) {
                $brands = BrandDao::getSlaveInstance()->getInfoByIds($brand_ids);
            }

            if($goods_ids) {
                $goodsSrv = new \app\service\GoodsSrv();
                $type_arr = $goodsSrv->getSaleType();

                $counts = GoodsStatisticsDao::getSlaveInstance()->getInfoByGoodsIds($goods_ids);
                foreach($list as $k=>$row) {
                    $goods = array();
                    $goods['goods_id'] = $row['goods_id'];
                    $goods['goods_name'] = $row['goods_name'];
                    $goods['brand_name'] = isset($brands[$row['brand_id']]) ? $brands[$row['brand_id']]['brand_name'] : '';
                    $goods['cate_name'] = $row['cate_name'];
                    $goods['sale_type'] = $row['sale_type'];
                    $goods['sale_type_info'] = $row['sale_type'] ? $type_arr[$row['sale_type']] : array();
                    $goods['tag_arr'] = explode(' ', $row['tags']);
                    $goods['tags'] = $goods['tag_arr'][0];
                    $goods['price'] = (float)$row['price'];
                    $goods['market_price'] = (float)$row['market_price'];
                    $goods['liked'] = true;
                    $goods['stock'] = (int)$row['stock'];
                    $goods['wishes'] = (int)$counts[$row['goods_id']]['wishes'];
                    $goods['weight'] = (int)$counts[$row['goods_id']]['weight'];
                    $goods['default_thumb'] = CDN_YMALL . $row['default_thumb'];
                    $goods['default_image'] = CDN_YMALL . $row['default_image'];

                    $goods_list[] = $goods;
                }
                unset($list);
            }
            return $goods_list;
        }
        catch(\Exception $e) {
            return array();
        }
    }

    /**
     * @param $user_id
     * @return int
     * @desc 取得用户统计
     */
    public function getMyCnt($user_id) {
        try{
            return LoveDao::getSlaveInstance()->getCnt( array('user_id'=>$user_id) );
        }
        catch(\Exception $e) {
            return 0;
        }
    }

}