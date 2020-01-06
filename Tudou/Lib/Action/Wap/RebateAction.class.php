<?php		
class RebateAction extends CommonAction{

//显示列表
public function index(){
    $this->assign('nextpage', LinkTo('rebate/load', array('t' => NOW_TIME, 'p' => '0000')));
	$this->display();
}

//用户信息预约列表
public function load(){
	$user=$this->uid;
	$obj=D('Lifereserve');
	import('ORG.Util.Page');
    $map = array('user_id' =>$user,'is_pay'=>1,'close'=>0);
    $count = $obj->where($map)->count();
    $Page = new Page($count, 10); 
	$show = $Page->show();
    $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
    $p = $_GET[$var];
    if($Page->totalPages < $p){
        die('0');
    }
    $lists = $obj->where($map)->order(array('time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
	$this->assign('list', $lists);
    $this->assign('page', $show);
	$this->display();
}


//用户返利
public function rebate($id){
    $detail=D('Lifereserve')->where(array('id'=>$id,'user_id'=>$this->uid))->find();

	if($this->ispost()){
		$data=array();
		$data['evidencephoto'] =$this->_post('evidencephoto','htmlspecialchars');

		if(empty($data['evidencephoto'])){
            $this->tuMsg('收据图片为空');
		}
		if(!isImage($data['evidencephoto'])){
            $this->tuMsg('收据图片格式不正确');
        }
        $data['goodsphoto'] =$this->_post('goodsphoto','htmlspecialchars');
		if(empty($data['goodsphoto'])){
            $this->tuMsg('物品图片为空');
		}
		if(!isImage($data['goodsphoto'])){
            $this->tuMsg('物品图片格式不正确');
        }
       	$data['type_id']=$this->_post('type_id','htmlspecialchars');
       	if($data['type_id']==0){
       		$this->tuMsg('请选择购买类型');
       	}
       	$data['explain']=$this->_post('explain','htmlspecialchars');
       	if(empty($data['explain'])){
       		$this->tuMsg('请填写返利说明');
       	}
        $data['money']=$this->_post('money','htmlspecialchars');
       	if(empty($data['money'])){
       	    $this->tuMsg('请填写成交价格');
        }
       	$data['shop_id']=$detail['sell_user_id'];
       	$data['user_id']=$this->uid;
       	$data['onetime']=$detail['time'];
       	$data['life_id']=$id;
       	$obj=D('Liferebate');
       	$fanli=D('Lifereserve');
       	if($obj->add($data)){
       		$fanli->where(array('id'=>$id))->save(array('fl'=>1));
       		$this->tuMsg('恭喜你提交成功!',U('rebate/index'));
       	}else{
       		$this->tuMsg('操作失败');
       	}
	}else{
	$this->assign('detail',$detail);
	}
	$this->display();
}

//装修点评
public function dianping($id){
    $id = (int) $id;
    $obj=D('Lifereserve');
    $details=$obj->where(['id'=>$id,'user_id'=>$this->uid,'type'=>3])->find();
    $detail=D('Decorate')->where(['id'=>$details['life_id'],'shop_id'=>$details['sell_user_id']])->find();
    $this->assign('detail',$detail);
    $this->display();
}

public function ctrate($id){
    if($this->ispost()) {
        if (D("DecorateDianping")->where(['user_id' => $this->uid, 'order_id' => $id])->find()) {
            $this->tuMsg("已经评价过了");
        }
        $detail=D('Decorate')->where(['id'=>$id])->find();
        $data = $this->checkFields($this->_post('data', FALSE), array('score', 'speed', 'contents', 'photos'));
        $data['user_id'] = $this->uid;
        $data['shop_id'] = $detail['shop_id'];
        $data['order_id'] = $id;
        $data['score'] = (int)$data['score'];
        if (empty($data['score'])) {
            $this->tuMsg("评分不能为空");
        }
        if (5 < $data['score'] || $data['score'] < 1) {
            $this->tuMsg("评分为1-5之间的数字");
        }
        $data['contents'] = htmlspecialchars($data['contents']);
        if (empty($data['contents'])) {
            $this->tuMsg("评价内容不能为空");
        }
        if ($words = D("Sensitive")->checkWords($data['contents'])) {
            $this->tuMsg("评价内容含有敏感词：" . $words);
        }
        $data['photos'] = htmlspecialchars($data['photos']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        if (D("DecorateDianping")->add($data)) {
            $this->tuMsg("恭喜您点评成功!", u("rebate/index"));
        }
        $this->tuMsg("点评失败！");
    }
}

//投诉
public function toushu($id){
    $shop=D("Decorate")->where(array("id" => $id))->find();
    $this->assign("res", $shop);
    $this->display();

}

//验证
public function tainjia($id){
    //查询订单信息
    if($this->_post()){
        if($dc = D('Shopcomplaint')->where(array('order_id'=>$id,'user_id'=>$this->uid,'type'=>11))->find()){
            $this->tuMsg('您已经投诉过了！');
        }
        $shop=D("Decorate")->where(array("id" => $id))->find();
        //获取页面信息
        $data = $this->checkFields($this->_post('data', FALSE), array('contents', 'photos'));
        $userid=$this->uid;
        $shop_id=$shop["shop_id"];
        $data['content']=$data['contents'];
        if(empty($data['content'])){
            $this->tuMsg('投诉内容不能为空');
        }
        if($words = D('Sensitive')->checkWords($data['content'])){
            $this->tuMsg('评价内容含有敏感词：' . $words);
        }
        $data['photo']=$data['photos'];
        $data['shop_id']=$shop_id;
        $data['order_id']=$id;
        $data['user_id']=$userid;
        $data['type']=11;
        $ts= D('Shopcomplaint')->add($data);

        if($ts>0){
            $this->tuMsg('投诉成功！',U('rebate/index'));
        }else{
            $this->tuMsg('投诉失败！');
        }
    }
}












}
