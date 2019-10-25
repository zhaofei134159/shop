<?php
namespace app\admin\controller;

use app\admin\model\Chart_admin;
use app\admin\model\Chart_tag;
use app\admin\model\Chart_temp;
use app\admin\model\Chart_room;

use think\Session;

class Template extends Common
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
        if(!empty($post['name'])){
            $where['name'] = array('like','%'.$post['name'].'%'); 
        }
        if(isset($post['is_del'])&&$post['is_del']!='-1'){
            $where['is_del'] = $post['is_del'];
        }
        $temps = Chart_temp::where($where)->paginate(5, false, [
                        'query' => request()->param(),
                    ]);
        $page = $temps->render();



        $data = array(
                'temps'=>$temps,
                'page'=>$page,
                'post'=>$post,
            );
        return $this->view->fetch('index',$data);
    }

    public function edit(){
        $get = input('get.');

        $temp = array();
        if($get['type']==2){
            if(!empty($get['id'])){
                $temp = Chart_temp::where(['id' => $get['id']])->find();
            }
        }

        $data = array(
                'type'=>$get['type'],
                'temp'=>$temp,
            );
        return $this->view->fetch('edit',$data);
    }

    public function do_edit(){
        $post = input('post.');
        $temp_img = ROOT_PATH . 'public';
        $img_path  = 'uploads' . DS . 'temp';
        if(!is_dir($temp_img.DS.$img_path)){
            mkdir($img_path,0777);
        }
        
        $data = array();
        $data['name'] = $post['name'];
        $data['desc'] = $post['desc'];
        $data['content'] = $post['content'];
        $data['max_photo'] = $post['max_photo'];
        $data['utime'] = time();

        $css = request()->file('css');
        $js = request()->file('js');
        $img = request()->file('img');

        $file = array();
        if($post['type']!=2){
            $data['img'] = '';
            if(!empty($img)){
                $imginfo = $img->move($temp_img.DS.$img_path);
                $data['img'] = $img_path.DS.$imginfo->getSaveName();
            }
            if(!empty($css)){
                $cssinfo = $css->move($temp_img.DS.$img_path);
                $file['css'] = $img_path.DS.$cssinfo->getSaveName();
            }
            if(!empty($js)){
                $jsinfo = $js->move($temp_img.DS.$img_path);
                $file['js'] = $img_path.DS.$jsinfo->getSaveName();
            }

            $data['file_path'] = json_encode($file);
            $data['ctime'] = time();

            //写入文件
            $data['html_file'] = '';
            $data['html_file'] = $this->write_file($data);

            Chart_temp::create($data);
        }else{
            if(!empty($post['id'])){
                $temp = Chart_temp::where(['id' => $post['id']])->find();
                $data['file_path'] = $temp['file_path'];
                $data['html_file'] = $temp['html_file'];
                $file_path = json_decode($temp['file_path'],true);
                
                $file['css'] = empty($file_path['css'])?'':$file_path['css'];
                $file['js'] = empty($file_path['js'])?'':$file_path['js'];
                $data['img'] = $temp['img'];

                if(!empty($img)&&!empty($temp['img'])){
                    @unlink($temp_img.DS.$temp['img']);
                }
                if(!empty($css)&&!empty($file_path['css'])){
                    @unlink($temp_img.DS.$file_path['css']);
                }
                if(!empty($js)&&!empty($file_path['js'])){
                    @unlink($temp_img.DS.$file_path['js']);
                }
                if(!empty($img)){
                    $imginfo = $img->move($temp_img.DS.$img_path);
                    $data['img'] = $img_path.DS.$imginfo->getSaveName();
                }

                if(!empty($css)&&!empty($js)){
                    if(!empty($css)){
                        $cssinfo = $css->move($temp_img.DS.$img_path);
                        $file['css'] = $img_path.DS.$cssinfo->getSaveName();
                    }
                    if(!empty($js)){
                        $jsinfo = $js->move($temp_img.DS.$img_path);
                        $file['js'] = $img_path.DS.$jsinfo->getSaveName();
                    }
                    $data['file_path'] = json_encode($file);
                }

                $data['ctime'] = time();

                //写入文件
                $data['html_file'] = $this->write_file($data,$post['id']);

                Chart_temp::where(['id'=>$post['id']])->update($data);
            }
        }

        $this->redirect('template/index');
    }

    //写入文件
    public function write_file($data){
        $temp_img = ROOT_PATH . 'public';
        $img_path  = 'uploads' . DS . 'temphtml';
        if(!is_dir($temp_img.DS.$img_path)){
            mkdir($img_path,0777);
        }

        if(!empty($data['html_file'])){
            @unlink($temp_img.DS.$data['html_file']);
        }

        $file_path = json_decode($data['file_path'],true);    
        $html = '';
        if(isset($file_path['css'])&&!empty($file_path['css'])){
            $html .= '<link rel="stylesheet" href="'.DS.$file_path['css'].'">';
        }
        if(isset($file_path['js'])&&!empty($file_path['js'])){
            $html .= '<script type="text/javascript" src="'.DS.$file_path['js'].'"></script>'; 
        }
        $html .= $data['content'];

        $name = date('YmdHis').rand(100,999).substr(time(),6).'.html';
        $myfile = fopen($temp_img.DS.$img_path.DS.$name, "w");
        fwrite($myfile, $html);
        fclose($myfile);

        return $img_path.DS.$name;
    }

    public function del(){
        $id = input('get.id');
        $temp = Chart_temp::where(['id' => $id])->find();

        $is_del = 0;
        if(empty($temp['is_del'])){
            $is_del = 1;
        }

        Chart_temp::where(['id'=>$id])->update(['is_del'=>$is_del]);

        $this->redirect('template/index');
    }

    public function file_show(){
        $id = input('get.id');

        $temp = Chart_temp::where('id',$id)->find();

        $where = array();
        $where['uid'] = 0;
        $photos = Chart_room::where($where)->limit(0,$temp['max_photo'])->select();


        $data = array(
                'photos'=>$photos,
            );
        return $this->view->fetch('./'.$temp['html_file'],$data);
    }

}