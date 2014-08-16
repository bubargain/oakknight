<?php

namespace admin\controller;

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

            $ret = array('image_id'=>$file_id, 'image_url'=>CDN_YMALL . $info['file_path'], 'image_path'=>$info['file_path'],'belong'=>$info['belong']);

            return self::renderJson( array('status'=>200, 'data'=>$ret) );
        }
        catch(\Exception $e) {
            return self::renderJson( array('status'=>500, 'msg'=>$e->getMessage()) );
        }
        return self::renderJson( $ret );
    }

    public function drop($request, $response) {
        try{
            //GoodsImageDao::getMasterInstance()->edit($request->id, array('is_del'=>1));
            UploadedFileDao::getMasterInstance()->edit($request->id, array('is_del'=>1));
            $ret = array('status'=>200, 'id'=>$request->id);
        }
        catch(\Exception $e) {
            $ret = array('status'=>500, 'msg'=>$e->getMessage());
        }

        return self::renderJson( $ret );
    }
}