<?php
namespace www\controller;
use \app\dao\PushDao;
use \app\dao\UserLogDao;
use \app\dao\SettingDao;
use \sprite\lib\Log;

class pushController extends BaseController
{
    public function index($request, $response) {
        $str_url = $_SERVER ['APP_SITE_URL'];
        $help = <<<EOF
##########################################################################################<br />
1.取得一条推送信息 <br />
$str_url/index.php?_c=push&_a=text&app_id=663&app_key=8305dd2e04c24b4d69b29b135ec2f3de7fc8fc71
<br />
2.取得推送用户列表 <br />
$str_url/index.php?_c=push&_a=token&page=10&size=10&app_id=663&app_key=8305dd2e04c24b4d69b29b135ec2f3de7fc8fc71
<br />
3.修改活动消息状态 <br />
$str_url/index.php?_c=push&_a=nodify&app_id=663&app_key=8305dd2e04c24b4d69b29b135ec2f3de7fc8fc71

##########################################################################################<br />
EOF;
        $response->help = $help;
        //echo $help;
        $this->renderSmarty();
	}

    /**
     * @param $request
     * @param $response
     *
     */
    public function text($request, $response) {
        try{
            if($request->deviceToken == 2)
                return ;

            $info = self::getPush();
            self::checkType($info['type']);

            $pushSrv = "\\app\\service\\push\\". $info['type'].'pushSrv';
            $pushSrv = new $pushSrv();
            $pushSrv->init($info);
            $push_num = $pushSrv->getPusherCnt();
            $all_num = $pushSrv->getAllCnt();

            PushDao::getMasterInstance()->edit($info['id'], array('user_count'=>$all_num, 'push_count'=>$push_num));

            if($push_num == 0) {
                self::dropPush($info['id']);
                throw new \Exception('发送用户数量不能为空', 5001);
            }

            $data = array(
                'id' => $info['id'],
                'shortid' => $info['id'],
                'title' => '礼物店',
                'content' => $info['message'],
                'type' => $info['show_type'] ? $info['show_type'] : 1 ,
                'property' => $info['show_property'],
                'totalCount' => $push_num,
            );

            $this->renderJson($data);
        }
        catch(\Exception $e) {}
    }

    public function token($request, $response) {
        try{//存在交叉bug，要求传入id
            if($request->deviceToken == 2)
                return ;

            $info = self::getPush();
            self::checkType($info['type']);
            //
            $page = $request->get('page', 1);
            $size = $request->get('size', 50000);

            $pushSrv = "\\app\\service\\push\\". $info['type'].'pushSrv';
            $pushSrv = new $pushSrv();
            $pushSrv->init($info);
            $list = $pushSrv->getPusher($page, $size);
            $data = array();

            if($list) {
                foreach($list as $row) {
                    $data[] = array('d'=>$row['push_token']);
                }
                $this->renderJson( array('array'=>$data) );
            }
        }catch (\Exception $e){}
    }

    public function nodify($request, $response) {//修改push状态
        $ret = file_get_contents("php://input");
        $data_str = preg_replace('/([a-zA-Z]+)/', '"\1"', $ret);
        $data = json_decode($data_str);
        Log::customLog(
            'push_auto_'.date('Ymd').'.log',
            'start|______|__notify________'.$data_str . "\n\n"
        );

        if( $data->id) {
            try{
                $info = PushDao::getSlaveInstance()->find($data->id);
                self::checkType($info['type']);

                $pushSrv = "\\app\\service\\push\\". $info['type'].'pushSrv';
                $pushSrv = new $pushSrv();
                $pushSrv->init($info);
                $pushSrv->notify($data->id);

                self::dropPush($data->id);

            }catch (\Exception $e){}
        }
        UserLogDao::getMasterInstance()->add(
            array(
                'user_id'=>0,
                'uuid'=>'',
                'type'=>'push',
                'action'=>'notify',
                'item_id'=>isset($data->id) ? $data->id : 0,
                'info'=>$data_str,
                'ctime'=>time(),
            )
        );
        Log::customLog(
            'push_auto_'.date('Ymd').'.log',
            'end|______|__notify________'.$data->id . "\n\n"
        );
    }

    private function checkType($type) {
        $allow_types = \app\dao\PushDao::getSlaveInstance()->getTypes();
        if( !isset($allow_types[$type]) )
            throw new \Exception('', 5000);
    }

    /**
     * @param $id
     * @throws \Exception
     * @desc 清除数据锁
     */
    private function dropPush($id) {
        $push_key = 'app_push_idx';
        try{
            $info = SettingDao::getSlaveInstance()->find($push_key);
            if($info) {
                $push = unserialize($info['uvalue']);
                if($push['id'] == $id) {
                    SettingDao::getMasterInstance()->delete($push_key);//删除
                }
            }
        }catch (\Exception $e){ throw $e; }
    }

    /**
     * @return mixed
     * @throws \Exception
     * @desc 取得 push 信息
     */
    private function getPush() {
        $_time = time();
        $push_key = 'app_push_idx';
        try{
            $info = SettingDao::getSlaveInstance()->find($push_key);
            if(!$info) {
                $push = PushDao::getSlaveInstance()->getValidPush();
                if(!$push)
                    throw new \Exception('暂无push 信息', 30001);

                $info = array('ukey'=>$push_key, 'uvalue'=>serialize($push), 'ctime'=>$_time, 'utime'=>$_time);
                SettingDao::getMasterInstance()->add( $info );
            }
            elseif($_time - $info['ctime'] > 30 * 60) {
                try{
                    // 强制清除
                    $push = unserialize($info['uvalue']);
                    PushDao::getMasterInstance()->edit( $push['id'], array('status'=>3) );//修改状态
                    SettingDao::getMasterInstance()->delete($push_key);//删除
                    //保存删除日志
                    SettingDao::getMasterInstance()->add( array('ukey'=>$push_key.'_err_'.$push['id'], 'uvalue'=>$info['uvalue'], 'ctime'=>$_time, 'utime'=>$_time) );
                }
                catch(\Exception $e) { throw $e; }
            }
            return unserialize($info['uvalue']);

        }catch (\Exception $e){ throw $e; }
    }
}