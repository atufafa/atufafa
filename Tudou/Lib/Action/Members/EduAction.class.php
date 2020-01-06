<?php
class EduAction extends CommonAction {
	 public function _initialize() {
        parent::_initialize();
        $this->age = D('Edu')->getEduage();
        $this->assign('age', $this->age);
        $this->get_time = D('Edu')->getEduTime();
        $this->assign('get_time', $this->get_time);
		$this->get_edu_class = D('Edu')->getEduClass();
        $this->assign('class', $this->get_edu_class);
		$this->assign('cates', D('Educate')->fetchAll());
		$this->assign('types', D('EduOrder')->getType());
    }
    //订单列表
    public function index() {
        $EduOrder = D('EduOrder'); 
        $order_id = I('order_id',0,'trim,intval');
        $map = array();
        $map['user_id'] = $this->uid;
        if($order_id){
            $map['order_id'] = $order_id;
        }
        import('ORG.Util.Page');
        $count = $EduOrder->where($map)->count();
        $Page = new Page($count,25); 
        $show = $Page->show();
        $list = $EduOrder->where($map)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($list as $k => $v){
            $course = D('Educourse')->where(array('course_id'=>$v['course_id']))->find();
            $list[$k]['course'] = $course;
        }
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display(); 
    }
  
    
	//课程取消订单
    public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->tuError('订单不存在');
       }elseif(!$detail = D('EduOrder')->find($order_id)){
           $this->tuError('订单不存在');
       }elseif($detail['user_id'] != $this->uid){
           $this->tuError('非法操作订单');
       }else{
           if(false !== D('EduOrder')->cancel($order_id)){
               $this->tuSuccess('订单取消成功',U('edu/index'));
           }else{
               $this->tuError('订单取消失败');
           }
       }
    }

    //教育订单点评
    public function comment($order_id) {
        if(!$order_id = (int) $order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('EduOrder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['user_id'] != $this->uid){
            $this->error('非法操作订单');
        }elseif($detail['comment_status'] == 1){
            $this->error('已经评价过了');
        }else{
            if ($this->_Post()) {
                $data = $this->checkFields($this->_post('data', false), array('score', 'content'));
                $data['user_id'] = $this->uid;
                if (!$Educourse= D('Educourse')->find($detail['course_id'])) {
                    $this->tuMsg('没有找到课程，请稍后再试');
                }
                $edu = D('Edu')->where(array('edu_id'=>$detail['edu_id']))->find();
                $data['shop_id'] = $edu['shop_id'];
                $data['course_id'] = $detail['course_id'];
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
                $data['show_date'] = date('Y-m-d', NOW_TIME + ($this->_CONFIG['mobile']['data_edu_dianping'] * 86400));
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $photos = $this->_post('photos', false);
                if($photos){
                    $data['have_photo'] = 1;
                }
                if ($comment_id = D('EduComment')->add($data)) {
                    $local = array();
                    foreach ($photos as $val) {
                        if (isImage($val))
                            $local[] = $val;
                    }
                    if (!empty($local)){
                        foreach($local as $k=>$val){
                            D('EduCommentPics')->add(array('comment_id'=>$comment_id,'photo'=>$val));
                        }
                    }
                    D('EduOrder')->save(array('order_id'=>$order_id,'comment_status'=>1));
                    D('Users')->updateCount($this->uid, 'ping_num');
                    D("Shop")->updateCount($edu['shop_id'], "score_num");
                    $this->tuMsg('恭喜您点评成功!', U('edu/index'));
                }
                $this->tuMsg('点评失败');
            }else {
                $detail['course'] = D('Educourse')->where(array('course_id'=>$detail['course_id']))->find();
                $detail['edu'] = D('Edu')->where(array('edu_id'=>$detail['edu_id']))->find();
                $this->assign('edu',D('Edu')->find($order_id));
                $this->assign('order_id',$order_id);
                $this->assign('detail', $detail);
                $this->display();
            }
        }
    }

}
