<?php
namespace app\admin\controller;

use app\admin\model\Chart_user;
use app\admin\model\Chart_room;

use think\Session;

class User extends Common
{	
	public function __construct(){
		parent::__construct();
		
        if(!Session::get('admin_id','fei_chart')){
        	$this->redirect('login/index');
        }

	}


    public function index(){
        $post = input('post.');

        $where = array();
        $where['id'] = array('>','0');

        if(!empty($post['email'])){
            $where['email'] = array('like','%'.$post['email'].'%'); 
        }

        if(isset($post['is_del'])&&$post['is_del']!='-1'){
            $where['is_del'] = $post['is_del'];
        }

        $users = Chart_user::where($where)->paginate(15, false, [
                        'query' => request()->param(),
                    ]);
        $page = $users->render();

        $data = array(
                'users'=>$users,
                'page'=>$page,
                'post'=>$post,
            );

        return $this->view->fetch('index',$data);
    }


    public function del(){
        $id = input('get.id');
        $admin = Chart_user::where(['id' => $id])->find();

        $is_del = 0;
        if(empty($admin['is_del'])){
            $is_del = 1;
        }

        Chart_user::where(['id'=>$id])->update(['is_del'=>$is_del]);

        $this->redirect('user/index');
    }

    public function photo(){
        $post = input('post.');

        $where = array();
        $where['chart_user.is_del'] = 0;

        if(!empty($post['email'])){
            $where['chart_user.email'] = array('like','%'.$post['email'].'%'); 
        }

        if(isset($post['is_del'])&&$post['is_del']!='-1'){
            $where['chart_room.is_del'] = $post['is_del'];
        }

        $field = 'chart_room.id,chart_room.path,chart_room.is_del,chart_room.create_time as ctime,chart_user.nikename,chart_user.email';
        $join = array();
        $join[] = array('chart_user','chart_user.id = chart_room.uid','left');
        $charts = Chart_room::join($join)->where($where)->field($field)->paginate(10, false, [
                        'query' => request()->param(),
                    ]);

        $page = $charts->render();

        $data = array(
                'charts'=>$charts,
                'page'=>$page,
                'post'=>$post,
            );

        return $this->view->fetch('photo',$data);
    }

    public function photo_del(){
        $id = input('get.id');
        $admin = Chart_room::where(['id' => $id])->find();

        $is_del = 0;
        if(empty($admin['is_del'])){
            $is_del = 1;
        }

        Chart_room::where(['id'=>$id])->update(['is_del'=>$is_del]);

        $this->redirect('user/photo');
    }

}