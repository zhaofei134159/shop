<?php
namespace app\admin\controller;

use think\View;
use think\Request;
use think\Session;
use think\Log;
use think\Controller;

// use app\index\model\Account;
// use app\index\model\Power_group;
// use app\index\model\Power;


class Common extends Controller
{
	public $view;

	public function __construct(){
    
		//实例化view
		$this->view = new View();

        $request = \think\Request::instance();
        $this->assign('controller',$request->controller());
        $this->assign('action',$request->action());

        if(Session::get('admin_id','fei_chart')){
        	$this->assign('admin_id',Session::get('admin_id','fei_chart'));
            $this->assign('admin_email',Session::get('admin_email','fei_chart'));
            $this->assign('admin_name',Session::get('admin_name','fei_chart'));
        }
        
        // $this->init($request->controller(),$request->action());
        // echo Power_group::getLastSql();
	}

    //获取当前登录人的权限
    public function init($controller,$action){

        if(Session::get('login_id','fei_chart')){

            $account = Account::get(['id' => Session::get('login_id','fei_chart')]);
            if(empty($account)){
                // 找不到用户报错页面
                $this->error('找不到用户,联系管理员！', 'login/login');
            }

            $this->assign('vest_user',$account);

            //全部权限 直接返回就好
            if($account['power_group_id']==0){
                return ;
            }

            
            $where = array(
                'lf_b2b_power_group.state'=>1,
                'lf_b2b_power_group.id'=>$account['power_group_id'],
            );
            $field = 'lf_b2b_power.id,lf_b2b_power.name,lf_b2b_power.controller,lf_b2b_power.function';
            $join = array();
            $join[] = array('lf_b2b_power_relation_group','lf_b2b_power_group.id = lf_b2b_power_relation_group.power_group_id','left');
            $join[] = array('lf_b2b_power','lf_b2b_power_relation_group.power_id = lf_b2b_power.id','left');
            $powers = Power_group::join($join)->field($field)->where($where)->select();

            $user_power = array();
            foreach($powers as $power){
                $power_cont = ucfirst($power['controller']);
                $user_power[$power_cont][] = $power['function'];
            }
          
            // 对不起， 没有权限
            if($controller=='Index' && $action=='index'){

            }else if(empty($powers)){
                $this->error('权限已经不启用了,联系管理员！', 'login/login');
            }else if(!isset($user_power[$controller])){
                $this->error('没有权限', '/');
            }else if(!in_array($action,$user_power[$controller])){
                $this->error('没有权限', '/');
            }
            

            $this->assign('user_power',$user_power);
        }

    }

}
