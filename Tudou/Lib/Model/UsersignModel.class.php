<?php
class  UsersignModel extends CommonModel{
    protected $pk   = 'user_id';
    protected $tableName =  'user_sign';

    public function getSign($user_id,$integral = false){
        $user_id = (int)$user_id;
        if(!$data = $this->where(array('user_id' => $user_id))->order('last_time desc')->limit(1)->select()){
        //if(!$data = $this->find($user_id)->order('last_time asc')){
            $data = array(
                'user_id'      => $user_id,
                'day'          => 0,
                //'last_time'    => NOW_TIME - 86400,
                'last_time'    => NOW_TIME - 86400,
                'is_first'     => 0
            );
            //echo '添加';
            //$this->add($data);
        }
        if($integral!==false){ //返回明日登录积分 及 今天是否登录的状态
            $day=$data[0]['day'] == 0 ? $data[0]['day'] + 2 : $data[0]['day']+1;
            if($day > 1){
                //后台没有
                //$integral+=$day; //加上连续登陆的天数
            }
            $data[0]['integral'] = $integral;
            $lastdate = date('Y-m-d',$data[0]['last_time']);

            if($lastdate  == TODAY){
                $data[0]['is_sign'] = 1;
            }else{
                $data[0]['is_sign'] = 0;
            }
        }
        return $data;
    }

    public function sign($user_id,$integral,$firstintegral = 0){
        $user_id = (int)$user_id;
        $integral = (int) $integral;
        $data = $this->getSign($user_id);
        $lastdate = date('Y-m-d',$data[0]['last_time']);
        if($lastdate < TODAY){
            if(NOW_TIME - $data[0]['last_time'] <= 86400*2){
                $data[0]['day']+=1;
                //$data[0]['status'] = 1;
            }else{
                $data[0]['day'] =  1;
            }
//            if(NOW_TIME - $data[0]['last_time'] > 86400){//隔天了
//                $data[0]['day'] =  1;
//            }else{
//                $data[0]['day']+=1;
//            }
//            if($data[0]['day'] > 1){
//                $integral+=$data[0]['day']; //加上连续登陆的天数
//            }
//            $is_first = false;
//            if($data[0]['is_first']){
//                $is_first = true;
//                $data[0]['is_first'] = 0;
//            }
            $query = $this->where(array('user_id' => $user_id))->order('last_time desc')->limit(1)->select();
            if(!$query){
                 $is_first= 0;
            }
            $data[0]['last_time'] = strtotime(date("Y-m-d 00:00:00"));
            $data[0]['user_id'] = $user_id;
            //if($this->save($data)){
            if($this->add($data[0])){
                $return = $integral;
                if($is_first){
                    D('Users')->addIntegral($user_id,$firstintegral,'首次签到');
                    $return += $firstintegral;
                }
                D('Users')->addIntegral($user_id,$integral,TODAY.'手机签到');
                return $return;
            }
            return false;
        }
        return false;
    }




}