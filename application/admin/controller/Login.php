<?php
namespace app\admin\controller;

use app\admin\model\Chart_admin;

use think\Session;

class Login extends Common
{	
	public function __construct(){
		parent::__construct();
	}


    public function index(){
        return $this->view->fetch('index');
    }

    public function do_login(){
    	$post = input('post.');

    	$user = Chart_admin::get(['email' => $post['email']]);

		if(empty($user)){
			return json(['flog'=>0, 'msg'=>'账号密码错误！']);
		}

		//判断密码是否正确
		if($user['password']!=md5($post['password'])){
			return json(['flog'=>0, 'msg'=>'账号,密码错误！']);
		}

		$this->session_login($user);

		return json(['flog'=>1, 'msg'=>'登录成功！']);
    }

    //登录的方法
    public function session_login($account){

    	if(empty($account)){
			Session::clear('fei_chart');
    	}else{
    		Session::set('admin_id',$account->id,'fei_chart');
    		Session::set('admin_email',$account->email,'fei_chart');
    		Session::set('admin_name',$account->name,'fei_chart');
    	}
    }
    
    public function login_out(){
    	$this->session_login(array());
    	$this->redirect('index/index');
    }


}