<?php
namespace Home\Controller;
use Think\Controller;
vendor('TaoBaoAPI.TopSdk');
class IndexController extends Controller {
    
    /**
     * 显示平台端首页
     */
    public function index(){
        $this->display();
    } 
    
    /**
     * 跳转登录页面
     */
    public function login(){
        $this->display();
    }
    /**
     * 生成验证码
     */
    public function verify(){
        $config =    array(
            'imageW'      =>    120,
            'imageH'      =>    34,
            'fontSize'    =>    14,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
        );
        $verify = new \Think\Verify($config);
        $verify->entry();
    }
    
    /**
     * 校验验证码
     */
    public function checkVertify(){
        $code = I('param.code');
        if (!check_verify($code)){
            $data = false;
            $this->ajaxReturn($data,'json');
        }else{
            $data = true;
            $this->ajaxReturn($data,'json');
        }
    }
    
    /**
     * 处理登录
     */
    public function dealLogin(){
        $phone = I('post.username');
        $password = I('post.password');
        $vertify = I('post.vertify');
        $rember = I('post.rember');
        
        $brand = 'colocker';
        $pass = $brand.$password;
        $user = M('user','lock_','DB_CONFIG1');
        $where['telNumber'] = $phone;
        $where['password'] = md5($pass);
        $info = $user->where($where)->select();
        if ($info){
             session('nickname',$info[0]['nickname']);
             session('userid',$info[0]['id']);
             if ($rember=="on"){
                 cookie('username',$phone,3600*24*7);
                 cookie('password',$password,3600*24*7);
             }
             $this->redirect('User/index');
        }else {
            $this->error('登录失败，请检查密码');
        }
    }
    
    /**
     * 校验电话号码
     */
    public function checkUser(){
        $phone = I('param.phone');
        $user = M('user','lock_','DB_CONFIG1');
        $where['telNumber'] = $phone;
        
        $info = $user->where($where)->find();
        if ($info){
            $data = 1;
            $this->ajaxReturn($data);     //表示号码被注册
        }elseif ($info==NULL){
            $data = 2;
            $this->ajaxReturn($data);      //表示号码未被注册
        }else{
            $data = 0;
            $this->ajaxReturn($data);      //表示查询错误
        }
    }
    
    /**
     * 跳转注册页面
     */
    public function registe(){
        $this->display();
    }

    /**
     * 方法名：sendSmsVertify
     * 参数：phone
     * 返回值：生成的验证码
     * 功能：发送短信验证码
     */
    public function sendSmsVertify()
    {
        $phone = I('param.phone');
        //获取四位随机数
        $code=rand(1000,9999);
        session(array("name"=>"$phone","expire"=>600));   //设置session名和10分钟有效
        session("$phone",$code);
        //调用短信验证码sdk
        $appkey = '23372839';
        $secret = '9e09cdf4ea74accb0b6a968979f7d9ad';
        $c = new \TopClient();
        $c->appkey = $appkey;
        $c->secretKey = $secret;
        $req = new \AlibabaAliqinFcSmsNumSendRequest();    //实例化验证码发送请求
        $req->setExtend("123456");                  //设置验证码公共回传参数
        $req->setSmsType("normal");                 //设置验证码样式
        $req->setSmsFreeSignName("CO团队");          //设置验证码签名
        $req->setSmsParam("{\"code\":\"$code\"}");  //设置验证码
        $req->setRecNum($phone);                    //设置接收验证码号码
        $req->setSmsTemplateCode("SMS_9965034");    //设置短信模板
        $resp = $c->execute($req);                  //执行请求
        if($resp){
            $this->ajaxReturn($code,'json');
        }
    }
    
    /**
     * 校验验证码
     */
    
    public function checkCode(){
        $phone = I('post.phone');
        $code = I('post.code');
        $code1 = session("$phone");
        if ($code == $code1){
            $data = true;
            $this->ajaxReturn($data,'json');
        }else {
            $data = false;
            $this->ajaxReturn($data,'json');
        }
    }
    
    /**
     * 处理注册
     */
    public function dealRegiste(){
        $phone = I('post.phone');
        $nick = I('post.nickname');
        $password = I('post.password');
        $vertify = I('post.vertify');
        $brand = 'colocker';
        $password = $brand.$password;
        $code = session("$phone");
        if ($code==$vertify){
            $user = M('user','lock_','DB_CONFIG1');
            $data['telNumber'] = $phone;
            $data['nickName'] = $nick;
            $data['password'] = md5($password);
            if ($user->add($data)){
                $this->success('注册成功',login);
            }else {
                $this->error('注册失败');
            }
        }else{
            $this->error('验证码输入错误');
        }
    }
    
    /**
     * 处理下载
     */
    
    public function download(){
        $filename = '/SmartLock/Public/app/Co-Locker.apk';
        header('content-disposition:attachment;filename='.basename($filename));
        header('content-length:'.filesize($filename));
        readfile($filename);
    }
}