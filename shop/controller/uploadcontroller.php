<?php

namespace shop\controller;

use \app\service\ImgSrv;
use \app\dao\UploadedFileDao;
use \app\dao\GoodsImageDao;

class UploadController extends BaseController {

    public function index($request, $response) {
        //$ret = array('image_id'=>2312, 'image_url'=>'http://mp1.yokacdn.com/data/files/store_0/admin_99/201306261628192712.jpg');
        //return self::renderJson( array('status'=>200, 'data'=>$ret) );
        try{
            $imgSrv = new ImgSrv();

            if( !$_FILES['upfile']['name'] )
                throw new \Exception('请选择上传文件', 40001);

            $info = $imgSrv->uploadFile( $_FILES['upfile'] );
            $info['store_id'] = $this->current_user['user_id'];
            $info['belong'] = $request->belong;
            $info['item_id'] = $request->item_id;
            $info['add_time'] = time();

            if(!$info['status'])
                throw new \Exception('上传失败', 50001);

            unset($info['status']);
            $file_id = UploadedFileDao::getMasterInstance()->add($info);

            if($info['belong'] == 2) {
                $image = array(
                    'goods_id'=>$request->item_id,
                    'image_width'=>0,
                    'image_height'=>0,
                    'image_url'=>$info['file_path'],
                    'file_id'=>$file_id,
                );

                $id = GoodsImageDao::getMasterInstance()->add($image);
                $image['image_id'] = $id;
            }

            if($image)
                $ret = array('image_id'=>$image['image_id'], 'image_url'=>CDN_YMALL . $image['image_url'], 'image_path'=>$image['image_url']);
            else
                $ret = array('image_id'=>$file_id, 'image_url'=>CDN_YMALL . $info['file_path'], 'image_path'=>$info['file_path']);

            return self::renderJson( array('status'=>200, 'data'=>$ret) );
        }
        catch(\Exception $e) {
            return self::renderJson( array('status'=>500, 'msg'=>$e->getMessage()) );
        }
        return self::renderJson( $ret );
    }

    public function drop($request, $response) {
        $this->_store_id = $this->current_user['user_id'];

        try{
            GoodsImageDao::getMasterInstance()->edit($request->id, array('is_del'=>1));
            $ret = array('status'=>200, 'id'=>$request->id);

        }
        catch(\Exception $e) {
            $ret = array('status'=>500, 'msg'=>$e->getMessage());
        }

        return self::renderJson( $ret );
    }

    public function setDefault($request, $response) {
        $this->_store_id = $this->current_user['user_id'];

        try{
            $t = GoodsImageDao::getSlaveInstance();
            $info = GoodsImageDao::getSlaveInstance()->find($request->id);
            if($info) {
                $list = GoodsImageDao::getSlaveInstance()->getAll($info['goods_id']);
                $wight = 1;
                if($list) {
                    $wight = $list[0]['sort_order'] + 1;
                }
                GoodsImageDao::getMasterInstance()->edit($request->id, array('sort_order'=>$wight));
            }
            $ret = array('status'=>200, 'id'=>$request->id);

        }
        catch(\Exception $e) {
            $ret = array('status'=>500, 'msg'=>$e->getMessage());
        }

        return self::renderJson( $ret );
    }

}