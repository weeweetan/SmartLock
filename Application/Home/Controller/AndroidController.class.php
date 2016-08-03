<?php
namespace Home\Controller;
use Think\Controller;
use Think\Session\Driver\Memcache;

vendor('TaoBaoAPI.TopSdk');
vendor('phpqrcode.phpqrcode');
require_once '/vendor/autoload.php';
class AndroidController extends Controller{
    
    /**
     * 方法名：report
     * 参数：device
     * 返回值：无
     * 功能：获取设备最近地理位置信息
     * 输出：json格式的设备最近地理位置
     */
    public function report()
    {
        $deviceNo = I('param.device');    //获取设备序列号
        $address = M('position','lock_','DB_CONFIG1'); //实例化position表
        $where['device_serialNumber'] = $deviceNo;  //初始化查询条件
        $info = $address->where($where)->order('id desc')->limit(0,1)->field('id,lng,lat,reason')->select();
        if ($info){
            static $key = 0;
            $id = $info[0]['id'];
            $lng = $info[0]['lng'];
            $lat = $info[0]['lat'];
            $reason = $info[0]['reason']; 
            $data[$key]['id'] = $id;
            $data[$key]['lng'] = $lng;
            $data[$key]['lat'] = $lat;
            $data[$key]['reason'] = $reason;
            echo json_encode($data);
        }else {
            static $key = 0;
            $data[$key]['key'] = -1;
            echo json_encode($data);
        }
    }
    
    /**
     * 方法名login
     * 功能：处理用户登录
     */
    
    public function login()
    {
        $phone = I('param.phone');
        $password = I('param.password');
        $brand = 'colocker';
        if ($phone&&$password){
            $user = M('user','lock_','DB_CONFIG1');
            $password = $brand.$password;
            $where['phone'] = $phone;
            $where['password'] = md5($password);
            $info = $user->where($where)->select();
            if ($info){
                static $k = 0;
                $data[$k]['id'] = $info[0]['id'];
                $data[$k]['nickname'] = $info[0]['nickname'];
                $data[$k]['name'] = $info[0]['name'];
                $data[$k]['image'] = $info[0]['image'];
                $data[$k]['password'] = $info[0]['password'];
                $data[$k]['telnumber'] = $info[0]['telnumber'];
                $data[$k]['studentnumber'] = $info[0]['studentnumber'];
                $data[$k]['gender'] = $info[0]['gender'];
                $data[$k]['relevancenumber'] = $info[0]['relevancenumber'];
                $data[$k]['registertime'] = $info[0]['registertime'];
                
                echo json_encode($data);
            }else{
                static $k = 0;
                $data[$k]['key'] = 0;   //表示密码错误
                echo json_encode($data);
            }
        }else {
            static $k = 0;
            $data[$k]['key'] = -1;   //表示参数缺少
            echo json_encode($data);
        }
    }
    
    /**
     * 检查电话号码是否被使用
     */
    public function checkUser()
    {
        $phone = I('param.phone');
        $user = M('user','lock_','DB_CONFIG1');
        $where['telNumber'] = $phone;
    
        $info = $user->where($where)->find();
        if ($info){
            static $k = 0;
            $data[$k]['key'] = 1;
            echo json_encode($data);      //表示电话号码被注册
        }elseif ($info==NULL){
            static $k = 0;
            $data[$k]['key'] = 2;
            echo json_encode($data);       //表示电话号码未被注册
        }else{
            static $k = 0;
            $data[$k]['key'] = 0;
            echo json_encode($data);       //表示查询错误
        }
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
        session(array("name"=>"$phone","expire"=>30));   //设置session名和10分钟有效
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
    }
    
    /**
     * 校验验证码
     */
    
    public function checkCode()
    {
        $phone = I('post.phone');
        $code = I('post.code');
        $code1 = session("$phone");
        if ($code == $code1){
            static $k = 0;
            $data[$k]['key'] = 1;   //表示验证码正确
            echo json_encode($data);
        }else {
            static $k = 0;
            $data[$k]['key'] = 0;   //表示验证错误
            echo json_encode($data);
        }
    }
    
    /**
     * 注册
     */
    public function registe()
    {
        $phone = I('param.phone');
        $nick = I('param.nickname');
        $password = I('param.password');
        $vertify = I('param.vertify');
        $brand = 'colocker';
        $password = $brand.$password;
        $code = session("$phone");
        if ($code==$vertify){
            $user = M('user','lock_','DB_CONFIG1');
            $data['telNumber'] = $phone;
            $data['nickName'] = $nick;
            $data['password'] = md5($password);
            if ($user->add($data)){
                static $k = 0;
                $info[$k]['key'] = 1;  //表示成功
                echo json_encode($info);
            }else {
                static $k = 0;
                $info[$k]['key'] = 0;//表示注册失败
                echo json_encode($info);
            }
        }else{
            static $k = 0;
            $data[$k]['key'] = -1; //表示验证码错误
            echo json_encode($data);
        }
    }
    
