<?php

class FarmAction extends CommonAction {
	protected function _initialize(){
       parent::_initialize();
        if ($this->_CONFIG['operation']['farm'] == 0) {
            $this->error('此功能已关闭');
            die;
        }
    }
  
    public function index() {
        $F = D('FarmOrder'); 
        $gotime = I('gotime','','trim');
        $order_id = I('order_id',0,'trim,intval');
        $map = array();
        $map['user_id'] = $this->uid;
        if($gotime){
            $gotime = strtotime($gotime);
            $map['gotime']  = array('between',array($gotime,$gotime+86399));
        }
        if($order_id){
            $map['order_id'] = $order_id;
        }
        import('ORG.Util.Page');
 
        $count  = $F->where($map)->count();
        $Page   = new Page($count,25); 
        $show   = $Page->show();
        $list = $F->where($map)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        foreach($list as $k => $v){
            $farm = D('Farm')->where(array('farm_id'=>$v['farm_id']))->find();
            $list[$k]['farm'] = $farm;
        }

        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display(); 
    }
    
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('FarmOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法的订单操作');
        }else{ 
           $detail['package'] = D('HotelPackage')->where(array('pid'=>$detail['pid']))->find();
           $detail['farm'] = D('Farm')->where(array('farm_id'=>$detail['farm_id']))->find();
           print
           $this->assign('detail',$detail);
           $this->display();
        } 
    }
    

    public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->tuError('订单不存在');
       }elseif(!$detail = D('FarmOrder')->find($order_id)){
           $this->tuError('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->tuError('非法操作订单');
       }else{
           if(false !== D('FarmOrder')->cancel($order_id)){
               $this->tuSuccess('订单取消成功',U('farm/detail',array('order_id'=>$order_id)));
           }else{
               $this->tuError('订单取消失败');
           }
       }
    }

    public function comment($order_id) {
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('FarmOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作订单');
        }elseif($detail['comment_status'] == 1){
            $this->error('已经评价过了');
        }else{
            if ($this->_Post()) {
                $data = $this->checkFields($this->_post('data', false), array('score', 'content'));
                $data['user_id'] = $this->uid;
                if (!$Farm = D('Farm')->find($detail['farm_id'])) {
                    $this->tuMsg('没有找到对应的农家乐，暂时无法点评，请稍后再试');
                }
                $data['shop_id'] = $Farm['shop_id'];
                $data['farm_id'] = $detail['farm_id'];
                $data['order_id'] = $order_id;
                $data['score'] = (int) $data['score'];
                if (empty($data['score'])) {
                    $this->tuError('评分不能为空');
                }
                if ($data['score'] > 5 || $data['score'] < 1) {
                    $this->tuError('评分为1-5之间的数字');
                }
                $data['content'] = htmlspecialchars($data['content']);
                if (empty($data['content'])) {
                    $this->tuError('评价内容不能为空');
                }
                if ($words = D('Sensitive')->checkWords($data['content'])) {
                    $this->tuError('评价内容含有敏感词：' . $words);
                }
                $data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_fram_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $photos = $this->_post('photos', false);
                if($photos){
                    $data['have_photo'] = 1;
                }
                if ($comment_id = D('FarmComment')->add($data)) {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        foreach($local as $k=>$val){
                            D('FarmCommentPics')->add(array('comment_id'=>$comment_id,'photo'=>$val));
                        }
                    }
                    D('FarmOrder')->save(array('order_id'=>$order_id,'comment_status'=>1));
                    D('Users')->updateCount($this->uid, 'ping_num');
                    D('Farm')->updateCount($detail['farm_id'],'comments');
                    D('Farm')->updateCount($detail['farm_id'],'score',$data['score']);
                    D("Shop")->updateCount($detail['shop_id'], "score_num");
                    //$this->tuMsg('恭喜您点评成功!'.$comment_id, U('farm/index'));
                    $this->tuMsg('恭喜您点评成功!', U('farm/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $detail['package'] = D('FarmPackage')->where(array('pid'=>$detail['pid']))->find();
                $detail['farm'] = D('Farm')->where(array('farm_id'=>$detail['farm_id']))->find();
                $this->assign('detail', $detail);
                $this->display();
            }
        }
    }
}
