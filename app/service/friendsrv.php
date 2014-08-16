<?php
/*
 *
    friends.user_id  == //我的好友列表
    friends.from.user_id  == //我发出的请求确认
    friends.to.user_id  == //我收到的请求确认
 * @author wanjilong@yoka.com
 * */

namespace app\service;
use sprite\redis\RedisManager;

class FriendSrv extends baseSrv{
    static $_instance = null;

    private function getFromKey($uid) {
        return 'friends.from.'.$uid;
    }

    private function getToKey($uid) {
        return 'friends.to.'.$uid;
    }

    private function getKey($uid) {
        return 'friends.'.$uid;
    }

    /**
     * get my attention list
     * @param $uid
     * @param $start
     * @param $len
     * @return mixed
     */
    public function getList($uid, $start = 0, $len = 9) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getKey($uid);
        $end = $start + $len - 1;
        return $redis->lrange($key, $start, $end);
    }

    public function getAll($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getKey($uid);
        return $redis->lrange($key, 0, -1);
    }

    public function getListCnt($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getKey($uid);
        return $redis->llen($key);
    }

    public function getFromListCnt($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getFromKey($uid);
        return $redis->llen($key);
    }

    public function getFromList($uid, $start = 0, $len = 9) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getFromKey($uid);
        $end = $start + $len - 1;
        return $redis->lrange($key, $start, $end);
    }

    public function getFromAll($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getFromKey($uid);
        return $redis->lrange($key, 0, -1);
    }

    public function getToListCnt($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getToKey($uid);
        return $redis->llen($key);
    }

    public function getToList($uid, $start = 0, $len = 9) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getToKey($uid);
        $end = $start + $len - 1;
        return $redis->lrange($key, $start, $end);
    }

    public function getToAll($uid) {
        $redis = RedisManager::getConnect('ymall_sredis');
        $key = $this->getToKey($uid);
        return $redis->lrange($key, 0, -1);
    }

    /**
     * @param $uid
     * @param $touid
     * @return bool
     * @desc 发送邀请
     */
    public function request($uid, $touid) {
        $redis = RedisManager::getConnect('ymall_mredis');

        $fromKey = $this->getFromKey($uid);
        $tokey = $this->getToKey($touid);

        $ids = $redis->lrange($fromKey, 0, -1);

        $exist = false;
        foreach($ids as $r) {
            if($r == $touid) {
                $exist = true;
                break;
            }
        }

        if(!$exist) {
            $redis->multi()->lPush($fromKey, $touid)->lPush($tokey, $uid)->exec();
        }
        return true;
    }

    /**
     * @param $uid
     * @param $touid
     * @return bool
     * @desc 同意好友邀请
     */
    public function accepted($uidf, $touid) {
        $redis = RedisManager::getConnect('ymall_mredis');

        $fromKey = $this->getFromKey($uid);
        $tokey = $this->getToKey($touid);


        $me = $this->getKey($uid);
        $you = $this->getKey($touid);

        $redis->multi()->lPush($me, $touid)->lPush($you, $uid)->lrem($tokey, $uid)->lrem($fromKey, $touid)->exec();
        return true;
    }

    public function refuse($uid, $touid) {
        $redis = RedisManager::getConnect('ymall_mredis');

        $fromKey = $this->getFromKey($uid);
        $tokey = $this->getToKey($touid);

        $redis->multi()->lrem($tokey, $uid)->lrem($fromKey, $touid)->exec();
        return true;
    }

    public function remove($uid, $touid) {
        $redis = RedisManager::getConnect('ymall_mredis');

        $me = $this->getKey($uid);
        $you = $this->getKey($touid);

        $redis->multi()->lrem($you, $uid)->lrem($me, $touid)->exec();
        return true;
    }
}
