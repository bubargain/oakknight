<?php

namespace admin\controller;
use \app\dao\ContacterDao;
use \app\dao\UserInfoDao;

class ContactController extends BaseController {

	public function index($request, $response) {
		$response->title = '月生日导出';
		// 设置默认时间为当天
		if($this->isPost()) {
            $month = $request->post('month', date('M'));

            $list = self::getContactInfoByBirthDay($month);
            $user = UserInfoDao::getSlaveInstance()->getInfoByIds(array_keys($list));

            $table = '';
            $title =  date("Y/m/d") . '通讯' . $month . '月生日表';

            self::drowTable($list, $user, $table);

            makeExcel( $table, $title );
        }
        else {
            $this->layoutSmarty();
        }
	}

    private function drowTable($list, $user, &$table) {
        ob_start();
        echo "<table>";
        echo "<tr><th>注册用户电话</th><th>好友名字</th><th>好友电话号码</th><th>生日日期</th></tr>";

        foreach($list as $k=>$r) {
            $_row = count($r);
            $_pre = "<tr><td rowspan='$_row'>{$user[$k]['user_name']}</td>";
            for($i=0; $i<$_row; $i++) {
                echo $_pre . '<td>'.$r['firstname'].$r['lastname'].'</td><td>'.$r['home_phone'].'</td><td>'.date($r['birthday'],"Y/m/d").'</td></tr>';
                $_pre = '<tr>';
            }
        }
        echo "</table>";

        $table = ob_get_clean();
    }

	private function makeExcel($string, $title) {
		$result_str = '<head><meta http-equiv="Content-Type" content="text/html;charset=gb2312"></head>' . $string;
		header ( "Content-Type:text/plain;charset=utf-8" );
		header ( 'Content-Transfer-Encoding: gbk' );
		header ( 'Content-Type: application/vnd.ms-excel;' );
		header ( "Content-type: application/x-msexcel" );
		header ( iconv ( 'UTF-8', 'GBK//IGNORE', 'Content-Disposition: attachment; filename="' . $title . '.xls"' ) );
		// echo iconv('UTF-8', 'GBK//IGNORE', $result_str);
		echo $result_str;
	}

    private function getContactInfoByBirthDay($month) {
        $pdo = ContacterDao::getSlaveInstance()->getpdo();
        $sql = "SELECT * FROM `ym_contact_info` WHERE `home_phone` != '' and birthday>0 and DATE_FORMAT(birthday,'%c')=$month";
        $list = $pdo->getRows($sql);
		$ret = array();
		foreach($list as $r) {
			$ret[$r['user_id']][] = $r;
		}
		
		return $ret;
    }
}