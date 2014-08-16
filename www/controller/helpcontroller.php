<?php 
namespace www\controller;

use \app\dao\GoodsDao;
use \app\service\GoodsSrv;
use \app\service\LoveSrv;
use \app\dao\FeedBackDao;
use \app\dao\SettingDao;

/*
 * product related behavior
 * @author : daniel
 */
class HelpController extends AppBaseController
{
    /**
     * @param $request
     * @param $response
     * @desc 校验用户验证码
     */
    public function feedBack($request, $response) {
        try{
            $data = array();
            $data['user_id'] = $this->current_user['user_id'];
            $data['uuid'] = $this->current_user['clientid'];
            $data['content'] = $request->post('content'); //反馈内容
            $data['contact'] = $request->post('contact'); //联系方式
            $data['type'] = $request->post('type', 'feed'); //反馈类型
            $data['ctime'] = time();

            FeedBackDao::getMasterInstance()->add( $data );

            $this->result(array('ok'));
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    public function version($request, $response) {
		$info = SettingDao::getSlaveInstance()->find('app_version');
		if( $info ) {
            $version = unserialize($info['uvalue']);
            $version["desc"] = $this->formatText($version["desc"]);
            $version["must"] = $version["must"] ? true : false;
            $version["show"] = $version["show"] ? true : false;

            $version["companyWeb"] = $version["companyWeb"] ? $version["companyWeb"] :''; //官网
            $version["qqGroup"] = $version["qqGroup"] ? $version["qqGroup"] :''; //QQ群
            $version["serviceTel"] = $version["serviceTel"] ? $version["serviceTel"] :''; //客服电话
            $version["companyName"] = $version["companyName"] ? $version["companyName"] :''; //公司名称
            $version["copyright"] = $version["copyright"] ? $version["copyright"] :''; //版权声明

            $version["copyright"] = $version["copyright"] ? $version["copyright"] :''; //版权声明
            $version["copyright"] = $version["copyright"] ? $version["copyright"] :''; //版权声明
            $version["shareTitle"] = $version["shareTitle"] ? $version["shareTitle"] :''; //分享标题
            $version["shareBody"] = $version["shareBody"] ? $version["shareBody"] :''; //分享主题
        }
        else {
            $version = array('desc'=>'',
                'must'=>false,
                'show'=>false,
                'companyWeb'=>'',
                'qqGroup'=>'',
                'serviceTel'=>'',
                'companyName'=>'',
                'copyright'=>'',
                'shareTitle'=>'',
                'shareBody'=>'');
        }
        //记录激活日志
        self::userLog( array('type'=>'active','action'=>'index', 'item_id'=>0));

        $this->result($version);
    }

    public function agreement() {
        try {
            $result = \app\dao\SettingDao::getSlaveInstance ()->find ( 'agreement' );
            $this->result( array (
                'uvalue' => $result ['uvalue']
            ) );
        } catch ( \Exception $e ) {
            $this->error( $e->getCode (), $e->getMessage () );
        }
    }

    public function start() {
        try {
            $result = \app\dao\SettingDao::getSlaveInstance ()->find ( 'startPic' );
            /*
            array(
                'flag'=>'open',
                'list'=>array(
                    0=>array('url'=>CDN_YMALL . $t, 'title'=>'', 'desc'=>'desc'),
                    1=>array('url'=>CDN_YMALL . $t, 'title'=>'', 'desc'=>'desc'),
                    2=>array('url'=>CDN_YMALL . $t, 'title'=>'', 'desc'=>'desc'),
                )
            );
            */
            ;
            list($w, $h) = explode('*', $this->header['devicesize']);
            $type = $h > 960 ? 5 : 4;   //取得设备类型
            if($result) {
                $data = unserialize($result['uvalue']);

                $data['flag'] = $data['flag'] == 'open' ? 'open' : 'closed';
                $list = array();

                foreach($data['list'] as $v) {
                    if( $v['phone_type'] != $type)
                        continue;

                    $v['url'] = CDN_YMALL . $v['url'];
                    $v['title'] = $v['title'] ? $v['title'] : '';
                    $v['desc'] = $v['desc'] ? $v['desc'] : '';
                    $list[] = $v;
                }
                $data['list'] = $list;
                $data['count'] = count($data['list']);
            }
            else {
                $data = array('flag'=>'closed', 'list'=>array(), 'count'=>0);
            }

            $this->result($data);

        } catch ( \Exception $e ) {
            $this->error( $e->getCode (), $e->getMessage () );
        }
    }

    public function alipay() {
        try {
            $data = array(
                'ALIPAY_PARTNER_ID'=>"2088101989241025",
                'ALIPAY_SELLER_ID'=>"shopadmin@yoka.com",
                'ALIPAY_PRIVATE_KEY'=>"MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMSnJVx/O12fMLQGRZfSd7R1w50zikwL+N5JFc09mvJ1FcGIOJQgGeLwn4AP+XNNqIM5TD3UeitT2+46HMovTSdUPC3UQz5Gbwren25qSyKtCZrVcsytGiPktbEtmeqwNyvuBQkKRPPEVmijtmNoJCIN0w/xghW8hWLhu3WNmPxnAgMBAAECgYA8mdWln3/H5hq4H4aqtq0062WQuCVSMg5LUfJqASYSuYouza/B1fKkQMqmSEXzBmd7qNiZ5TSQzZLk4vukQtdfUO8wZshl9n3D6xBcaEX41p2Gr1VGjgvu95sIt9AIq+aBc8yctR5lKeIUrIpXOqXEl3TpkqciMtCsAuiRqeAkYQJBAOj4jys2F601xen/WPDTr77iGySrOuVDK2AgLMjspq0F62zUXd8HHar+BBlLMK7EziVTgr/lmtYdp28igumwJuMCQQDYF4q1g6Jt+jcAb4RaqN7RrgxhevvcMNrC0nVMYWheEBMykvm8nmWV1LwsU4XQYQK3jPmNRgU+faadLkLfT4etAkBeT/mNkblmCoXTo+a6n8fe66P3ZUZTd6zAnmXi9ULIesQC67oQxs2w4mKAZtsPdWbK35Ln4qibE6grqnn65q/nAkA+hplRKjSOo+7URnBCl0hZ3YWhkydbMBuscZ8VKb48MWSMprByXlbAgtyr6sL2Z4uUtsrikNclnM/f/SnGWcnFAkEAvbGiw79Rc6IjCkr7BmPGLm3+XdwwYw4eUa4bpUa4lVTu11Z9m0w+POrx0HqN9jLt9tsaopPQBHcmk/l3iKZkBA=="

            );
            $this->result($data);
        } catch ( \Exception $e ) {
            $this->error( $e->getCode (), $e->getMessage () );
        }
    }
}
