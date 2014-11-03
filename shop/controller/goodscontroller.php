<?php

namespace shop\controller;

use \app\service\appcountsrv;
use \app\service\GoodsSrv;
use app\common\util\SubPages;

class GoodsController extends BaseController {

    public function __construct($request, $response) {
        parent::__construct($request, $response);
        $this->_store_id = $this->current_user['user_id'];
    }

	public function index($request, $response) {
        $params = array();
        $params['store_id'] = 'store_id ='.$this->_store_id;
        if($request->cate_id)
            $params['cate_id'] = 'cate_id ='.intval($request->cate_id);

        if($request->brand_id)
            $params['brand_id'] = 'brand_id ='.intval($request->brand_id);

        if($request->goods_name)
            $params['goods_name'] = 'goods_name like \'%'.$request->goods_name.'%\'';

        if($request->goods_id) {
            if(preg_match_all('/[0-9]+/', $request->goods_id, $r))
                $params['goods_id'] = 'goods_id in('.implode($r[0]) .')';
        }

        if($request->tag == 'store') { //仓储
            $params['status'] = 'status <> 12 and status & 1 = 0';
        }
        elseif($request->tag == 'hot') {
            $params['sale_type'] = 'sale_type = 1';
        }
        elseif($request->tag == 'new') {
            $params['sale_type'] = 'sale_type = 2';
        }
        elseif($request->tag == 'price') {
            $params['sale_type'] = 'sale_type = 3';
        }
        else {
            $params['status'] = 'status = 12';
        }

        $_curr_page = $request->get("page", 1);

        $total = \app\dao\GoodsDao::getSlaveInstance()->getListCnt($params);
        $url = $_SERVER['PHP_SELF'] . '?' . preg_replace('/[\?|&]page=[0-9]+/', '', $_SERVER['QUERY_STRING']);
        $page = new SubPages($url, 20, $total, $_curr_page);
        $limit = $page->GetLimit();
        $sort = ' utime desc ';

        $response->page_html = $page->GetPageHtml();

        $response->list = array();
        if($total)
            $response->list = \app\dao\GoodsDao::getSlaveInstance()->getList($params, $limit, $sort );

		$response->CDN_YMALL = CDN_YMALL;
        $response->_tag = $request->tag  ? $request->tag : 'index';
        $this->layoutSmarty();
	}

    /**
     * @param $request
     * @param $response
     * @throws \Exception
     * @desc 商品状态修改
     */
    public function batch($request, $response) {
        switch($request->status) {
            case 'show':
                $status = '`status` | 0b01000';
                break;
            case 'unshow':
                $status = '`status` & 0b00111';
                break;
            case 'delete':
                $status = '`status` | 0b00001';
                break;
        }

        $where = 'store_id='.$this->_store_id;
        if(!$request->ids)
            throw new \Exception('请选择操作商品ids','40002');

        preg_match_all('/[0-9]+/', $request->ids, $r);
        if(!$r[0])
            throw new \Exception('请选择操作商品ids', '40002');

        $where .= ' and goods_id in('.implode(',', $r[0]).')';

        try{
            \app\dao\GoodsDao::getMasterInstance()->editStatus($status, $where);
            $this->renderJson(array('status'=>true, 'data'=>array()));
        }
        catch(\Exception $e) {
            $this->renderJson(array('status'=>false, 'code'=>$e->getCode(), 'msg'=>$e->getMessage()));
        }
    }

    public function edit($request, $response) {
        $info = \app\dao\GoodsDao::getSlaveInstance()->info($request->id);
        if(!$info || $info['store_id'] != $this->_store_id)
            throw new \Exception('商品不存在,或不允许修改', 40001);

        if(!$this->isPost()) {
            $response->brands = \app\dao\BrandDao::getSlaveInstance()->findByField('if_show', 1);
            $response->gcategory =\app\dao\GcategoryDao::getSlaveInstance()->findByField('if_show', 1);
            //$response->cates_html = self::getCateLevelHtml($info['cate_id']);

            $info['status'] = $info['status'] & 8;
            $response->info = $info;

            $response->images = \app\dao\GoodsImageDao::getSlaveInstance()->getAll($request->id);
            $response->CDN_YMALL = CDN_YMALL;

            $response->_tag =  'add';
            $srv = new \app\service\GoodsSrv();
            $response->_saleTypes = $srv->getSaleType();

            $this->layoutSmarty();
        }
        else {
            $goods = $this->beforeSave($request);
            //$status = $info['status'] & 11; //$info['status'] & 1011
            if($goods['status'])
                $goods['status'] = $info['status'] | 8;

            if($info['price'] != $goods['price']) //修改价格则需要审核
                $goods['status'] = $goods['status'] & 11;

            $goods['utime'] = time();
            self::initCateInfo($request->cate_id, $goods);
            $goodsSrv = new GoodsSrv();
            $goodsSrv->edit($request->id, $goods);

            $this->success('index.php?_c=goods', 'edit ok');
        }
    }