    /**
     * 获取验证码
     */
    
    public function getVertify()
    {
        header('content-type:image/gif');
        $cookie = get_cookie();
        $vertify = get_vertify($cookie[1][0]);
        echo  $vertify;
    }
    
    /**
     * 模拟登录
     */
    public function simulateLogin()
    {
        $id = I('param.id');
        $college = I('param.college');
        $xh = I('param.xh');
        $password = I('param.password');
        $name = I('param.name');
        $vertify = I('param.vertify');
        
        $cookie = get_cookie();
        $res = moniLogin($cookie[1][0], $xh, $password, $vertify);
        switch ($res){
            case '1':
                $user = M('user','lock_','DB_CONFIG1');
                $where['id'] = $id;
                $data['studentNumber'] = $xh;
                $data['name'] = $name;
                $row = $user->where($where)->save($data);
                if ($row===1){
                    static $k = 0;
                    $info[$k]['key'] = 1;
                    echo json_encode($info);   //表示校验成功
                }
                break;
            case '-1':
                static $k = 0;
                $info[$k]['key'] = -1;
                echo json_encode($info);  //表示验证码错误
                break;
            default:
                static $k = 0;
                $info[$k]['key'] = 0;
                echo json_encode($info);      //表示出现未知错误
                break;
        }
    }

    /**
     * 获取二维码
     */
    public function getQrcode()
    {

        $data = 'http://www.weespice.cn/tw/SmartLock/';
        $filename = 'index';
        /* echo qrcode($data, $filename); */
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 4;
        \QRcode::png($data, false, $level, $size);
    }

    /**
     *
     * 添加自行车
     */
    public function addBike()
    {
        $id = I('param.id');
        $bikeinfo = I('param.bike');
        $start = I('param.start');
        $end = I('param.end');
        $rental = I('param.rental');
        $upload = new \Think\Upload();
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Public/'; // 设置附件上传根目录
        $upload->savePath  =     'images/'; // 设置附件上传（子）目录        $upload
        $info = $upload->upload();
        $photopath="/SmartLock/Public/".$info['image']['savepath'].$info['image']['savename'];
        
        if ($info){
            if ($id&&$bikeinfo&&$start&&$end){
                $data['owner_id'] = $id;
                $data['bikeinfo'] = $bikeinfo;
                $data['image'] = $photopath;
                $data['startTime'] = $start;
                $data['endTime'] = $end;
                $data['rental'] = $rental;
                
                $bike = M('bike','lock_','DB_CONFIG1');
                if ($bike->add($data)){
                    static $k = 0;
                    $data1[$k]['key'] = 1;
                    json_encode($data1);   //表示添加成功
                }else {
                    static $k = 0;
                    $data1[$k]['key'] = 0;
                    json_encode($data1);    //表示添加失败
                }
            }else {
                static $k = 0;
                $data[$k]['key'] = -2;
                json_encode($data);   //表示参数缺失
            }
        }else {
            static $k = 0;
            $data[$k]['key'] = -1;
            echo json_encode($data);     //表示图片上传错误
        }
    }
    
    /**
     * 查看我的自行车
     */
    public function getBike()
    {
        $id = I('param.id');
        $where['owner_id'] = $id;
        
        $bike = M('bike','lock_','DB_CONFIG1');
        $info = $bike->where($where)->select();
        switch ($info){
            case NULL:
                static $k = 0;
                $data[$k]['key'] = 0;
                echo json_encode($data);    //表示没有自行车数据
                break;
            case FALSE:
                static $k = 0;
                $data[$k]['key'] = -1;
                echo json_encode($data);    //表示出现查询错误
                break;
            default:
                static $k = 0;
                
                $data[$k]['id'] = $info[0]['id'];
                $data[$k]['bikeinfo'] = $info[0]['bikeinfo'];
                $data[$k]['image'] = $info[0]['image'];
                $data[$k]['startTime'] = $info[0]['startTime'];
                $data[$k]['endTime'] = $info[0]['endTime'];
                
                echo json_encode($data);
                break;
        }
    }

    /**
     * 获取租金
     */
    public function getRental()
    {
        
    }

