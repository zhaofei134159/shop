<?php
namespace app\index\controller;

use app\index\model\Chart_user;


use think\Session;
use think\Config;


class Login extends Common
{	

	public $login;

	public $class_arr = array(
                'weibo'=>'weibo_uid',
                'weixin'=>'weixin_openid',
                'qq'=>'qq_openid',
            );

	public function __construct(){
		parent::__construct();
		
		$config = Config::load('config.php');
		$this->login = $config['login'];
	}


    public function login(){
        return $this->view->fetch('login');
    }

    public function do_login(){
    	//获取post的值
    	$post = input('post.');

		$user = Chart_user::get(['email' => $post['email']]);

		if(empty($user)){
			return json(['flog'=>0, 'msg'=>'没有该账户,请注册！']);
		}

		//判断密码是否正确
		if($user['password']!=md5($post['password'])){
			return json(['flog'=>0, 'msg'=>'密码错误！']);
		}

		$this->session_login($user);
		
		return json(['flog'=>1, 'msg'=>'登录成功！']);
    }


    public function do_register(){
    	//获取post的值
    	$post = input('post.');

		$account = Chart_user::get(['email' => $post['email']]);
		if(!empty($account)){
			return json(['flog'=>0, 'msg'=>'登录账号已存在，可直接登录！']);
		}

		// 创建用户
    	$accountArr = array(
			'email' => $post['email'],
			'password' => md5($post['password']),
			'is_del' => 1,
			'state' => 0,
			'create_time'=>time(),
			'update_time'=>time(),
    	);
		$accountData = Chart_user::create($accountArr);

		$this->session_login($accountData);
		
		return json(['flog'=>1, 'msg'=>'注册成功']);
    }

    //登录的方法
    public function session_login($account){

    	if(empty($account)){
			Session::clear('fei_chart');
    	}else{
    		$email = '';
    		if(!empty($account->email)){
    			$email = $account->email;
    		}
    		Session::set('login_id',$account->id,'fei_chart');
    		Session::set('login_email',$email,'fei_chart');
    		Session::set('login_name',$account->name,'fei_chart');
    		Session::set('login_nikename',$account->nikename,'fei_chart');
    	}
    }


    public function login_out(){
    	$this->session_login(array());
    	$this->redirect('index/index');
    }


    //腾讯QQ登录
    function qq_login(){
    	//判断是PC的还是手机网页访问，如果是手机则需要传入mobile 默认为PC
    	$web_type = input('get.web_type');
        if(empty($web_type)||$web_type!='mobile'){
            $web_type = 'pc';
        }

        $appkey = $this->login['qq_appkey'];
        $appsecret = $this->login['qq_appsecret'];
        $login = $this->login['qq_login'];
        $response_type = 'code';
        $state = base64_encode('zf');

        header('location:https://graph.qq.com/oauth2.0/authorize?response_type='.$response_type.'&client_id='.$appkey.'&redirect_uri='.urlencode($login).'&state='.$state.'&display='.$web_type);
    }

