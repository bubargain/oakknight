<?php

namespace admin\controller;

use app\dao\CmsTextlinkDao;
use app\service\MenuSrv;
use \stdClass;

class MenuController extends BaseController {
	// 菜单列表
	public function index($request, $response) {
		$response->title = '菜单列表';
        $loc_id = $request->tag ?  14 : 4;
        $menuSrv = new MenuSrv();
        $response->list = $menuSrv->getMenuMap($loc_id, false);

		$this->layoutSmarty();
	}
		
    public function add($request, $response) {
        $response->title = '添加菜单';
        $loc_id = $request->loc_id ? $request->loc_id : 4;
        if(self::isPost()) {
            $info = self::formatPost($request);
			
            $dao = CmsTextlinkDao::getMasterInstance();

            $info['sort'] = $dao->getAdjacentSortOrder($info['parent_id'], $info['loc_id']);
            $ret = $dao->add( $info );
            if($ret) {
                $goto = 'index.php?_c=menu';
                if($info['loc_id'] == 14)
                    $goto .= '&tag=new';
                $this->showError('添加菜单成功', $goto);
            }
            else {
                $this->showError('添加菜单失败');
            }
        }
        else {
            $response->parent_options = CmsTextlinkDao::getSlaveInstance()->getAllBySort ( array('loc_id='.$loc_id, 'parent_id=0') );
            $response->info = array('loc_id'=>$request->loc_id);
            $this->layoutSmarty ( 'add.form' );
        }
    }

    public function edit($request, $response) {
        $response->title = '修改菜单';
        $info = CmsTextlinkDao::getSlaveInstance()->find( $request->id );
        if(self::isPost()) {
            $data = self::formatPost($request);
            $ret = CmsTextlinkDao::getMasterInstance()->edit( $request->id, $data );

            if($ret) {
                $goto = 'index.php?_c=menu';
                if($data['loc_id'] == 14)
                    $goto .= '&tag=new';
                $this->showError('修改菜单成功', $goto);
            }
            else {
                $this->showError('修改菜单失败');
            }
        }
        else {
            $response->info = $info;
            $response->parent_options = CmsTextlinkDao::getSlaveInstance()->getAllBySort( array('loc_id='.$info['loc_id'], 'parent_id=0') );
            $this->layoutSmarty( 'add.form' );
        }
    }

	public function delete($request, $response) {
		$id = intval( $request->id );
        $info = CmsTextlinkDao::getSlaveInstance()->find( $request->id );
		$childCount = CmsTextlinkDao::getSlaveInstance()->getChildCount( $id );
		if ($childCount) {
			$this->showError( '请先删除该菜单的子菜单' );
		}
		$result = CmsTextlinkDao::getMasterInstance()->delete( $id );
		if ($result) {
            $goto = 'index.php?_c=menu';
            if($info['loc_id'] == 14)
                $goto .= '&tag=new';
            $this->showError( '删除成功' , $goto);
		} else {
			$this->showError( '删除菜单失败' );
		}
	}

	public function changeSortOrder($request, $response) {
        $dao = CmsTextlinkDao::getMasterInstance();
        $info = $dao->find( $request->id );
        if(!$info)
            $this->showError( '信息不存在或者已删除');

        if ($request->type == 'up') {
            $next = $dao->getAdjacentGcategory( "loc_id={$info['loc_id']} AND parent_id ={$info['parent_id']} AND sort < {$info['sort']}", "sort DESC", "1" );
        }
        else {
            $next = $dao->getAdjacentGcategory( "loc_id={$info['loc_id']} AND parent_id ={$info['parent_id']} AND sort > {$info['sort']}", "sort ASC", "1" );
        }
        if(!$next || !$info)
            $this->showError( '已经达到边界，不能调整位置');

        $dao->edit($next['id'], array('sort'=>$info['sort']));
        $dao->edit($info['id'], array('sort'=>$next['sort']));

        $goto = 'index.php?_c=menu';
        if($info['loc_id'] == 14)
            $goto .= '&tag=new';

		header ( "Location: $goto" );
	}

    private function formatPost($request) {
        $data = array();
        $data['loc_id'] = $request->post('loc_id', 0);
        $data['parent_id'] = $request->post('parent_id', 0);
        $data['title'] = $request->post('title', '');
        $data['alt'] = $request->post('alt', '');
        $data['utype'] = $request->post('utype', '');
        $data['url'] = $request->post('url', '');
        $data['sort'] = $request->post('sort', 0);
        $data['status'] = $request->post('status', 0);

        if(empty($data['title']) || empty($data['url']))
            $this->showError('请完善信息');

        return $data;
    }
}