<?php
class LifesModel extends CommonModel{
    protected $pk = 'life_id';
    protected $tableName = 'lifes';
	
	//获取信息详细页面轮播数据
	public function getScroll(){
		$config = D('Setting')->fetchAll();
		$limit = isset($config['life']['limit']) ? (int)$config['life']['limit'] : 6;
		$order = isset($config['life']['order']) ? (int)$config['life']['order'] : 1;
		switch ($order) {
            case '1':
                $orderby = array('create_time' =>'desc','log_id' =>'desc');
                break;
            case '2':
                $orderby = array('money' =>'desc');
                break;
            case '3':
                $orderby = array('log_id' =>'desc');
                break;
        }
		$list = D('LifePacketLogs')->order($orderby)->limit(0,$limit)->select();
		foreach($list as $k => $v){
            if($user = D('Users')->where(array('user_id'=>$v['user_id']))->find()){
                $list[$k]['user'] = $user;
            }
        }
        return $list;
    }
	
	//返回当前分类信息是否还有红包
	public function getLifePacket($life_id){
		if(!$detail = D('Lifes')->find($life_id)){
			return 0;
		}
		$LifePacket = D('Lifespacket')->where(array('life_id'=>$life_id,'closed'=>0,'status'=>1))->find();
		if(!$LifePacket){
			return 0;
		}
		if($LifePacket['packet_sold_num'] == $LifePacket['packet_num']){
			return 0;
		}
		return true;
	}		
	}