<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service;
use app\dao\AddressDao;

class AddressSrv extends BaseSrv {

    public function getList($user_id, $limit = '0, 10') {
        return AddressDao::getSlaveInstance()->myList($user_id, $limit);
    }

    public function setDefault($id, $user_id) {
        $where = "user_id=".intval($user_id);
        AddressDao::getMasterInstance()->editByWhere(array('is_default'=>0) , $where);
        AddressDao::getMasterInstance()->edit(array('addr_id'=>$id, 'user_id'=>$user_id) , array('is_default'=>1));
    }

    /**
     * @param $phone
     * @param $type
     * @return array|bool
     */
    public function delete($id, $user_id) {
        return AddressDao::getMasterInstance()->delete(array('addr_id'=>$id, 'user_id'=>$user_id));
    }

    public function edit($id, $info) {
        return AddressDao::getMasterInstance()->edit($id , $info);
    }

    public function add($info) {
        return AddressDao::getMasterInstance()->add($info);
    }

    public function getDefault($user_id) {
        return AddressDao::getSlaveInstance()->getDefault($user_id);
    }
}