    //qq登录
    function qq_web(){
    	$code = input('get.code');
    	$state = input('get.state');

        $appkey = $this->login['qq_appkey'];
        $appsecret = $this->login['qq_appsecret'];
        $login = $this->login['qq_login'];


        if(base64_encode('zf')==$state&&!empty($code)){
            //获取access token
            $token_url = 'https://graph.qq.com/oauth2.0/token?client_id='.$appkey.'&client_secret='.$appsecret.'&code='.$code.'&redirect_uri='.urlencode($login).'&grant_type=authorization_code';

            $token_info = $this->_curl_get_request($token_url);
            if(empty($token_info)||!strstr($token_info,'access_token')){
                //登录错误的跳转页面
        		return $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
            }

            $token = array();
            parse_str($token_info,$token);

            if(!isset($token['access_token'])&&empty($token['access_token'])){
        		return $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
            }

            $access_token = $token['access_token'];

            $user_url = 'https://graph.qq.com/oauth2.0/me?access_token='.$access_token;
            $user_info = $this->_curl_get_request($user_url);

            $user_info = str_replace('callback(','',$user_info);
            $user_info = str_replace(');','',$user_info);
            $user_info = json_decode($user_info,true);

            if(!array_key_exists("openid",$user_info)){
        		return $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
            }

            $qq_user_url = 'https://graph.qq.com/user/get_user_info?access_token='.$access_token.'&oauth_consumer_key='.$appkey.'&openid='.$user_info['openid'];
            $qq_user_info = $this->_curl_get_request($qq_user_url);

            if($qq_user_info['ret']<0){
        		return $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
            }

            $user_data = array(
                    'id'=>$user_info['openid'],
                    'name'=>$qq_user_info['nickname'],
                    'gender'=>($qq_user_info['gender']=='男')?'m':'w',
                    'avatar_large'=>$qq_user_info['figureurl_qq_1'],
                );

            $qq_user = $this->user_data('qq',$user_data);

			$this->redirect('index/index');

            //微信账号存在且有手机号
            // if($qq_user['flag']==1){
            	// echo '正常';
                // header('location:'.HOME_URL);
            // }else{
            	// echo '绑定页面';
        		// $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
                // header('location:'.HOME_URL.'login/bind_user_phone?user='.base64_encode($qq_user['data']['id']).'&type='.base64_encode('qq'));
            // }

        }else{
            //微信登录错误的跳转页面
        	return $this->view->fetch('web_error',array('type'=>'腾讯QQ','login'=>'qq'));
        }
    }

    /**
    * 判断微博账号是否在网站已经存在且是否有手机号
    * 1.若存在且有手机号，则直接登录
    * 2.若存在但无手机号，则需要绑定手机号
    * 3.若不存在，则需要插入微博账号，且绑定手机号 
    */
    protected function user_data($type,$user_data){
 
        $class_arr = $this->class_arr;

        $login_user = Chart_user::where($class_arr[$type],$user_data['id'])->find();

    	//微博uid在都学网中有账号 且手机号存在是，则直接登录
    	if(!empty($login_user) && !empty($login_user['email'])){
    		//登录机制
            $this->_external_session_login($login_user,$user_data);

            return array('flag'=>1,'msg'=>'有账号且有手机号');

    	}else if(!empty($login_user) && empty($login_user['email'])){
    		//微博uid在都学网中有账号 但手机号不存在

    		//登录机制
            $this->_external_session_login($login_user,$user_data);

            return array('flag'=>2,'msg'=>'有账号但无手机号','data'=>$login_user);

    	}else if(empty($login_user)){
    		//无微博账号

    		$data = array(
    				'id'=>$user_data['id'],
    				'nickname'=>$user_data['name'],
    				'sex'=>($user_data['gender']=='m')?1:2,
    			);

			$login_user = $this->_external_auth_register($type,$data);

            $this->_external_session_login($login_user,$user_data);

            return array('flag'=>3,'msg'=>'无账号无手机号','data'=>$login_user);
    	}

    }


    //第三方注册
    protected function _external_auth_register($type,$user_data){
    	$external_uid = $user_data['id'];

    	$class_arr = $this->class_arr;

        $map['name'] = $login_account = $type.'_'.$external_uid;
        $whereor = array(
        		'name' => $login_account,
        		$class_arr[$type] => $external_uid,
        	);
        $exist_user = Chart_user::whereOr($whereor)->find();

        if ($exist_user != false)
        {
            return $exist_user;
        }

        $login_salt = rand(11111, 99999);
        $password = chr(rand(65, 90)) .rand(11111, 99999) . chr(rand(65, 90));
        $map['nikename'] = $user_data['nickname'];
        $map['sex'] = $user_data['sex'];
        $map['login_stat'] = $login_salt;
        $map['password'] = md5(md5($password) . $login_salt);
        $map['ctime'] = time();
        $map['utime'] = time();
		$map['phone'] = '';
		$map['user_type'] = 3;
        $class = $class_arr[$type];
        $map[$class] = $external_uid;

        $user = Chart_user::create($map);

		//注册的账号登陆网站
		$this->session_login($user);

        return $user;
    }




    //登录机制
    protected function _external_session_login($login_user,$user_data){
		$this->session_login($login_user);

        if (empty($login_user['headimg']))  // 更新用户头像 at 2016-06-01
        {	
        	if(!empty($user_data['avatar_large'])){
                $headimg = $this->_save_external_user_avatar($user_data['avatar_large']);
                Chart_user::where('id',$login_user['id'])->update(array('headimg' => $headimg));
        	}
        }
    }

