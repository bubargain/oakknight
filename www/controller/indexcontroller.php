<?php
namespace www\controller;
use \app\dao\BrandDao;
use app\dao\OrderDao;

class IndexController extends BaseController 
{
	public function index($request,$response){
        //self::redirect('http://t.ymall.com');
        echo "API Doc";
	}

    public function add($request, $response) {
        try{
            $useSrv = new \app\service\UserSrv();
            $useSrv->addUserByUserName($request->phone, $request->client);
        }
        catch(\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function friends($request, $response) {
        $uid = $request->uid;
        $touid = $request->touid;
    
        $action = $request->a;

        $friendSrc = new \app\service\FriendSrv();
        $friendSrc->$action($uid,$touid );

        var_dump($friendSrc->getAll($uid), ' get my list');
        var_dump($friendSrc->getAll($touid), ' get you list');
        var_dump($friendSrc->getFromAll($uid), ' get from you list');
        var_dump($friendSrc->getToAll($touid), ' get to you list');

    }

    public function cache() {
        $cacheObj = \sprite\cache\CacheManager::getInstance('default');
        var_dump($cacheObj);
        $t= $cacheObj->set('test', 'test for abc');
        $r = $cacheObj->get('test');
        var_dump($t, $r);
    }

    public function search($request, $respons) {
        $sort = $request->get('sort', '');
        $page = $request->get('page', 1);
        $size = $request->get('size', 20);
        $size = $size>100 ? 100 : $size;

        $params = array();
        if($request->cate_id)
            $params['cate_id'] = intval($request->cate_id);

        if($request->tags)
            $params['tags'] = $request->tags;

        if($request->keyword)
            $params['keyword'] = $request->keyword;

        if($request->price)
            $params['price'] = $request->price;

        /**/
        $searchSrv = new \app\service\SearchSrv();
        $ret = $searchSrv->search($params, $sort, $page, $size);
        var_dump($ret);
    }


    public function erp($request, $respons) {
        $ecerpObj = new \app\service\ecerp\OrderSrv();
        $t = '马驰';
        try{
            $list = $ecerpObj->getOrdersByBuyer($t);
        }
        catch(\Exception $e) {
            //更新库存
            throw new \Exception('太火爆卖完了，可以设置“到货提醒”哦~',50002);
        }
    }

    function testKD() {
        $data =new \app\service\transfer\kuaidi100srv();
        $list = self::orders();

        $r = array_rand($list);
        echo "<br />======================<br />";

        $r = $list[$r];
var_dump($r);
        switch($r['ship_name'])
        {
            case '圆通快递':
                $realCom='yuantong';
                break;
            case '顺丰快递':
                $realCom='shunfeng';
                break;
            default:
                $realCom=$r['ship_name'];
        }

        echo $data->query($r['code'], $realCom);

    }


    private function orders() {
        return array(
            array('order_id'=>123472, 'order_sn'=>1322097459, 'code'=>302419091196, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123474, 'order_sn'=>1322037294, 'code'=>302419091187, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123476, 'order_sn'=>1322079432, 'code'=>302419091178, 'ship_name'=>'顺丰快递'),

            array('order_id'=>123477, 'order_sn'=>1322021886, 'code'=>302419091150, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123478, 'order_sn'=>1322034694, 'code'=>302419091169, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123480, 'order_sn'=>1322073450, 'code'=>302419091123, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123483, 'order_sn'=>1322038551, 'code'=>302419091132, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123484, 'order_sn'=>1322093614, 'code'=>302419091141, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123487, 'order_sn'=>1322058622, 'code'=>302419091202, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123500, 'order_sn'=>1322047998, 'code'=>9007689243,   'ship_name'=>'圆通快递'),
            array('order_id'=>123507, 'order_sn'=>1322096644, 'code'=>9007689142,   'ship_name'=>'圆通快递'),
            array('order_id'=>123510, 'order_sn'=>1322116135, 'code'=>9007689135,   'ship_name'=>'圆通快递'),
            array('order_id'=>123513, 'order_sn'=>1322129919, 'code'=>9007689151,   'ship_name'=>'圆通快递'),
            array('order_id'=>123514, 'order_sn'=>1322135521, 'code'=>9007689151,   'ship_name'=>'圆通快递'),
            array('order_id'=>123515, 'order_sn'=>1322155831, 'code'=>9007689151,   'ship_name'=>'圆通快递'),
            array('order_id'=>123518, 'order_sn'=>1322155292, 'code'=>302419091257, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123519, 'order_sn'=>1322128264, 'code'=>302419091293, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123522, 'order_sn'=>1322109578, 'code'=>302419092242, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123525, 'order_sn'=>1322174211, 'code'=>302419092172, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123530, 'order_sn'=>1322239730, 'code'=>302419091990, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123531, 'order_sn'=>1322252134, 'code'=>302419091441, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123533, 'order_sn'=>1322333194, 'code'=>302419091672, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123536, 'order_sn'=>1322387776, 'code'=>302419092588, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123537, 'order_sn'=>1322356566, 'code'=>302419093847, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123541, 'order_sn'=>1322411844, 'code'=>302419093801, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123545, 'order_sn'=>1322489201, 'code'=>302419094514, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123552, 'order_sn'=>1322573675, 'code'=>302419094108, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123554, 'order_sn'=>1322557261, 'code'=>302419094223, 'ship_name'=>'顺丰快递'),
            array('order_id'=>123559, 'order_sn'=>1322582070, 'code'=>6382441172,   'ship_name'=>'圆通快递'),
            array('order_id'=>123568, 'order_sn'=>1322536967, 'code'=>6382441357,   'ship_name'=>'圆通快递'),
            array('order_id'=>123569, 'order_sn'=>1322622595, 'code'=>6382302373,   'ship_name'=>'圆通快递'),
            array('order_id'=>123570, 'order_sn'=>1322651571, 'code'=>6382441356,   'ship_name'=>'圆通快递'),
            array('order_id'=>123575, 'order_sn'=>1322624684, 'code'=>6382302467,   'ship_name'=>'圆通快递'),
            array('order_id'=>123576, 'order_sn'=>1322662372, 'code'=>6382302374,   'ship_name'=>'圆通快递'),
            array('order_id'=>123578, 'order_sn'=>1322732317, 'code'=>6382302545,   'ship_name'=>'圆通快递'),
            array('order_id'=>123579, 'order_sn'=>1322775266, 'code'=>6382302725,   'ship_name'=>'圆通快递'),
            array('order_id'=>123582, 'order_sn'=>1322774886, 'code'=>6382302408,   'ship_name'=>'圆通快递'),
            array('order_id'=>123583, 'order_sn'=>1322786329, 'code'=>6382302411,   'ship_name'=>'圆通快递'),
            array('order_id'=>123586, 'order_sn'=>1322763113, 'code'=>6382302547,   'ship_name'=>'圆通快递'),
            array('order_id'=>123587, 'order_sn'=>1322703650, 'code'=>6382302466,   'ship_name'=>'圆通快递'),
            array('order_id'=>123588, 'order_sn'=>1322841586, 'code'=>6382302544,   'ship_name'=>'圆通快递'),
            array('order_id'=>123594, 'order_sn'=>1322882117, 'code'=>6382302714,   'ship_name'=>'圆通快递'),
            array('order_id'=>123597, 'order_sn'=>1322922729, 'code'=>6382302642,   'ship_name'=>'圆通快递'),
            array('order_id'=>123598, 'order_sn'=>1323060314, 'code'=>6382302607,   'ship_name'=>'圆通快递'),
            array('order_id'=>123600, 'order_sn'=>1323093468, 'code'=>6382302815,   'ship_name'=>'圆通快递'),
            array('order_id'=>123601, 'order_sn'=>1323158915, 'code'=>6382302875,   'ship_name'=>'圆通快递'),
            array('order_id'=>123605, 'order_sn'=>1323179758, 'code'=>6382302864,   'ship_name'=>'圆通快递'),
            array('order_id'=>123609, 'order_sn'=>1323219097, 'code'=>6382302894,   'ship_name'=>'圆通快递'),

        );
    }
}