    /**
     * 申请借车
     */
    public function lendBike()
    {
        $user_id = I('param.user_id');   //借车人id
        $owner_id = I('param.owner_id');  //车主id
        $length = I('param.length');       //租车时长
//        $end = I('param.end');            //结束时间

        $user_length = $user_id.'length';
//        $user_end = $user_id.'end';

        S($user_length,$length);
//        S($user_end,$end);

        $user = M('user','lock_','DB_CONFIG1');
        $where['id'] = $user_id;
        $search['id'] = $owner_id;
        $user_info = $user->where($where)->find();
        $owner_info = $user->where($search)->find();
        $alias = $owner_info['alias'];
        if ($user_info&&$alias)
        {
            if (!S($user_id)&&S($owner_id))
            {
                S($user_id,$user_info);
                S($owner_id,$owner_info);
            }
            $length1 = $length/3600;
            $title = '尊敬的用户您好';
            $content = '用户'.$user_info['name'].'请求租借您的自行车，时长为'.$length1.'小时';

            sendNotification($title,$content,$alias,$user_info);
        }

    }

    /**
     *车主确认借车
     */
    public function sureLend()
    {
        $owner_id = I('param.owner_id');
        $uer_id = I('param.user_id');
        $flag = I('param.flag');

        switch ($flag){
            case false:
            case '0':
                if (!is_null(S($uer_id)))
                {
                    $user_info = S($uer_id);
                    $alias = $user_info['alias'];
                }else{
                    $user = M('user','lock_','DB_CONFIG1');
                    $user_info = $user->where("id=$uer_id")->find();
                    $alias = $user_info['alias'];
                }
                $title = '尊敬的用户您好';
                $content = '车主拒绝了您的请求';

                sendNotification($title,$content,$alias,$user_info);
                break;
            case true:
            case '1':
                if (!is_null(S($uer_id)))
                {
                    $user_info = S($uer_id);
                    $alias = $user_info['alias'];
                } else {
                    $user = M('user','lock_','DB_CONFIG1');
                    $user_info = $user->where("id=$uer_id")->find();
                    $alias = $user_info['alias'];
                    }
                $code = rand(1000,9999);
                $user_rand = $uer_id.'rand';
                $user_length = $uer_id.'length';
                if (S($user_length))
                {
                    $length = S($user_length);
                }
                session(array("name"=>"$user_rand","expire"=>$length));   //设置session名
                session($user_rand,$code);
                $title = '尊敬的用户您好';
                $content = '车主同意您的请求';
                $pass = array('password'=>$code);
                sendNotification($title,$content,$alias,$pass);          //发送推送通知
                $rent = M('rent','lock_','DB_CONFIG1');
                $data['owner_id'] = $owner_id;
                $data['rentNumber'] = $uer_id;
                $data['rentTime'] = date('Y-m-d H:i:s',time());
                $data['length'] = $length;
                $last_id = $rent->add($data);
                if (!is_null($last_id))
                {
                    static $k = 0;
                    $data1[$k]['last_id'] = $last_id;
                    S($last_id,$data);

                    echo json_encode($data1);
                }else{
                    static $k = 0;
                    $data1[$k]['key'] = -1;

                    echo json_encode($data1);
                }
                break;
        }

    }

    /**
     * 校验随机密码
     */
    public function checkRand()
    {
        $code = I('param.pass');
        $user_id = I('param.uer_id');
        $user_rand = $user_id.'rand';
        $pass = session($user_rand);

        if (is_null($pass))
        {
            static $key = 0;
            $data[$key]['key'] = -1;
            echo json_encode($data);     //表示密码失效
        }
        if ($code==$pass)
        {
            static $key = 0;
            $data[$key]['key'] = 1;
            echo json_encode($data);     //表示匹配成功
        }
    }

    /**
     * 还车
     */
    public function returnBike()
    {
        $rent_id = I('param.rent_id');
        $rent = M('rent','lock_','DB_CONFIG1');
        $where['id'] = $rent_id;
        if (is_null(S($rent_id)))
        {
            $rentinfo = $rent->where($where)->select();
            $owner_id = $rentinfo[0]['owner_id'];
            $user_id = $rentinfo[0]['rentNumber'];
            $rentTime = $rentinfo[0]['rentTime'];
            $length = $rentinfo[0]['length'];
        }
        else
        {
            $rentinfo = S($rent_id);
            $owner_id = $rentinfo['owner_id'];
            $user_id = $rentinfo['rentNumber'];
            $rentTime = $rentinfo['rentTime'];
            $length = $rentinfo['length'];
        }

        $lendTime = strtotime($rentTime);
        $now = time();
        $returnTime = date('Y-m-d H:i:s',$now);
        $len = number_format(($now-$lendTime)/3600,1);
        if ($len!=$length)
        {
            $length = $len;
        }
        $bike = M('bike','lock_','DB_CONFIG1');
        $bike_where['owner_id'] = $owner_id;
        $bikeinfo = $bike->where($bike_where)->field();
        $rental = $bikeinfo['rental'];
        $rental = $rental*$length;

        $data['returnTime'] = $returnTime;
        $data['rental'] = $rental;
        $data['isPay'] = false;
        $data['length'] = $length;

        if ($rent->where($where)->save($data)===false)
        {
            static $k = 0;
            $data1[$k]['key'] = -1;    //表示还车失败

            echo json_encode($data1);
        }
        else
        {
            $user_length = $user_id.'length';
            S($user_length,null);
            static $k = 0;
            $data1[$k]['key'] = 1;

            echo json_encode($data1);    //表示成功
        }
    }

