<?php 
namespace app\admin\model;

use think\Model;

class Common extends Model
{

	public function get_query($sql){
		if(empty($sql)){
			return array();
		}
		// 下面执行原生SQL操作
		$res = $this->query($sql);

		return $res;
	}
}