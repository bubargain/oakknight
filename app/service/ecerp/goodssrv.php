<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\ecerp;
use app\service\BaseSrv;
use app\service\ecerp\request;

class GoodsSrv extends BaseSrv {

    public function getGoodsStock($erp_id, $sku) {
        if(!$erp_id || !$sku)
            throw new \Exception('sku 不匹配', 40000);

        $params = array('method'=>'ecerp.shangpin.get', 'condition'=>urlencode("spdm='$erp_id'"));
        $ret = request::get($params);
        if(!$ret)
            throw new \Exception('请求库存无返回', 40000);

        $info = array();

        foreach($ret->shangpins->shangpin->SPSKUS as $r) {
            if($r->SKUDM == $sku)
                $info = array('sku'=>$sku, 'stock'=>$r->SL2);
        }

        if(!$info)
            throw new \Exception('sku 不匹配', 40000);

        return $info;
    }
}