    public function add($request, $response) {
        if(!$this->isPost()) {
            $response->brands = \app\dao\BrandDao::getSlaveInstance()->findByField('if_show', 1);
			
			$response->gcategory = \app\dao\GcategoryDao::getSlaveInstance()->findByField('if_show', 1);
            $response->cates_html = self::getCateLevelHtml();
            $response->CDN_YMALL = CDN_YMALL;
            $default['more_sale'] = array(
                '配送费用'=>'免',
				'关税'=>'免',
                '送货范围'=>'仅限中国大陆地区',
          
                '发货快递'=>'空运拼箱',
                '客服微信'=>'oakknight(欧欧）',
				'支付提醒'=>'因限额而支付失败时，请先给支付宝账号充值，再用支付宝余额进行支付',
            );

            $response->info = $default;

            $response->_tag =  'add';

            $srv = new \app\service\GoodsSrv();
            $response->_saleTypes = $srv->getSaleType();

            $this->layoutSmarty('edit');
        }
        else {
            $goods = $this->beforeSave($request);
            if($goods['status'])
                $goods['status'] = 8;

            $goods['ctime'] = $goods['utime'] = time();

            self::initCateInfo($request->cate_id, $goods);

            $goodsSrv = new GoodsSrv();
            $goodsSrv->add($goods, $request->post('images'));

            $this->success('index.php?_c=goods', 'add ok');
        }
    }

    private function beforeSave($request) {
        $goods = array();
        if(!$request->post('goods_name') || !$request->post('brand_id') || !$request->post('cate_id')
                || !$request->post('price') )
            throw new \Exception('请完善商品信息', 40001);

        $goods['store_id'] = $this->_store_id;
        $goods['goods_name'] = $request->post('goods_name');
        $goods['brand_id'] = $request->post('brand_id');
        $goods['cate_id'] = $request->post('cate_id');

        $goods['status'] = intval( $request->post('status') );

        $goods['if_codpay'] = $request->post('if_codpay');
        $goods['recommend'] = $request->post('recommend', 0);
        $goods['sale_type'] = $request->post('sale_type', 0);
        $goods['market_price'] = $request->post('market_price');
        //$goods['cost_price'] = $request->post('cost_price');
        $goods['price'] = $request->post('price');
        $goods['tags'] = $request->post('tags');
        $goods['stock'] = $request->post('stock');
        $goods['sku'] = $request->post('sku');
        $goods['default_thumb'] = $request->post('default_thumb');
        $goods['default_image'] = $request->post('default_image');
        $goods['share_title'] = $request->post('share_title');
        $goods['share_desc'] = $request->post('share_desc');

        $goods['title_desc'] = $request->post('title_desc');
        $goods['description'] = $request->post('description');

        //more_property 处理
        $more_property = array();
        $p_keys = $request->post('property_name');
        $p_values = $request->post('property_value');
        if($p_keys && $p_values) {
            foreach($p_values as $_k=>$val) {
                if(!$val || !$p_keys[$_k])
                    continue;
                $more_property[$p_keys[$_k]] = $val;
            }
        }
        $goods['more_property'] = serialize($more_property);

        //more_sales 处理
        $more_sale = array();
        $s_keys = $request->post('sale_name');
        $s_values = $request->post('sale_value');

        if($s_keys && $s_values) {
            foreach($s_values as $_k=>$val) {
                if(!$val || !$s_keys[$_k])
                    continue;
                $more_sale[$s_keys[$_k]] = $val;
            }
        }
        $goods['more_sale'] = serialize($more_sale);

        return $goods;
    }

    private function initCateInfo($cate_id, &$goods) {
        $list = \app\dao\GcategoryDao::getSlaveInstance()->ancestor($cate_id);
        if(!$list)
            throw new \Exception('分类不存在或者被删除', 3001);

        $goods['cate_id_1'] = isset($list[0]['cate_id']) ? $list[0]['cate_id'] : 0;
        $goods['cate_id_2'] = isset($list[1]['cate_id']) ? $list[1]['cate_id'] : 0;

        $info = array_pop($list);
        $goods['cate_name'] = $info['cate_name'];
    }

    private function getCateLevelHtml($id = 0) {
        $maps = $info = array();

        $list = \app\dao\GcategoryDao::getSlaveInstance()->findByField('if_show', 1);

//		var_dump($list);die();
        foreach($list as $row) {
            $maps[$row['parent_id']][$row['cate_id']] = $row['cate_id'];
            $info[$row['cate_id']] = $row;
        }

        $html = '';

//		var_dump($maps);die();
					
		foreach($maps[0] as $_one) {
			if(!isset($maps[$_one]))
				continue;

			$html .= "<optgroup label='{$info[$_one]["cate_name"]}'>";
			foreach($maps[$_one] as $_two) {
				if($id == $_two)
					$html .= "<option value='{$info[$_two]['cate_id']}' selected='true'>".$info[$_two]['cate_name'].'</option>';
				else
					$html .= "<option value='{$info[$_two]['cate_id']}'>".$info[$_two]['cate_name'].'</option>';
			}
			$html .= "</optgroup>";;
		}
		

        return $html;
    }

    protected function initMenu() {
        return array(
          0=>array('url'=>'index.php?_c=goods', 'tag'=>'index', 'title'=>'销售商品'),
          1=>array('url'=>'index.php?_c=goods&tag=store', 'tag'=>'store', 'title'=>'仓库商品'),
          2=>array('url'=>'index.php?_c=goods&tag=hot', 'tag'=>'hot', 'title'=>'热卖商品'),
          3=>array('url'=>'index.php?_c=goods&tag=new', 'tag'=>'new', 'title'=>'新品商品'),
          4=>array('url'=>'index.php?_c=goods&tag=price', 'tag'=>'price', 'title'=>'促销商品'),
          5=>array('url'=>'index.php?_c=goods&_a=add', 'tag'=>'add', 'title'=>'增加商品'),
        );
    }


}