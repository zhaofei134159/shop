<?php
namespace app\index\controller;

use app\index\model\Chart_user;
use app\index\model\Chart_room;
use app\index\model\Chart_temp;
use app\index\model\Chart_user_temp;

use think\Session;


class Ucenter extends Common
{
	public function __construct(){
		parent::__construct();
		
        if(!Session::get('login_id','fei_chart')){
        	$this->redirect('login/login');
        }
	}

    public function index()
    {
        $user = Chart_user::get(['id'=>Session::get('login_id','fei_chart')]);
        $data = array(
                'user'=>$user
            );
        return $this->view->fetch('index',$data);
    }

    public function user_info(){
        $head = ROOT_PATH . 'public';
        $head_path = 'uploads' . DS . 'headimg';

        $post = input('post.');
        $file = request()->file('headimg');
        if($file){
            $user = Chart_user::get(['id'=>Session::get('login_id','fei_chart')]);
            if(!empty($user->headimg)){
                unlink($head.DS.$user->headimg);
            }
            $info = $file->move($head.DS.$head_path);
            $post['headimg'] = $head_path.DS.$info->getSaveName();
        }

        $post['utime'] = time();

        Chart_user::where('id',Session::get('login_id','fei_chart'))->update($post);

        if(!empty($post['name'])){
            Session::set('login_name',$post['name'],'fei_chart');
        }else{
            Session::delete('login_name','fei_chart');
        }

        $this->redirect('ucenter/index');
    }

    //用户上传图片
    public function user_upload(){

        $charts = Chart_room::where('uid',Session::get('login_id','fei_chart'))->where('is_del',0)->select();

        $data = array(
                'charts'=>$charts,
            );
        return $this->view->fetch('user_upload',$data);
    }

    public function upload_user_chart(){
        $data = array();
        $chart_img = ROOT_PATH . 'public';
        $img_path  = 'uploads' . DS . 'chart';

        $file = request()->file('path');
        if($file){
            $info = $file->move($chart_img.DS.$img_path);
            $data['path'] = $img_path.DS.$info->getSaveName();
            $data['uid'] = Session::get('login_id','fei_chart');
            $data['create_time'] = time();
            Chart_room::create($data);
        }

        $this->redirect('ucenter/user_upload');
    }

    public function chart_del(){
        $id = input('post.id');

        $chart = Chart_room::where('id',$id)->find();
        if(empty($chart)){
            return json(['flog'=>0,'msg'=>'找不到']);
        }

        // $chart_url =  ROOT_PATH . 'public';
        // unlink($chart_url.DS.$chart['path']);
        
        Chart_room::where(['id' => $id])->update(array('is_del'=>1));

        return json(['flog'=>1, 'msg'=>'成功!']);
    }


    //模板
    public function user_temp(){
        $where = array();
        $where['uid'] = Session::get('login_id','fei_chart');
        $where['chart_user_temp.is_del'] = 0;

        $field = 'chart_user_temp.id,chart_user_temp.temp_id,chart_user_temp.photos,chart_user_temp.name as my_name,chart_user_temp.ctime,chart_temp.name as temp_name';
        $join = array();
        $join[] = array('chart_temp','chart_temp.id = chart_user_temp.temp_id','left');
        $user_temp = Chart_user_temp::join($join)->where($where)->field($field)->paginate(10, false, [
                        'query' => request()->param(),
                    ]);
        $page = $user_temp->render();

        $data = array(
                'user_temp'=>$user_temp,
                'page'=>$page,
            );
        return $this->view->fetch('ucenter/user_temp',$data);
    }

    public function add_temp(){
        $tem_where = array();
        $tem_where['is_del'] = 0;
        $temps = Chart_temp::where($tem_where)->select();

        $pho_where = array();
        $pho_where['uid'] = Session::get('login_id','fei_chart');

        $photos = Chart_room::where($pho_where)->select();


        $data = array(
                'temps'=>$temps,
                'photos'=>$photos,
            );
        return $this->view->fetch('ucenter/add_temp',$data);
    }

    public function do_edit_temp(){
        $post = input('post.');

        $data = array();
        $data['name'] = $post['name'];
        $data['uid'] = Session::get('login_id','fei_chart');
        $data['temp_id'] = $post['input_temp'];
        $data['photos'] = json_encode($post['input_photo']);
        $data['ctime'] = time();
        $data['utime'] = time();

        Chart_user_temp::create($data);

        return json(['flog'=>1, 'msg'=>'成功!','data'=>$data]);
    }

    public function user_temp_show(){
        $path = ROOT_PATH . 'public';

        $id = input('id');

        if(!isset($id)||empty($id)){
             return json(['flog'=>0, 'msg'=>'地址错误!']);
        }

        $user_temp = Chart_user_temp::get(['id'=>$id]);
        if(empty($user_temp)){
             return json(['flog'=>0, 'msg'=>'找不到对应模板!']);
        }

        $temp = Chart_temp::where('id',$user_temp['temp_id'])->find();

        $photos = Chart_room::where('id','in',json_decode($user_temp['photos'],true))->where('uid',Session::get('login_id','fei_chart'))->select();
        $data = array('photos'=>$photos,);
        $html = $this->view->fetch('./'.$temp['html_file'],$data);

        //下载
        $res = array(
                    'html'=>$html,
                    'user_temp'=>$user_temp,
                );
        return json(['flog'=>1, 'msg'=>'成功!','data'=>$res]);
    }

    public function del_temp_user(){
        $id = input('get.id');
        if(!isset($id)||empty($id)){
             $this->redirect('ucenter/user_temp');
        }

        $user_temp = Chart_user_temp::get(['id'=>$id]);
        if(empty($user_temp)){
             $this->redirect('ucenter/user_temp');
        }

        Chart_user_temp::where(['id' => $id])->update(array('is_del'=>1));

        $this->redirect('ucenter/user_temp');
    }

}