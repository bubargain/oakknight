<?php 
namespace www\controller;

use \app\service\AddressSrv;

/*
 * product related behavior
 * @author : daniel
 */
class AddressController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);
        parent::checkLogin();
    }

    /**
     * @param $request
     * @param $response
     * @desc 校验用户验证码
     */
    public function save($request, $response) {
        try{
            $addr_id = $request->post('addr_id', 0);

            $info['user_id'] = $this->current_user['user_id'];
            $info['consignee'] = $request->post('consignee');
            $info['region_id'] = $request->post('region_id', 0);
            $info['region_name'] = $request->post('region_name');
            $info['address'] = $request->post('address');
            $info['phone_mob'] = $request->post('phone_mob');

            if(!$info['consignee'] || !$info['address'] || !$info['phone_mob'])
                throw new \Exception('请完善地址信息', 3000);

            if(!preg_match('/^1[0-9]{10}$/', $info['phone_mob']))
                throw new \Exception('手机号码错误', 3000);

            try{
                $obj = new AddressSrv();
                if(!$addr_id) {
                    $addr_id = $obj->add($info);
                }
                else {
                    $obj->edit($addr_id, $info);
                }

                $info['addr_id'] = $addr_id;
                $this->result($info);
            }
            catch(\Exception $e) { throw $e; }
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $request
     * @param $response
     * @desc 返回地址列表
     */
    public function index($request, $response) {

        $user_id = $this->current_user['user_id'];

        $size = 100;
        $page = $request->get('page', 1);
        $page = $page<1 ? 1 : $page;
        $limit = ($page - 1) * $size .','. $size;

        try{
            $obj = new AddressSrv();
            $list = $obj->getList($user_id, $limit);

            $this->result($list);
        }
        catch(\Exception $e) {
            $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function delete($request, $response) {
        $user_id = $this->current_user['user_id'];
        $addr_id = $request->get('addr_id', 0);
        try{
            $obj = new AddressSrv();
            $obj->delete($addr_id, $user_id);

            $this->result(array('ok'));
        }
        catch(\Exception $e) {
            $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function setDefault($request, $response) {
        $user_id = $this->current_user['user_id'];
        $addr_id = $request->get('addr_id', 0);
        try{
            $obj = new AddressSrv();
            $obj->setDefault($addr_id, $user_id);

            $this->result(array('ok'));
        }
        catch(\Exception $e) {
            $this->error($e->getMessage(), $e->getCode());
        }
    }
}
