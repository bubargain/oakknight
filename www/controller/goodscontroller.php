<?php
namespace www\controller;

use \app\dao\GoodsDao;
use \app\service\GoodsSrv;
use \app\service\LoveSrv;
use \app\service\NotifySrv;

/*
 * product related behavior
 * @author : daniel
 */
class GoodsController extends AppBaseController
{
    /**
     * @param $request
     * @param $response
     * @desc 校验用户验证码
     */
    public function info($request, $response) {
        try{
        	//统计埋点
        	self::userLog( array('type'=>'goods', 'action'=> 'info', 'item_id'=>$request->goods_id) );
        	
            $goodsSrv = new GoodsSrv();
            $info = $goodsSrv->info($request->goods_id);

            if(!$info)
                throw new \Exception('商品信息不存在', 40001);

            $info['liked'] = false;
            $info['notified'] = false;
            if($this->has_login) {
                $loveSrv = new LoveSrv();
                $info['liked'] = $loveSrv->checkLoved($info['goods_id'], $this->current_user['user_id']);

                $notifySrv = new NotifySrv();
                $info['notified'] = $notifySrv->check($info['goods_id'], $this->current_user['user_id']);
            }

            $info['price'] = (float)$info['price'];
            $info['market_price'] = (float)$info['market_price'];

            $info['ad_footer'] = self::ad_footer();
            $this->result($info);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    public function stock($request,$response) {
        try{
          $info = \app\dao\GoodsDao::getSlaveInstance()->find($request->goods_id);
            if(!$info)
                throw new \Exception('库存不存在', 50000);

            $message = ($info['stock'] == 0) ? '太火爆卖完了，可以设置“到货提醒”哦~' : '';

            $this->result( array('stock'=>$info['stock'], 'message'=>$message ) );
        }catch (\Exception $e) {
            $this->result( array('stock'=>-1, 'message'=>$e->getMessage() ));
        }
    }
  
	
	/*
	 * show the lover list of certain product
	 * return person list sort by relationship with searching person
	 */
	public function lover($request,$response)
	{
		try
		{
			if(!$this->has_login)
			{
				$this->error(10000,'user did not login','用户未登录');
			}
			else if($this->header['uid'] && $request->get('goods_id'))
			{
				$goods_id = (int) $request->get('goods_id');
				$user_id = (int) $this->header['uid'];
				$goodsIns = new \app\service\GoodsSrv();
				$personList = $goodsIns->getLoverListByGoodsId($goods_id); // 返回喜欢商品的用户列表
				$res= $goodsIns->sortPersonByRelationship($personList,$user_id);
				$this->result($res);
			}
		}
		catch (\Exception $e)
		{
			$this->error($e->getCode(),$e->getMessage(),"内部错误");
		}
	}

    public function search($request, $response) {
        try{
            //search($params, $sort = 'default', $page = 1, $skip = 20) {
            $sort = $request->get('sort', '');
            $page = $request->get('page', 1);
            $size = $request->get('size', 20);
            $size = $size>100 ? 100 : $size;
			
            $params = array();
            if($request->cate_id)
                $params['cate_id'] = intval($request->cate_id);

            if($request->cate_name)
                $params['cate_name'] = $request->cate_name;

            if($request->sale_type)
                $params['sale_type'] = $request->sale_type;

            if($request->tags)
                $params['tags'] = $request->tags;

            if($request->keyword)
                $params['keyword'] = $request->keyword;

            if($request->price)
                $params['price'] = $request->price;

            $from = $request->get('from', '');
            self::addLog($params, $from, $page);

            if($from == 'search')//保护词转换
                $params = self::getAlia($params);

            $searchSrv = new \app\service\SearchSrv();
            $ret = $searchSrv->search($params, $sort, $page, $size);


            if($this->has_login && $ret['list']) {
                foreach($ret['list'] as $k=>$r) {
                    $ret['list'][$k]['liked'] = false;
                    $ids[] = $r['goods_id'];
                }

                $_tmp = \app\dao\LoveDao::getSlaveInstance()->getMyListByGoodsIds($ids, $this->current_user['user_id']);
                if($_tmp) {
                    foreach($_tmp as $r) {
                        $_t[$r['goods_id']] = true;
                    }
                    foreach($ret['list'] as $k=>$v) {
                        $ret['list'][$k]['liked'] = isset($_t[$v['goods_id']]) ? true : false;
                    }
                }
            }

            $ret['pages'] = ceil($ret['count'] / $size);
            $ret['next'] = ($ret['pages'] <= $page) ? false : true;

            $this->result($ret);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    private function addLog($params, $from, $page) {//增加转换
        ksort($params);
        $data = array(
            'user_id'=>$this->current_user['user_id'],
            'keyword'=>isset($params['keyword']) ? $params['keyword'] : '',
            'params'=>json_encode($params),
            'from'=>$from,
            'page'=>$page,
            'ctime'=>time(),
        );

        try{
            \app\dao\SearchLogDao::getMasterInstance()->add($data);
        }
        catch(\Exception $e){}
    }

    private function getAlia($params) {//别名转换
        if(!$params['keyword'])
            return $params;

        $key = md5($params['keyword']);

        try{
            $info = \app\dao\SearchAliaDao::getSlaveInstance()->find($key);
            if(!$info || !$info['urls'])
                throw new \Exception('暂无别名定义', 20);

            $urls = parse_url($info['urls']);
            parse_str($urls['query'], $news);

            return $news;
        }
        catch(\Exception $e){return $params;}
    }

    private function ad_footer() {
        $ad_footer = CDN_YMALL.'/img/goods_footer.jpg';
        $result = \app\dao\SettingDao::getSlaveInstance()->find( 'img_buttom' );
        if ($result)
            $ad_footer = CDN_YMALL . $result['uvalue'];

        return $ad_footer;
    }
}
