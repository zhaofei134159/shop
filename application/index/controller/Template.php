<?php
namespace app\index\controller;

use think\Session;
use app\index\model\Chart_temp;
use app\index\model\Chart_room;


class Template extends Common
{
	public function __construct(){
		parent::__construct();
	}

    public function index()
    {
    	$where = array();
    	$where['is_del'] = 0;
    	
        $temps = Chart_temp::where($where)->select();

        $data = array(
                'temps'=>$temps,
            );
        return $this->view->fetch('index',$data);
    }

    public function look_temp(){
        $id = input('get.id');

        $temp = Chart_temp::where('id',$id)->find();

        $where = array();
        $where['uid'] = 0;
        $photos = Chart_room::where($where)->limit(0,$temp['max_photo'])->select();

        $data = array(
                'photos'=>$photos,
            );
        $html = $this->view->fetch('./'.$temp['html_file'],$data);

        $res = array(
                'name'=>$temp['name'],
                'html'=>$html,
            );
        // return json(['flog'=>1, 'msg'=>'请查看','data'=>$res]);
        return $this->view->fetch('template/look_temp',$res);
    }

}