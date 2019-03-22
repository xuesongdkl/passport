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
            setcookie('uid',$uid,time()+86400,'/','vm.lening.com',false,true);
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
        return view('users.login');
    }

    public function dologin(Request $request){
        $name=$request->input('u_name');
        $pwd=$request->input('u_pwd');
        $res=UserModel::where(['name'=>$name])->first();
        if($res){
            if(password_verify($pwd,$res->password)){

                $token = substr(md5(time().mt_rand(1,99999)),10,10);
                setcookie('name',$res->name,time()+86400,'/','',false,true);
                setcookie('uid',$res->uid,time()+86400,'/','',false,true);
                setcookie('token',$token,time()+86400,'/','',false,true);
                $redis_key_web_token='str:u:token:web:'.$res->uid;
                Redis::set($redis_key_web_token,$token);
                Redis::expire($redis_key_web_token,86400);       //设置过期时间
//                $request->session()->put('uid',$res->uid);
//                $request->session()->put('p_token',$token);
                header('refresh:1;url=/');
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
        setcookie('uid',null);
        setcookie('token',null);
        setcookie('name',null);
        echo "请重新登录,正在跳转";
        header('Refresh:1;url=/userlogin');
    }

    //passport登录
    public function paslogin(){
        $data=$_POST['data'];
        $data=json_decode($data);
        $where=[
            'name' => $data['u_name']
        ];
        $res=UserModel::where($where)->find();
        if($res){
            if(password_verify($data['u_pwd'],$res->password)){
                $token = substr(md5(time().mt_rand(1,99999)),10,10);
                setcookie('name',$res->name,time()+86400,'/','',false,true);
                setcookie('uid',$res->uid,time()+86400,'/','',false,true);
                setcookie('token',$token,time()+86400,'/','',false,true);
                $redis_key_web_token='str:u:token:web:'.$res->uid;
                Redis::set($redis_key_web_token,$token);
                Redis::expire($redis_key_web_token,86400);       //设置过期时间
                header('refresh:1;url=/');
                echo "登录成功";die;
            }else{
                echo "账号或者密码错误";die;
            }
        }else{
            echo "用户不存在";die;
        }
    }
}
