<?php
namespace app\admin\controller;

use app\admin\model\Chart_admin;

use think\Session;

class Strat extends Common
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

        $admins = Chart_admin::where($where)->paginate(15, false, [
                        'query' => request()->param(),
                    ]);
        $page = $admins->render();

        $data = array(
                'admins'=>$admins,
                'page'=>$page,
                'post'=>$post,
            );

        return $this->view->fetch('index',$data);
    }

    public function edit(){
        $get = input('get.');

        $admin = array();
        if(isset($get['type'])&&$get['type']==2){
            if(!empty($get['id'])){
                $admin = Chart_admin::where('id',$get['id'])->find();
            }
        }

        $data = array(
                'admin'=>$admin,
                'type'=>(isset($get['type']))?$get['type']:1,
            );

        return $this->view->fetch('edit',$data);
    }


    public function do_edit(){
        $post = input('post.');

        $name = '';
        if(!empty($post['name'])){
            $name = $post['name'];
        }

        $data = array();
        $data['utime'] = time();
        if(!isset($post['type'])||$post['type']!=2){
            $data['name'] = $name;
            if(!empty($post['password'])){
                $data['password'] = $post['password'];
            }
            $data['email'] = $post['email'];
            $data['ctime'] = time();
            $admin = Chart_admin::create($data);
        }else if($post['type']==2){


        }

        $this->redirect('strat/index');
    }


    public function del(){
        $id = input('get.id');
        $admin = Chart_admin::where(['id' => $id])->find();

        $is_del = 0;
        if(empty($admin['is_del'])){
            $is_del = 1;
        }

        Chart_admin::where(['id'=>$id])->update(['is_del'=>$is_del]);

        $this->redirect('strat/index');
    }

}