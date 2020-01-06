<?php
class ReleaseAction extends CommonAction{
	//申请信息
	public function index(){
		$user_id=$this->uid;
		$room=D('Lifesauthentication')->where(array('user_id'=>$user_id))->find();
        $vehicle=D('Lifesvehicle')->where(array('user_id'=>$user_id))->find();

		if(!empty($room)){
		$fan = D('Lifes');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
       
        if($user_id = (int) $this->_param('user_id')){
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if($area_id = (int) $this->_param('area_id')){
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        $count = $fan->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $fan->where($map)->order(array('life_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }

    }elseif(!empty($vehicle)){
    	$che = D('Lifessell');
        import('ORG.Util.Page');
        
        $map = array('closed' => 0,'user_id'=>$this->uid);
       
        if($user_id = (int) $this->_param('user_id')){
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if($area_id = (int) $this->_param('area_id')){
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        $count = $che->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $che->where($map)->order(array('life_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }


    }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('cates', D('Llifeclass')->fetchAll());
 
		
		$this->display();
	}

    //删除
    public function delete($life_id){
        $user_id=$this->uid;
        $room=D('Lifesauthentication')->where(array('user_id'=>$user_id))->find();
        $vehicle=D('Lifesvehicle')->where(array('user_id'=>$user_id))->find();
        if(!empty($room)){
            if (is_numeric($life_id) && ($life_id = (int) $life_id)) {
            $obj = D('Lifes');
            $obj->save(array('life_id' => $life_id,'closed'=>1));
            $this->tuSuccess('删除成功', U('release/index'));
             }

        }elseif(!empty($vehicle)){
            
            if (is_numeric($life_id) && ($life_id = (int) $life_id)) {
            $obj = D('Lifessell');
            $obj->save(array('life_id' => $life_id,'closed'=>1));
            $this->tuSuccess('删除成功', U('release/index'));
             }

        }

    }

}