    /**
     * 查看附近车辆
     */
    public function viewNearby()
    {
        $lng = I('param.lng');
        $lat = I('param.lat');

        $position = M('position','lock_','DB_CONFIG1');
        $device = M('device','lock_','DB_CONFIG1');
        $bike = M('bike','lock_','DB_CONFIG1');
        $user = M('user','lock_','DB_CONFIG1');

        $info = $position
            ->query("select a.* from lock_position a,(select max(id) as id,device_serialNumber from lock_position group by device_serialNumber) b
where a.id=b.id and a.device_serialNumber=b.device_serialNumber and a.reason=1");

            foreach ($info as $key => $value){
                $lng1 = $value['lng'];
                $lat1 = $value['lat'];
                $distance = returnDistance($lat,$lng,$lat1,$lng1);
                if ($distance<=1){
                    static $k = 0;
                    $serialNumber = $value['device_serialnumber'];
                    $device_where['serialNumber'] = $serialNumber;
                    $deviceinfo = $device->where($device_where)->find();

                    $id = $deviceinfo['owner_id'];
                    $user_where['id'] = $id;
                    $userinfo = $user->where($user_where)->select();
                    $bike_where['owner_id'] = $id;
                    $bikeinfo = $bike->where($bike_where)->select();

                    /*dump($userinfo);*/
                    $data[$k]['id'] = $userinfo[0]['id'];
                    $data[$k]['nickname'] = $userinfo[0]['nickname'];
                    $data[$k]['name'] = $userinfo[0]['name'];
                    $data[$k]['image'] = $userinfo[0]['image'];
                    $data[$k]['telNumber'] = $userinfo[0]['telNumber'];
                    $data[$k]['bike_id'] = $bikeinfo[0]['id'];
                    $data[$k]['bike_owner'] = $bikeinfo[0]['owner_id'];
                    $data[$k]['bikeinfo'] = $bikeinfo[0]['bikeinfo'];
                    $data[$k]['bikeimage'] = $bikeinfo[0]['image'];
                    $data[$k]['startTime'] = $bikeinfo[0]['startTime'];
                    $data[$k]['endTime'] = $bikeinfo[0]['endTime'];
                    $data[$k]['rental'] = $bikeinfo[0]['rental'];
                    $k++;
                }
            }
            echo json_encode($data);
    }

    /**
     * 校验开锁密码
     */
    public function checkPassword()
    {
        $user_id = I('param.user_id');
        $password = I('param.password');

        $device = M('device','lock_','DB_CONFIG1');
        $where['owner_id'] = $user_id;

        $info = $device->where($where)->find();
        $pass = $info['lockPassword'];
        if ($pass==$password)
        {
            if ($device->where($where)->setInc('unlockCount'))
            {
                static $k = 0;
                $data[$k]['key'] = 1;

                echo json_encode($data);
            }

        }
        else
        {
            static $k = 0;

            $data[$k]['key'] = -1;

            json_encode($data);
      }
    }

    /**
     * 修改自行车信息
     */
    public function updateBike()
    {
        $user_id = I('param.user_id');
        $content = I('param.bikeinfo');
        $startTime = I('param.startTime');
        $endTime = I('param.endTime');
        $rental = I('param.rental');
        $bike = M('bike','lock_','DB_CONFIG1');
        if (!is_null($user_id))
        {
            $where['owner_id'] = $user_id;
        }
        if (!is_null($content))
        {
            $data['bikeinfo'] = $content;
        }
        if (!is_null($startTime))
        {
            $data['startTime'] = $startTime;
        }
        if (!is_null($endTime))
        {
            $data['endTime'] = $endTime;
        }
        if (!is_null($rental))
        {
            $data['rental'] = $rental;
        }

        if ($bike->where($where)->save($data))
        {
            static $k = 0;
            $data1[$k]['key'] = 1;

            echo json_encode($data1);
        }
        else
        {
            static $k = 0;
            $data1[$k]['key'] = -1;

            echo json_encode($data1);
        }
    }


/*    public function test(){
        $reason = I('param.reason');

        $position = M('position','lock_','DB_CONFIG1');
        $info = $position->where("reason=$reason")->select();
        dump($info);
    }*/
}