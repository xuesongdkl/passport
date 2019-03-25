<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\UserModel;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    //注册
    public function reg(){
        return view('users.reg');
    }

    public function doreg(Request $request){
        $name=$request->input('u_name');
        $where=[
            'name'=>$name
        ];
        $userinfo=UserModel::where($where)->first();
        if($userinfo) {
            die("该用户已存在");
        }
        $pwd1 = $request->input('u_pwd1');
        $pwd2 = $request->input('u_pwd2');
        if($pwd1 !== $pwd2){
            echo "密码不一致";
            header("refresh:1;url=/userreg");
        }
        $pwd = password_hash($pwd1,PASSWORD_BCRYPT);
        $data=[
            'name'=>$name,
            'password'=>$pwd,
            'email'=>$request->input('u_email'),
            'age'=>$request->input('u_age'),
            'reg_time'=>time()
        ];
        $uid=UserModel::insertGetId($data);
        if($uid){
            setcookie('uid',$uid,time()+86400,'/','',false,true);
//            $redis_key_web_token='str:u:token:web:'.$uid;
            header("refresh:2;url=/userlogin");
            echo "注册成功,正在跳转";
        }else{
            echo "注册失败";die;
            header("refresh:2;url=/userreg");
        }
    }

    //登录
    public function login(){
        $redirect=$_GET['redirect'] ?? env('SHOP_URL');
        $data=[
            'redirect'  =>$redirect
        ];
        return view('users.login',$data);
    }

    public function dologin(Request $request){
        $name=$request->input('u_name');
        $pwd=$request->input('u_pwd');
        $aaa=$request->input('redirect')?? env('SHOP_URL');
//        echo $aaa;die;
        $res=UserModel::where(['name'=>$name])->first();
        if($res){
            if(password_verify($pwd,$res->password)){

                $token = substr(md5(time().mt_rand(1,99999)),10,10);
                setcookie('name',$res->name,time()+86400,'/','52self.cn',false,true);
                setcookie('uid',$res->uid,time()+86400,'/','52self.cn',false,true);
                setcookie('token',$token,time()+86400,'/','52self.cn',false,true);
//                $request->session()->put('uid',$res->uid);
//                $request->session()->put('token',$token);

//                $redis_key_web_token='str:u:token:web:'.$res->uid;
//                Redis::set($redis_key_web_token,$token);
//                Redis::expire($redis_key_web_token,86400);       //设置过期时间

                $redis_key_web_token='str:u:token:'.$res->uid;
                Redis::del($redis_key_web_token);
                Redis::hSet($redis_key_web_token,'web',$token);


//                echo $redis_key_web_token;die;
                header("refresh:1;$aaa");
                echo "登录成功";die;
            }else{
                echo "账号或者密码错误";die;
            }
        }else{
            echo "用户不存在";die;
        }
    }
    public function center(Request $request){

//        if($_COOKIE['token'] != $request->session()->get('p_token')) {
//            die('非法请求');
//        }else {
//            echo "正常请求";
//        }

        if(empty($_COOKIE['name'])){
            header('refresh:1;url=/userlogin');
            echo "请先登录";
            die;
        }else{
            $list=UserModel::all()->toArray();
            $data=[
                'list'=>$list
            ];
            return view('users.list',$data);
        }
    }

    //退出
    public function quit(){
        setcookie('uid',null,time()-1,'/','52self.cn',false,true);
        setcookie('token',null,time()-1,'/','52self.cn',false,true);
        setcookie('name',null,time()-1,'/','52self.cn',false,true);
        echo "请重新登录,正在跳转";
        header('Refresh:1;url=/userlogin');
    }

    //passport登录
    public function paslogin(Request $request){
        $u_name=$request->input('u_name');
        $u_pwd=$request->input('u_pwd');
        $where=[
            'name' => $u_name
        ];
        $res=UserModel::where($where)->first();
        if($res){
            if(password_verify($u_pwd,$res->password)){
                $token = substr(md5(time().mt_rand(1,99999)),10,10);
                $redis_key_web_token='str:u:token:'.$res->uid;
                Redis::del($redis_key_web_token);
                Redis::hSet($redis_key_web_token,'app',$token);        //设置过期时间
                $response=[
                    'errno'  =>   0,
                    'msg'    =>   'ok',
                    'token'  =>   $token,
                    'uid'    =>   $res->uid,
                    'user'   =>   $res->name
                ];
            }else{
                $response=[
                    'errno'  =>   40003,
                    'msg'    =>   '账号或者密码错误'
                ];
            }
        }else{
            $response=[
                'errno'  =>   40001,
                'msg'    =>   '用户不存在'
            ];
        }
        return $response;
    }

    //passport注册
    public function pasregister(Request $request){
        $u_name=$request->input('u_name');
        $u_email=$request->input('u_email');
        $u_age=$request->input('u_age');
        $where=[
            'name' => $u_name
        ];
        $userinfo=UserModel::where($where)->first();
        if($userinfo) {
            $response=[
                'errno'  =>   40003,
                'msg'    =>   '该用户已存在'
            ];
        }
        $pwd1 = $request->input('u_pwd1');
        $pwd2 = $request->input('u_pwd2');
        if($pwd1 !== $pwd2){
            $response=[
                'errno'  =>   40003,
                'msg'    =>    '确认密码需和密码一致'
            ];
        }
        $pwd = password_hash($pwd1,PASSWORD_BCRYPT);
        $data=[
            'name'      =>      $u_name,
            'password'  =>      $pwd,
            'email'     =>      $request->input('u_email'),
            'age'       =>      $request->input('u_age'),
            'reg_time'  =>      time()
        ];
        $uid=UserModel::insertGetId($data);
        if($uid){
            setcookie('uid',$uid,time()+86400,'/','52self.cn',false,true);
            $token = substr(md5(time().mt_rand(1,99999)),10,10);
            $redis_key_web_token='str:u:token:'.$uid->uid;
            Redis::del($redis_key_web_token);
            Redis::hSet($redis_key_web_token,'app',$token);        //设置过期时间
            $response=[
                'errno'  =>   0,
                'msg'    =>   'ok',
                'token'  =>   $token,
                'uid'    =>   $uid->uid,
                'user'   =>   $uid->name
            ];
        }else{
            $response=[
                'errno'  =>   40001,
                'msg'    =>   '用户不存在'
            ];
        }
        return $response;
    }
}
