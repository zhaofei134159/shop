<?php
namespace app\index\controller;

use think\Session;

class Chat extends Common
{
	public function __construct(){
		parent::__construct();
	}

    public function index()
    {

        $data = array();
        return $this->view->fetch('index',$data);
    }

}