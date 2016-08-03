<?php
namespace Home\Controller;
use Think\Controller;
class DeviceController extends Controller{
    /**
     * 方法名：reportPosition
     * 参数：device
     * 返回值：无
     * 功能：设备报告当前地理位置
     * 输出：json格式的状态信息
     */
    public function reportPosition(){
        $deviceNo = I('get.device');      //设备序列号
        $lng = I('get.lng');            //经度
        $lat = I('get.lat');            //纬度
        $result= file_get_contents("http://restapi.amap.com/v3/geocode/regeo?output=xml&location=$lng,$lat&key=e44333d64b0f22973908d9c3d4c0a05b&radius=1000&extensions=all");
        $xml = simplexml_load_string($result);
        $address = (String)$xml->regeocode->formatted_address;
        $device = M('position','lock_','DB_CONFIG1');  //实例化position表
        $info['device_serialNumber']=$deviceNo;
        $info['lng'] = $lng;
        $info['lat'] = $lat;
        $info['position'] = $address;
        echo $address;
        if ($lng!=null&&$lat!=null&&$deviceNo!=null){
            if (!$device->add($info)){
                static $key = 0;
                $data[$key]['key'] = -1;
                echo json_encode($data);
            }else{
                static $key = 0;
                $data[$key]['key'] = 1;
                echo json_encode($data);  
            }
        }else if ($deviceNo==null||$lat==null||$lng==null){
            static $key = 0;
            $data[$key]['key'] = -1;
            echo json_encode($data);
        }
    }
    
    /**
     * 车锁已锁上
     */
    public function locked(){
        
    }
    
    /**
     * 车锁已解锁
     */
    public function unlocked(){
        
    }
    
    /**
     * 车连续抖动三次
     */
    public function shakeThree(){
        
    }
    
    /**
     * 车连续抖动四次
     * 
     */
    public function shakeFour(){
        
    }
    
    /**
     * 发生意外
     */
    public function accident(){
        
    }
    
    /**
     * 报告电量
     */
}