<?php

class CityAction extends CommonAction{
    public function index(){
		if ($keyword2 = $this->_param('keyword2', 'htmlspecialchars')) {
             $map = array('name|pinyin' => array('LIKE', '%' . $keyword2 . '%'));
             $citlist = D('City')->where($map)->orderby(' city_id asc ')->select();
			 $this->citys = $citlist;
             $this->assign('keyword2', $keyword2);
        }
		
        $citylists = array();
	    $this->_CONFIG = D('Setting')->fetchAll();
        foreach($this->citys as $val){
			 if($val['is_open'] == 1){
            $a = strtoupper($val['first_letter']);
            $citylists[$a][] = $val;
		  }
        }
		
        ksort($citylists);
        $this->assign('citylists',$citylists);
		$this->assign('hostdo',$this->_CONFIG['site']['hostdo']);
        $this->display();
    }
	public function change($city_id){
		$http = ($this->_CONFIG['site']['https'] == 1) ? 'https' : 'http';
        if(empty($city_id)){
            $this->error('没有正确的城市');
        }
        if(isset($this->citys[$city_id])){            
            cookie('city_id',$city_id,86400*30);
            header('Location: '.$http.'//'.TU_HOST_PREFIX.'.'. TU_DOMAIN);die;
        }
        $this->error('没有正确的城市');
    }

}