    /****
    * @desc 保存用户的头像
    * @param $url
    * @return string
    */
    private function _save_external_user_avatar($url){	
        if (strpos($url, 'http://') !== 0){
            return '';
        }

        $content = $this->_curl_get_request($url);
            
        if ($content != false) {
          	$save_dir = 'uploads' . DS . 'headimg/'.date('Ymd');
            // $save_dir = trim($save_dir,'/');
            if(!is_dir($save_dir)){
            	mkdir($save_dir,0777);
            }

            $file_name = date('YmdHis') . rand(10000, 99999) . '.jpg';
            $save_path = $save_dir.'/'.$file_name;

            file_put_contents($save_path, $content);
            $web_path = $save_dir . '/' . $file_name;

            return $web_path;
        }
        else
        {
            return '';
        }
    }


    //curl
    private function _curl_get_request($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $return_str = curl_exec($ch);
        curl_close($ch);
        $format_result = json_decode($return_str, true);
        return $format_result ? $format_result : $return_str;
    }

    //绑定页面
    public function bind_user_phone(){
    	$get = $this->input->get();
        $class_arr = $this->class_arr;

    	$type = base64_decode($get['type']);
    	$id = base64_decode($get['user']);

    	$user = $this->zf_user_model->select_one('id='.$id);

    	if(!array_key_exists($type,$class_arr)||empty($user)){
			header('location:'.HOME_URL);
    	}

    	$data = array(
    			'type'=>$type,
    			'user'=>$user,
    		);

    	$this->load->view(HOME_URL.'login/bind_user_phone',$data);
    }

    //绑定
    public function do_bind(){
    	$post = $this->input->post();

        $class_arr = $this->class_arr;

   		$class = array(
            'weibo'=>'微博',
            'weixin'=>'微信',
            'qq'=>'腾讯QQ',
        );

        $type = $post['type'];
        $fen = $class[$type];
        $field = $class_arr[$type];

		$data = array();
		$res = array();

		$email_user = $this->zf_user_model->select_one('email="'.$post['email'].'"');
        $login_user = $this->zf_user_model->select_one('id='.$post['login_uid']);

       if(empty($login_user)||empty($login_user[$field])){
            $this->load->view(HOME_URL.'login/web_error',array('type'=>$fen,'login'=>$type));
        }

		if(!empty($email_user)){
		 	//老用户登录绑定
            $password = md5(md5($post['password']).$email_user['login_stat']);
            if($email_user['password']!=$password){
                $data['flog']=0; 
				$data['msg']='您输入的密码与账号不匹配!'; 
				$data['data']=array(); 
				return_json($data);	
            }

            $update_data['name'] = $email_user['email'];
            $update_data['email'] = $email_user['email'];
            $update_data[$field] = $login_user[$field];

            if(empty($email_user['headimg'])){
                $update_data['headimg'] = $login_user['headimg'];
            }
            if(empty($email_user['nikename'])){
                $update_data['nikename'] = $login_user['nikename'];
            }
            
            $this->zf_user_model->update($update_data, 'id = ' . $email_user['id']);
            $this->zf_user_model->update(array($field => '',), 'id = ' . $login_user['id']);

            // 删除微信数据
            $this->zf_user_model->delete('id =' .$login_user['id']);

			//注册的账号登陆网站
			$this->session_login($email_user);

	        if($email_user['is_activate']==0){
				//给注册的邮箱发邮件
				$this->register_email($post['email']);
	        }

		}else{

            // 检查用户
            $login_stat = rand(11111, 99999);
            $new_password = md5(md5($post['password']) . $login_stat);
            $new_data = array(
                'name' => $post['email'],
                'email' => $post['email'],
                'login_stat' => $login_stat,
                'password' => $new_password,
            );
            $this->zf_user_model->update($new_data, 'id='.$post['login_uid']);

			//注册的账号登陆网站
			$this->session_login($login_user);

	        if($login_user['is_activate']==0){
				//给注册的邮箱发邮件
				$this->register_email($post['email']);
	        }

		}

		$data['flog']=1; 
		$data['msg']='绑定成功!'; 
		$data['data']=array(); 
		return_json($data);	
    }


}
