<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Admin\Controller;
/**
 * 文件控制器
 * 主要用于下载模型的文件上传和下载
 */
class FileController extends AdminController {

    /* 文件上传 */
    public function upload(){
		$return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
		/* 调用文件上传组件上传文件 */
		$File = D('File');
		$file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
		$info = $File->upload(
			$_FILES,
			C('DOWNLOAD_UPLOAD'),
			C('DOWNLOAD_UPLOAD_DRIVER'),
			C("UPLOAD_{$file_driver}_CONFIG")
		);

        /* 记录附件信息 */
        if($info){
            $return['data'] = think_encrypt(json_encode($info['download']));
            $return['info'] = $info['download']['name'];
        } else {
            $return['status'] = 0;
            $return['info']   = $File->getError();
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }

    /* 下载文件 */
    public function download($id = null){
        if(empty($id) || !is_numeric($id)){
            $this->error('参数错误！');
        }

        $logic = D('Download', 'Logic');
        if(!$logic->download($id)){
            $this->error($logic->getError());
        }

    }

    /**
     * 上传图片
     * @author huajie <banhuajie@163.com>
     */
    public function uploadPicture(){
        //TODO: 用户登录检测

        /* 返回标准数据 */
        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');

        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload(
            $_FILES,
            C('PICTURE_UPLOAD'),
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$pic_driver}_CONFIG")
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if($info){
            $return['status'] = 1;
            $return = array_merge($info['download'], $return);
        } else {
            $return['status'] = 0;
            $return['info']   = $Picture->getError();
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }
    /**
     * 上传图片
     * @author huajie <banhuajie@163.com>
     */
    public function kindupload(){
        //TODO: 用户登录检测
        /* 上传路径检测 */
        $uploadpath = htmlspecialchars(I('uploadpath'));
        $hasfiles   = Amango_Scanfiles('Picture','array');
        $mainparam  = C('EDITOR_UPLOAD');
            if(in_array($uploadpath, $hasfiles)){
                $mainparam['savePath'] = $uploadpath.'/';
            } else {
                $return['error']  = 1;
                $return['info']   = '请检查目录是否存在';
                $this->ajaxReturn($return);
            }
        C('EDITOR_UPLOAD',$mainparam);
        /* 返回标准数据 */
        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
        
        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload(
            $_FILES,
            C('EDITOR_UPLOAD'),
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$pic_driver}_CONFIG")
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if($info){
            $return['error'] = 0;
            $return = array_merge($info['imgFile'], $return);
            $return['url'] = HEAD_URL.__ROOT__.$return['path'];
        } else {
            $return['error'] = 1;
            $return['info']   = $Picture->getError();
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }
    /* 文件上传 */
    public function kinguploadfile(){
        $return  = array('status' => 1, 'info' => '上传成功', 'data' => '');
        /* 调用文件上传组件上传文件 */
        $File = D('File');
        $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
        $info = $File->upload(
            $_FILES,
            C('DOWNLOAD_UPLOAD'),
            C('DOWNLOAD_UPLOAD_DRIVER'),
            C("UPLOAD_{$file_driver}_CONFIG")
        );
        /* 记录附件信息 */
        if($info){
            $saveparam = C('DOWNLOAD_UPLOAD');
            $return['error'] = 0;
            $return['url']   = HEAD_URL.__ROOT__.str_replace('.', '', $saveparam['rootPath']).$info['imgFile']['savepath'].$info['imgFile']['savename'];
        } else {
            $return['error'] = 1;
            $return['info']   = $Picture->getError();
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }
    //图片管理
    public function kindmanageimg(){
        $root_path = C('EDITOR_UPLOAD.rootPath');
        //根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
        //$root_url = $php_url . '../attached/';
        $root_url  = C('EDITOR_UPLOAD.rootPath');
        $ext_arr   = explode(',',C('EDITOR_UPLOAD.exts'));
        //根据path参数，设置各路径和URL
        if (empty($_GET['path'])) {
            $current_path     = realpath($root_path). '/';
            $current_url      = $root_url;
            $current_dir_path = '';
            $moveup_dir_path  = '';
        } else {
            $current_path     = realpath($root_path) . '/' . $_GET['path'];
            $current_url      = $root_url . $_GET['path'];
            $current_dir_path = $_GET['path'];
            $moveup_dir_path  = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
        }
        //排序形式，name or size or type
        $order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);
        //不允许使用..移动到上一级目录
        if (preg_match('/\.\./', $current_path)) {
            echo 'Access is not allowed.';
            exit;
        }
        //最后一个字符不是/
        if (!preg_match('/\/$/', $current_path)) {
            echo 'Parameter is not valid.';
            exit;
        }
        //目录不存在或不是目录
        if (!file_exists($current_path) || !is_dir($current_path)) {
            echo 'Directory does not exist.';
            exit;
        }
        //遍历目录取得文件信息
        $file_list = array();
        if ($handle = opendir($current_path)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if ($filename{0} == '.') continue;
                $file = $current_path . $filename;
                if (is_dir($file)) {
                    $file_list[$i]['is_dir']   = true; //是否文件夹
                    $file_list[$i]['has_file'] = ($this->scan_dir($file) > 2); //文件夹是否包含文件
                    $file_list[$i]['filesize'] = 0; //文件大小
                    $file_list[$i]['is_photo'] = false; //是否图片
                    $file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $file_list[$i]['is_dir'] = false;
                    $file_list[$i]['has_file'] = false;
                    $file_list[$i]['filesize'] = filesize($file);
                    $file_list[$i]['dir_path'] = '';
                    $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
                    $file_list[$i]['filetype'] = $file_ext;
                }
                $file_list[$i]['filename'] = $filename; //文件名，包含扩展名
                $file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($handle);
        }
        //将采用匿名函数
        usort($file_list, function($a, $b){
                global $order;
                if ($a['is_dir'] && !$b['is_dir']) {
                    return -1;
                } else if (!$a['is_dir'] && $b['is_dir']) {
                    return 1;
                } else {
                    if ($order == 'size') {
                        if ($a['filesize'] > $b['filesize']) {
                            return 1;
                        } else if ($a['filesize'] < $b['filesize']) {
                            return -1;
                        } else {
                            return 0;
                        }
                    } else if ($order == 'type') {
                        return strcmp($a['filetype'], $b['filetype']);
                    } else {
                        return strcmp($a['filename'], $b['filename']);
                    }
                }
        });
        $result = array();
        //相对于根目录的上一级目录
        $result['moveup_dir_path']  = $moveup_dir_path;
        //相对于根目录的当前目录
        $result['current_dir_path'] = $current_dir_path;
        //当前目录的URL
        $result['current_url']      = $current_url;
        //文件数
        $result['total_count']      = count($file_list);
        //文件列表数组
        $result['file_list']        = $file_list;
        /* 返回JSON数据 */
        $this->ajaxReturn($result);
    }
    public function scan_dir($res){
        $dir=opendir($res);
        readdir($dir);
        readdir($dir);
        $Count=0;
        while($filename=readdir($dir)){
            $New_path=$res.'/'.$filename;
            if(is_dir($New_path)){
                $Count+=$this->scan_dir($New_path);
            }else{
                $Count++;
            }
        }
        closedir($dir);
        return $Count;
    }
    //图片管理
    public function kindmanagefile(){
        $root_path = C('DOWNLOAD_UPLOAD.rootPath');
        //根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
        //$root_url = $php_url . '../attached/';
        $root_url  = C('DOWNLOAD_UPLOAD.rootPath');
        $ext_arr   = explode(',',C('DOWNLOAD_UPLOAD.exts'));
        //根据path参数，设置各路径和URL
        if (empty($_GET['path'])) {
            $current_path     = realpath($root_path). '/';
            $current_url      = $root_url;
            $current_dir_path = '';
            $moveup_dir_path  = '';
        } else {
            $current_path     = realpath($root_path) . '/' . $_GET['path'];
            $current_url      = $root_url . $_GET['path'];
            $current_dir_path = $_GET['path'];
            $moveup_dir_path  = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
        }
        //排序形式，name or size or type
        $order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);
        //不允许使用..移动到上一级目录
        if (preg_match('/\.\./', $current_path)) {
            echo 'Access is not allowed.';
            exit;
        }
        //最后一个字符不是/
        if (!preg_match('/\/$/', $current_path)) {
            echo 'Parameter is not valid.';
            exit;
        }
        //目录不存在或不是目录
        if (!file_exists($current_path) || !is_dir($current_path)) {
            echo 'Directory does not exist.';
            exit;
        }
        //遍历目录取得文件信息
        $file_list = array();
        if ($handle = opendir($current_path)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if ($filename{0} == '.') continue;
                $file = $current_path . $filename;
                if (is_dir($file)) {
                    $file_list[$i]['is_dir']   = true; //是否文件夹
                    $file_list[$i]['has_file'] = 1; //文件夹是否包含文件
                    $file_list[$i]['filesize'] = 0; //文件大小
                    $file_list[$i]['is_photo'] = false; //是否图片
                    $file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $file_list[$i]['is_dir'] = false;
                    $file_list[$i]['has_file'] = false;
                    $file_list[$i]['filesize'] = filesize($file);
                    $file_list[$i]['dir_path'] = '';
                    $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
                    $file_list[$i]['filetype'] = $file_ext;
                }
                $file_list[$i]['filename'] = $filename; //文件名，包含扩展名
                $file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($handle);
        }
        //将采用匿名函数
        usort($file_list, function($a, $b){
                global $order;
                if ($a['is_dir'] && !$b['is_dir']) {
                    return -1;
                } else if (!$a['is_dir'] && $b['is_dir']) {
                    return 1;
                } else {
                    if ($order == 'size') {
                        if ($a['filesize'] > $b['filesize']) {
                            return 1;
                        } else if ($a['filesize'] < $b['filesize']) {
                            return -1;
                        } else {
                            return 0;
                        }
                    } else if ($order == 'type') {
                        return strcmp($a['filetype'], $b['filetype']);
                    } else {
                        return strcmp($a['filename'], $b['filename']);
                    }
                }
        });
        $result = array();
        //相对于根目录的上一级目录
        $result['moveup_dir_path']  = $moveup_dir_path;
        //相对于根目录的当前目录
        $result['current_dir_path'] = $current_dir_path;
        //当前目录的URL
        $result['current_url']      = $current_url;
        //文件数
        $result['total_count']      = count($file_list);
        //文件列表数组
        $result['file_list']        = $file_list;
        /* 返回JSON数据 */
        $this->ajaxReturn($result);
    }
}
