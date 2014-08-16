<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\ecerp;
use app\service\BaseSrv;
use app\service\ecerp\request;

class OrderSrv extends BaseSrv {

    public function submit($order, $goods, $address) {
        $_d = explode("\t", $address['region_name']);

        /**
         * pay_codes 数据字典（来源ecerp）
         * 000 结余款; 001:支付宝; 002:财付通; 003 积分换购; 004 在线支付; 005 货到付款; 006 邮局汇款; 007 自提;
         * 008 支付礼券; 009 支付礼品卡; 010 现金; 011 预存款支付; 012 快钱; 013 刷卡
         */
        $req_array = array(
            "method" => "ecerp.trade.orders", // (必)
            "outer_tid" =>$order['order_sn'], // (必) 外部订单号
            "outer_ddly" =>0, // (必) 新增订单来源
            "outer_shop_code" =>'ymall旗舰店', // (必) 店铺代码
            "buyer_message" =>'ymall app', // (必) 买家留言
            "mail" => $order['buyer_name']."@ymall.com", // (必) 邮箱
            "store_code" => "", // (必) 仓库代码

            "receiver_zip" =>$address['zipcode'], // (必) 邮编
            "receiver_phone" =>$address['phone_tel'], // (必) 电话
            "receiver_mobile" =>$address['phone_mob'], // (必) 手机
            "receiver_name" =>$address['consignee'], // (必) 收货人
            "receiver_state" =>$_d[0], // (必) 省
            "receiver_city" =>$_d[2] ? $_d[1] : $_d[0], // (必) 市
            "receiver_district" =>$_d[2] ? $_d[2] : $_d[1], // (必) 区
            "receiver_address" =>$address['address'],   //收货地址

            "total_discount_fee" =>$order['discount_amount'], // 订单优惠信息

            "logistics_fee" =>"0", // (必)  运费
            "logistics_type" =>"express", // (必)  运输方式
            "fptt" =>"", // 发票抬头
            "syfp" =>"", // 是否开发票
            "lxdm" =>"", // 发票类型代码
            "ticket_no" =>$order['out_trade_sn'], // 交易单号
            "pay_moneys" =>$order['order_amount'], // 付款金额
            "pay_datatimes" =>date('Y-m-d H:i:s', $order['pay_time']), // 支付时间
            "pay_codes" =>'001', // 支付代码
            "itemsns" =>$goods['erp_id'], // 商品编号
            "skusns" =>$goods['sku'], // 货号
            "nums" =>$goods['quantity'], // 数量
            "prices" =>$goods['price'], // (必) 价格
        );

        if($order['buyer_name'] == '15652244988')
            $req_array["pay_codes"] = '001';

        $ret = request::post($req_array);
        if(!$ret)
            throw new \Exception('erp 订单提交无响应', 45000);

        if(isset($ret->ERROR))
            return array('erp_sn'=>'ERP_ERROR', 'msg'=>(string)$ret->ERROR);

        $erp_sn = (string)$ret->trade_orders_response->trade->tid;

        return array('erp_sn'=>$erp_sn, 'msg'=>'erp ok');
    }
	
	/**
	 * 
	 * 同步EC_ERP
	 * @param xml $xml
	 */
	public function ErpSync($xml)
	{
        foreach($xml->shangpins->shangpin as $row)
        {

            foreach($row->SPSKUS->SPSKU as $oneRow)
            {
                //var_dump($oneRow);
                $SKUDM = strval($oneRow->SKUDM);
                /**
                 * 临时修改，获得最新真实库存数.需将 $oneRow->SL3 改回 $oneRow->SL2
                 * AT: 20130913
                 * by: daniel ma
                 */
                         
                $SL =    strval($oneRow->SL2);
                $erp_id = strval($oneRow->spdm);
                //var_dump($SKUDM."/".$erp_id."/".$SL);
                \app\dao\GoodsDao::getMasterInstance()->edit(array('sku'=>$SKUDM),array('stock'=>$SL,'erp_id'=>$erp_id));
            }
        }
	}


    public function getOrder($erp_sn = '') {
        $params = array('method'=>'ecerp.trade.get', 'condition'=>urlencode("djbh='$erp_sn' and fh=1"));
        $ret = request::get($params);
        if(!$ret)
            throw new \Exception('请求库存无返回', 40000);

        if($ret->trades->trade) {
            if( isset($ret->trades->trade->wldh) ) {
                $_ls = explode(',', $ret->trades->trade->wldh);

                return array('shipping_code'=>$_ls[0], 'shipping_name'=>$ret->trades->trade->wlgsmc);
            }

        }

        throw new \Exception('目前未发货', 40001);

    }

    /**
     * @param $buyer_name
     * @return array
     * @throws \Exception
     * @desc 收货人姓名查找发货订单,倒序
     */
    public function getOrdersByBuyer($buyer_name) {
        try{
            $params = array('method'=>'ecerp.trade.get', 'condition'=>urlencode("shouhuor='$buyer_name' and fh=1"), 'orderby'=>'fhrq', 'orderbytype'=>'DESC');
            $ret = request::get($params);
            if(!$ret)
                throw new \Exception('请求ERP无返回', 40000);

            foreach($ret->trades->trade as $r) {
                $tmp = explode(',', $r->lydh );
                foreach($tmp as $v) {
                    $v = trim($v);
                    $list[$v] = trim($r->djbh);
                }
            }
            return $list;
        }catch (\Exception $e) {
            throw $e;
        }
    }
}
