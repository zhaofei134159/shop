<?php
namespace app\admin\controller;

use app\admin\model\Chart_admin;

use think\Session;

class Index extends Common
{	
	public function __construct(){
		parent::__construct();
		
        if(!Session::get('admin_id','fei_chart')){
        	$this->redirect('login/index');
        }

	}


    public function index(){
        return $this->view->fetch('index');
    }

}