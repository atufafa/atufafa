<?php
class GoodsAction extends CommonAction{

    private $create_fields = array('title','is_pintuan','ping_money','photo','parent_id','monry','is_yuyue','is_show','yuyue_time','yuyue_money','explain', 'shoplx','cate_id', 'intro','guige', 'num','is_reight','weight','kuaidi_id','select1', 'select2', 'select3', 'select4', 'select5','price', 'shopcate_id', 'mall_price','use_integral','instructions', 'details', 'end_date','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','is_vs7','is_vs8','is_vs9','readi','numbers','huifu_money','day_date','huifu2_money','profit_enable','profit_rate1','profit_rate2','profit_rate3','profit_rank_id');
    private $edit_fields = array('title','is_pintuan','ping_money','photo','parent_id','monry','is_yuyue','is_show','yuyue_time','yuyue_money','explain','shoplx', 'cate_id', 'intro','guige', 'num','is_reight','weight','kuaidi_id','select1', 'select2', 'select3', 'select4', 'select5','price', 'shopcate_id', 'mall_price','use_integral', 'instructions', 'details', 'end_date','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','is_vs7','is_vs8','is_vs9','readi','numbers','huifu_money','day_date','huifu2_money','profit_enable','profit_rate1','profit_rate2','profit_rate3','profit_rank_id','audit');
     private $tuan_create_fields = array('parent_id','shop_id', 'weight','use_integral', 'cate_id', 'intro', 'title', 'photo', 'price','shoplx','tuan_price', 'settlement_price', 'num', 'sold_num', 'bg_date', 'end_date', 'fail_date', 'is_hot', 'is_new', 'is_chose', 'freebook','xiangou','kuaidi_id');

    public function _initialize(){
        parent::_initialize();
		if($this->_CONFIG['operation']['mall'] == 0){
				$this->error('此功能已关闭');die;
		}
        $this->shop = D('Shop')->find($this->shop_id);
		$this->assign('mall',$this->shop);
        $this->autocates = D('Goodsshopcate')->where(array('shop_id' => $this->shop_id))->select();
        $this->assign('autocates', $this->autocates);

		$this->tuan_autocates=D('Zeroelementcate')->where(array('shop_id' => $this->shop_id))->select();
        $this->assign('tuancates', $this->tuan_autocates);

		$this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$this->shop_id,'type'=>goods,'closed'=>0))->select());
        $this->assign('autocates', $this->autocates);
		$this->GoodsCates = D('Goodscate')->fetchAll();
        $this->assign('GoodsCates', $this->GoodsCates);
    }

    //商城设置
    public function set(){
        $this->display();
    }

    public function open(){
        $is_yuyue = (int) $_POST['is_yuyue'];
        if($is_yuyue==0){
            D('Goods')->where(array('shop_id'=>array('IN',$this->shop)))->save(array('is_yuyue'=>0,'closed'=>1));
        }
        D('Shop')->save(array(
            'shop_id' => $this->shop_id,
            'is_yuyue'=>$is_yuyue,
        ));
        $this->tuSuccess('操作成功', U('goods/set'));
    }

    public function index(){
        $Goods = D('Goods');
        import('ORG.Util.Page'); 
        $map = array('shop_id' =>$this->shop_id,'huodong'=>0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = array('IN', D('Goodscate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }

        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show();
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
			
			if($kuaidi = D('Pkuaidi')->where(array('kuaidi_id'=>$val['kuaidi_id'],'closed'=>0))->find()){
                $list[$k]['kuaidi'] = $kuaidi;
            }
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display();
    }
	
    public function reply()
    {
        if($this->isPost()){
            print_r($_POST);die;
        }else{
            $this->display();            
        }
    }

    public function get_select(){
        if(IS_AJAX){
            $pid = I('pid', 0, 'intval,trim');
            $gc = D('GoodsCate');
            $list = $gc->where('parent_id =' . $pid)->select();
            if($pid == 0){
                $this->ajaxReturn(array('status' => 'success', 'list' => ''));
            }
            if($list){
                $l = '';
                foreach ($list as $k => $v) {
                    $l = $l . '<option value=' . $v['cate_id'] . ' style="color:#333333;">' . $v['cate_name'] . '</option>';
                }

                $this->ajaxReturn(array('status' => 'success', 'list' => $l));
            }
        }
    }

	//编辑或者添加分销通用
	public function profit($goods_id){  
		if($this->shop['is_profit'] != 1){
            $this->error('您尚未开通分销权限');
        }
		$profit = M('GoodsProfit')->where(array('goods_id'=>$goods_id))->find();
		
		if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('profit_enable','profit_rate1','profit_rate2','profit_rate3'));
			$data['goods_id'] = $goods_id;
			$data['shop_id'] = $this->shop['shop_id'];
			$data['profit_enable'] = (int) $data['profit_enable'];
			$data['profit_rate1'] = $data['profit_rate1'];
			$data['profit_rate2'] = $data['profit_rate2'];
			$data['profit_rate3'] = $data['profit_rate3'];
			if(($data['profit_rate1'] + $data['profit_rate2'] + $data['profit_rate3']) >= 100){
				$this->tuError('分成比例相加不能大于或者等于100%');
				//$this->ajaxReturn(array('code'=>'0','msg'=>'分成比例相加不能大于或者等于100%'));
			}
			if($profit){
				$res = M('GoodsProfit')->save($data);
			}else{
				$res = M('GoodsProfit')->add($data);
			}
            if($res){
				//$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('goods/index')));
				$this->tuSuccess('操作成功', U('goods/index'));
            }
			$this->tuError('操作失败');
            //$this->ajaxReturn(array('code'=>'0','msg'=>'操作失败'));
        }else{
           $this->assign('detail', M('Goods')->find($goods_id));
		   $this->assign('profit', $profit);
           $this->display();
		}
	}

 	//删除商品
 	public function delete($goods_id = 0){
        $goods_id = (int) $goods_id;
        $obj = D('Goods');
        if(empty($goods_id)){
            $this->tuError('该商品信息不存在');
        }
        if(!($detail = D('Tuan')->find($goods_id))){
            $this->tuError('该商品信息不存在');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('非法操作');
        }
        $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
        $activity=M('mall_activity_goods')->where(['goods_id'=>$goods_id,'shop_id'=>$this->shop_id])->find();
        if(!empty($activity)){
            M('mall_activity_goods')->where(['goods_id'=>$goods_id,'shop_id'=>$this->shop_id])->delete();
        }
        $this->tuSuccess('删除成功', U('goods/index'));
    }
	
	
	//商品上架下架
    public function update($goods_id = 0){
        if($goods_id = (int) $goods_id){
            if(!($detail = D('Goods')->find($goods_id))){
                $this->tuError('请选择要操作的商品');
            }
            $data = array('closed' =>0,'goods_id' => $goods_id);
            $ad=D('Ad')->where(['shop_id'=>$this->shop_id,'goods_id'=>$goods_id])->find();
            if(!empty($ad)){
                D('Ad')->where(['ad_id'=>$ad['ad_id'],'shop_id'=>$this->shop_id,'goods_id'=>$goods_id])->save(['closed'=>0]);
                $intro = '上架商品成功，广告位重新启用！！！';
            }else{
                $intro = '上架商品成功';
            }

            if($detail['closed'] == 0){
                $pin=D('Envelope')->where(array('shop_id'=>$this->shop_id,'goods_id'=>$goods_id))->find();

			    $time=time();
                if($time>$pin['lower_shelf'] && !empty($pin)){
                    $this->tuError('该拼团商品还未到下架时间');
                }else{
                    $data['closed'] = 1;
                    $data['is_pintuan']=0;
                    $activity=M('mall_activity_goods')->where(['goods_id'=>$goods_id,'shop_id'=>$this->shop_id])->find();
                    if(!empty($activity)){
                        M('mall_activity_goods')->where(['goods_id'=>$goods_id,'shop_id'=>$this->shop_id])->delete();
                    }
                    if(!empty($ad)){
                        D('Ad')->where(['ad_id'=>$ad['ad_id'],'shop_id'=>$this->shop_id,'goods_id'=>$goods_id])->save(['closed'=>1]);
                        $intro = '下架商品成功,广告已停止！！！';
                    }else{
                        $intro = '下架商品成功';
                    }

                }
            }
            if(D('Goods')->save($data)){
                $this->tuSuccess($intro, U('goods/index'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }



    //商品上架下架
    public function update2($tuan_id = 0){
        if($tuan_id = (int) $tuan_id){
            if(!($detail = D('Pindan')->find($tuan_id))){
                $this->tuError('请选择要操作的商品');
            }
            $data = array('closed' =>0,'tuan_id' => $tuan_id);
            $intro = '上架商品成功';
            if($detail['closed'] == 0){
                    $data['closed'] = 1;
                    $intro = '下架商品成功';
                }

            if(D('Pindan')->save($data)){
                $this->tuSuccess($intro, U('goods/tuan_index'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }


    //0员购删除
    public function tuan_del($tuan_id=0){
        if($tuan_id = (int) $tuan_id){
            if(!($detail = D('Pindan')->find($tuan_id))){
                $this->tuError('请选择要操作的商品');
            }
            if(D('Pindan')->where(['tuan_id'=>$tuan_id])->delete()){
                $this->tuSuccess('删除成功', U('goods/tuan_index'));
            }
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }
	

    public function create(){
        if ($this->isPost()){
            $data = $this->createCheck();
            $obj = D('Goods');
            if($goods_id = $obj->add($data)) {
                if($data['is_yuyue']==1){
                    $s=D('Goods')->where(['goods_id'=>$goods_id])->save(['mall_price'=>$data['yuyue_money']]);
                }
                $wei_pic = D('Weixin')->getCode($goods_id, 3); 
                $obj->save(array('goods_id' => $goods_id, 'wei_pic' => $wei_pic));
				$photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Goodsphoto')->upload($goods_id, $photos);
                }
				$this->shuxin($goods_id);
				$this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性 -->
                $this->tuSuccess('添加成功', U('goods/index'));
            }
            $this->tuError('操作失败');
        }else{
            $shop_user = D('Shop')->where(array('shop_id'=>$this->shop_id))->find();
        	$this->assign('goodsInfo',D('Goods')->where('goods_id='.I('GET.id',0))->find());  // 商品详情   
            $this->assign('goodsType',M("TpGoodsType")->where(array("shop_id"=>$this->shop_id))->select());
            // dump($shop_user);
            $shop_tel=$this->_CONFIG['goods']['shop_tel'];

            if($shop_user['mobile'] == $shop_tel){
                $this->assign('is_plat_admin', 1); //判断是否为平台管理员
                $this->assign('cates',D('Goodscate')->fetchAll());
            }else{
                $this->assign('is_plat_admin', 0);
                $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
            }

            $this->display();
        }
    }
	//0元购列表 -----新增
    public function tuan_index()
    {
        $Tuan = D('Pindan');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Tuan->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Tuan->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            $val = $Tuan->_format($val);
            $list[$k] = $val;
            if($kuaidi = D('Pkuaidi')->where(array('kuaidi_id'=>$val['kuaidi_id'],'closed'=>0))->find()){
                $list[$k]['kuaidi'] = $kuaidi;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //0元购发布 -----新增
    public function tuan_create()
    {
        if ($this->isPost()){
            $data = $this->tuan_createCheck();
            $obj = D('Pindan');
            $details = $this->_post('details', 'SecurityEditorHtml');
            if(empty($details)){
                $this->tuError('抢购详情不能为空');
            }
            if($words = D('Sensitive')->checkWords($details)){
                $this->tuError('详细内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            if(empty($instructions)){
                $this->tuError('购买须知不能为空');
            }
            if($words = D('Sensitive')->checkWords($instructions)){
                $this->tuError('购买须知含有敏感词：' . $words);
            }
            $thumb = $this->_param('thumb', false);
            foreach($thumb as $k => $val){
                if(empty($val)){
                    unset($thumb[$k]);
                }
                if(!isImage($val)){
                    unset($thumb[$k]);
                }
            }
            // print_r($data);die;
            $data['thumb'] = serialize($thumb);
            if ($tuan_id = $obj->add($data)){
                $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                //抢购类型是2
                $obj->save(array('tuan_id' => $tuan_id, 'wei_pic' => $wei_pic));
                D('Pindandetails')->add(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions));
                $this->shuxins($tuan_id);
                $this->saveGoodsAttrS($tuan_id,$_POST['goods_type']); //更新商品属性 -->
                $this->tuSuccess('添加成功', U('goods/tuan_index'));
            }
            $this->tuError('操作失败');
        }else{
            $shop_user = D('Shop')->where(array('shop_id'=>$this->shop_id))->find();
            $this->assign('goodsInfo',D('Pindan')->where('goods_id='.I('GET.id',0))->find());  // 商品详情
            $this->assign('goodsType',D("Zeroelementtype")->where(array("shop_id"=>$this->shop_id))->select());
            // dump($shop_user);
            // dump( D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());die;
            $shop_tel=$this->_CONFIG['goods']['shop_tel'];
            if($shop_user['mobile']==$shop_tel){
                $this->assign('cates',D('Goodscate')->fetchAll());
            }else{
                $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
            }
            $this->display();
        }
    }

    private function tuan_createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->tuan_create_fields);
        // print_r($data);die;
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])){
            $this->tuError('抢购分类不能为空');
        }
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;

        $data['shop_id'] = $this->shop_id;
        $data['city_id'] = $this->shop['city_id'];
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['lng'] = $this->shop['lng'];
        $data['lat'] = $this->shop['lat'];
        
        
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('抢购名称不能为空');
        }
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('抢购简介不能为空');
        }

        $data['weight'] = (float) $data['weight'];
            if (empty($data['weight'])) {
                $this->tuError('重量不能为空');
             }

        $data['kuaidi_id'] = (int) $data['kuaidi_id'];
            if (empty($data['kuaidi_id'])) {
                $this->tuError('运费模板不能为空');
            }

        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('图片格式不正确');
        }
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        }
        //添加
        $data['settlement_price'] = (int) ($data['tuan_price'] - $data['tuan_price'] * $this->tuancates[$data['cate_id']]['rate'] / 1000);
        $data['use_integral'] = (int) $data['use_integral'];
        //抢购检测积分合法性结束
        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        }
        $data['sold_num'] = (int) $data['sold_num'];
        $data['bg_date'] = htmlspecialchars($data['bg_date']);
        if (empty($data['bg_date'])) {
            $this->tuError('开始时间不能为空');
        }
        if (!isDate($data['bg_date'])) {
            $this->tuError('开始时间格式不正确');
        }
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('结束时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('结束时间格式不正确');
        }
        $data['is_hot'] = (int) $data['is_hot'];
        $data['xiangou'] = (int) $data['xiangou'];
        $data['is_new'] = (int) $data['is_new'];
        $data['is_chose'] = (int) $data['is_chose'];
        $data['is_multi'] = (int) $data['is_multi'];
        $data['freebook'] = (int) $data['freebook'];
        $data['is_return_cash'] = (int) $data['is_return_cash'];
        $data['fail_date'] = htmlspecialchars($data['fail_date']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['audit'] = 0;
        return $data;
    }

    public function tuan_edit($tuan_id){
        $obj=D('Pindan');
        if (!$detail = $obj->find($tuan_id)) {
            $this->error('请选择要编辑的商品');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要试图越权操作其他人的内容');
        }
        if($this->ispost()){
            $data = $this->tuan_createCheck();
            $details = $this->_post('details', 'SecurityEditorHtml');
            if(empty($details)){
                $this->tuError('抢购详情不能为空');
            }
            if($words = D('Sensitive')->checkWords($details)){
                $this->tuError('详细内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            if(empty($instructions)){
                $this->tuError('购买须知不能为空');
            }
            if($words = D('Sensitive')->checkWords($instructions)){
                $this->tuError('购买须知含有敏感词：' . $words);
            }
            $data['tuan_id'] = $tuan_id;
            if(false!=$obj->save($data)){
                D('Pindandetails')->save(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions));
                $this->shuxins($tuan_id);
                $this->saveGoodsAttrS($tuan_id,$_POST['goods_type']); //更新商品属性 -->
                $this->tuSuccess('编辑成功', U('goods/tuan_index'));
            }
            $this->tuError('操作失败');
        }else{
            $this->assign('tuan_details',D('Pindandetails')->where(array('tuan_id'=>$detail['tuan_id']))->find());
            //$this->assign('cates',D('Goodscate')->where(array('cate_id'=>179))->select());
            $this->assign('detail',$detail);

            $shop_user=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
            $shop_tel=$this->_CONFIG['goods']['shop_tel'];
            if($shop_user['mobile']==$shop_tel){
                $this->assign('cates',D('Goodscate')->fetchAll());
                $this->assign('parent',D('Goodscate')->select());
            }else{
                $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
            }
            $goodsInfo=D('Pindan')->where(['tuan_id'=>$tuan_id])->find();
//            $s=D("Zeroelementtype")->where(array("shop_id"=>$this->shop_id))->select();
//            var_dump($goodsInfo,$s);die;
            $this->assign('goodsInfo',$goodsInfo);
            $this->assign('goodsType',D("Zeroelementtype")->where(array("shop_id"=>$this->shop_id))->select());
            $this->assign('detail', $obj->_format($detail));
            $this->assign('attrs', D('zeroelementattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
            $this->assign('shop', D('Shop')->find($detail['shop_id']));
            $this->display();
        }
    }

    //分销商品确认发货
    public function tuan_express($order_id = 0)
    {
        $data = $_POST;
        $order_id = $data['order_id'];
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        if (!($detail = D('Pindanorder')->find($order_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有该订单'.$order_id));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单已经被删除'));
        }
        if ($detail['status'] == 2 || $detail['status'] == 3 || $detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 5) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单状态不正确，不能发货'));
        }
        $express_id = $data['express_id'];
        if (empty($express_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择快递'));
        }
        if (!($detail = D('Logistics')->find($express_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有'.$detail['express_name'].'快递'));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该快递已关闭'));
        }
        $express_number = $data['express_number'];
        if (empty($express_number)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '快递单号不能为空'));
        }
        $add_express = array(
                'status' => 2
        );
        if(D('Pindanorder')->where(['order_id'=>$order_id])->save($add_express)){
            //D('Pindanorder')->pc_express_deliver($order_id);//执行发货
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 2);
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 2);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '一键发货成功', 'url' => U('goods/tuan_wait')));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '发货失败')); 
        }
    }

        //选择分类
    public function tuan_create_child($parent_id=0){
        $datas = D('Goodscate')->fetchAll();
        $str = '';
        foreach($datas as $var){
            if($var['parent_id'] == 0 && $var['cate_id'] == $parent_id){
                foreach($datas as $var2){
                    if($var2['parent_id'] == $var['cate_id']){
                        $str.='<option value="'.$var2['cate_id'].'">'.$var2['cate_name'].'</option>'."\n\r";
                        foreach($datas as $var3){
                            if($var3['parent_id'] == $var2['cate_id']){
                               $str.='<option value="'.$var3['cate_id'].'">&nbsp;&nbsp;--'.$var3['cate_name'].'</option>'."\n\r"; 
                            }
                            
                        }
                    }  
                }

            }
        }
        echo $str;die;
    }

    //新增选择分类
    public function tuan_creates($parent_id=0){
        $datas = D('Goodscate')->where(array('parent_id'=>0,'cate_id'=>$parent_id))->find();
        $row=D('Goodscate')->where(array('parent_id'=>array('in',$datas['cate_id'])))->select();
        $str = '';
        foreach($row as $var){
              $str.='<option value="'.$var['cate_id'].'">'.$var['cate_name'].'</option>'."\n\r";
        }
        echo $str;die;
    }

    // 分销商品售后
	public function tuan_refund()
    {
        import('ORG.Util.Page'); 
        $Order = M('PindanRefund');
         $map=array('shop_id'=>$this->shop_id);
        $count = $Order->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $Order->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('create_time desc')->select();
         $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $value) {
            $goods = D('Pindanorder')->where(['id'=>$value['order_goods_id']])->find();
            $order = D('Pindan')->where(['order_id'=>$value['order_id']])->find();
            // $goods_ids[$goods['goods_id']] = $goods['goods_id'];
            $order_ids[$value['order_id']] = $value['order_id'];
            $addr_ids[$order['addr_id']] = $order['addr_id'];
            $shop_ids[$order['shop_id']] = $order['shop_id'];  
            $address_ids[$order['address_id']] = $order['address_id'];  
            $user_ids[$value['user_id']]  = $value['user_id']; 
            if(!empty($user_ids)){
                $users = D('Users')->where(['user_id'=>array('IN',$user_ids)])->select();
                foreach ($users as $k => $v) {
                    $users[$value['user_id']] = $v;
                }
                $this->assign('users',$users);
            }  
        }
        
        // print_r($users);die;
        if (!empty($order_ids)) {
            $goods = D('Pindanorder')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['tuan_id']] = $val['tuan_id'];
            }
            $this->assign('goods', $goods);
        }
        // print_r($goods);die;
        if (!empty($order_ids)) {
            $this->assign('orders', D('Pindan')->itemsByIds($order_ids));
        }
        $this->assign('products', D('Pindan')->itemsByIds($goods_ids));
        // dump(D('Pindan')->itemsByIds($goods_ids));die;
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Paddress')->itemsByIds($address_ids));
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display();
    }

    //审核分销商品售后
    public function tuan_refund_sale($order_id)
    {
        if(!$this->shop_id){
            $this->tuError(array('status' =>'login'));
        }
        if(false == ($detail = D('Pindanorder')->find($order_id))){
            $this->tuError('未找到该订单');
        }
        if(false == ($details = D('Pindan')->find($detail['tuan_id']))){
            $this->tuError('未找到该商品');
        }
        if($detail['closed'] ==1){
            $this->tuError('该商品已经下架');
        }

        if(false == D('Shop')->check_shop_user_id($detail['shop_id'],$detail['user_id'])){
            $this->tuError('您不能购买自己的产品');
        }
        $data = array(
            'tuan_id' => $detail['tuan_id'], 
            'num' => $detail['num'], 
            'user_id' => $detail['user_id'], 
            'shop_id' => $detail['shop_id'], 
            'create_time' => NOW_TIME, 
            'create_ip' => get_client_ip(), 
            'total_price' => $detail['total_price'] , 
            'mobile_fan' => $detail['mobile_fan'], 
            'need_pay' => $detail['total_price'], 
            'status' => 0, 
            'is_mobile' => 1
        );
        if($order_id = D('Pindanorder')->add($data) && D('Pindanorder')->where(['order_id'=>$order_id])->save(['status'=>4])){
            $this->tuSuccess( '审核成功，新的订单已经生成',U('goods/tuan_index'));
        }else{
            $this->tuError('审核失败');
        }
    }

    //所有订单
    public function tuan_list()
    {
        $this->status = array('IN',array(0,1,2,3,4,5,6,7,8));
        $this->showdatas();
        $this->display();
    }

    //已付款订单
    public function tuan_over(){
        $this->status = 1;
        $this->showdatas();
        $this->display();
    }

    //确认收货
    public function tuan_complete(){
        $this->status = 2;
        $this->showdatas();
        $this->display();
    }

    //已完成
    public function tuan_ok(){
        $this->status = 8;
        $this->showdatas();
        $this->display();
    }

    //剩下的控制器
    public function showdatas() {
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'status' => $this->status ,'shop_id'=> $this->shop_id );
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars') ) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        if (isset($_GET['profit']) || isset($_POST['profit'])) {
            $profit = (int) $this->_param('profit');
            if ($profit != 999) {
                $map['is_profit'] = $profit;
            }
            $this->assign('profit', $profit);
        } else {
            $this->assign('profit', 999);
        }
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
        }
        $this->assign('keyword', $keyword);
        $Order = D('Pindanorder');
        // $map=array('status <2');
        $count = $Order->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $Order->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('create_time desc')->select();
        $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $value) {
            $goods = D('Pindanorder')->where(['id'=>$value['order_goods_id']])->find();
            $order = D('Pindan')->where(['order_id'=>$value['order_id']])->find();
            // $goods_ids[$goods['goods_id']] = $goods['goods_id'];
            $order_ids[$value['order_id']] = $value['order_id'];
            $addr_ids[$value['addr_id']] = $value['addr_id'];
            //var_dump($addr_ids);
            $shop_ids[$value['shop_id']] = $value['shop_id'];
//            $address_ids[$order['address_id']] = $order['address_id'];
//            var_dump($address_ids);
            $user_ids[$value['user_id']]  = $value['user_id'];
            $goods_ids[$value['tuan_id']] = $value['tuan_id'];
            if(!empty($user_ids)){
                $users = D('Users')->where(['user_id'=>array('IN',$user_ids)])->select();
                foreach ($users as $k => $v) {
                    $users[$value['user_id']] = $v;
                }
                $this->assign('users',$users);
            }  
        }
        // dump($goods);die;
        if (!empty($order_ids)) {
            $this->assign('orders', D('Pindan')->itemsByIds($order_ids));
        }
        $this->assign('types',D('Pindanorder')->getType());
        $this->assign('products', D('Pindan')->itemsByIds($goods_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Paddress')->itemsByIds($addr_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);

    }

    //确认完成
    public function distribution(){
        $order_id = (int) $this->_get('order_id');
        $config = D('Setting')->fetchAll();
        $days = isset($config['site']['goods']) ? (int)$config['site']['goods'] : 15;
        $t = NOW_TIME - $days*86400;
        if (!$order_id) {
            $this->tuError('参数错误');
        }else if(!$order = D('Pindanorder')->find($order_id)){
            $this->tuError('该订单不存在');
        }else if($order['shop_id'] != $this->shop_id){
            $this->tuError('不能管理不是您的订单');
        }else if(($order['status'] != 2) && ($order['status']!=3)){
            $this->tuError('该订单状态不正确，不能发货');
        }else{
            D('Pindanorder')->overOrder($order_id); //发货订单接口
            $this->tuSuccess('确认订单完成，资金已结算', U('goods/tuan_ok'));
        }
        $this->tuError('确认订单失败');
    }

    //分销商品发货       ------新增部分
    public function tuan_wait()
    {
        import('ORG.Util.Page'); 
        $Order = D('Pindanorder');
        // $map=array('status <2');
        $map = ['status'=>1];
        $count = $Order->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $Order->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('create_time desc')->select();
        $user_ids = $order_ids = $shop_ids = $addr_ids = array();
        foreach ($list as $key => $value) {
            $goods = D('Pindanorder')->where(['id'=>$value['order_goods_id']])->find();
            $order = D('Pindan')->where(['order_id'=>$value['order_id']])->find();
            // $goods_ids[$goods['goods_id']] = $goods['goods_id'];
            $order_ids[$value['order_id']] = $value['order_id'];
            $addr_ids[$order['addr_id']] = $order['addr_id'];
            $shop_ids[$order['shop_id']] = $order['shop_id'];  
            $address_ids[$order['address_id']] = $order['address_id'];  
            $user_ids[$value['user_id']]  = $value['user_id']; 
            $goods_ids[$value['tuan_id']] = $value['tuan_id'];
            if(!empty($user_ids)){
                $users = D('Users')->where(['user_id'=>array('IN',$user_ids)])->select();
                foreach ($users as $k => $v) {
                    $users[$value['user_id']] = $v;
                }
                $this->assign('users',$users);
            }  
        }
        // dump($goods);die;
        if (!empty($order_ids)) {
            $this->assign('orders', D('Pindan')->itemsByIds($order_ids));
        }
        $this->assign('products', D('Pindan')->itemsByIds($goods_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Paddress')->itemsByIds($address_ids));
        $this->assign('logistics', $logistics = D('Logistics')->where(array('closed'=>0))->select());
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display();
    }

    //分销商品点评回复
    public function tuan_reply($order_id)
    {
        $order_id = (int) $order_id;
        $detail = D('Pindandianping')->where(['order_id'=>$order_id])->find();
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->tuError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                // print_r($data);die;
                if (D('Pindandianping')->save($data)) {
                    $this->tuSuccess('回复成功', U('goods/tuan_list'));
                }
            }
            $this->tuError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }


	public function child($parent_id=0){
        $datas = D('Goodscate')->fetchAll();
        $str = '';

        foreach($datas as $var){
            if($var['parent_id'] == 0 && $var['cate_id'] == $parent_id){
         
                foreach($datas as $var2){

                    if($var2['parent_id'] == $var['cate_id']){
                        $str.='<option value="'.$var2['cate_id'].'">'.$var2['cate_name'].'</option>'."\n\r";
           
                        foreach($datas as $var3){
                            if($var3['parent_id'] == $var2['cate_id']){
                                
                               $str.='<option value="'.$var3['cate_id'].'">&nbsp;&nbsp;--'.$var3['cate_name'].'</option>'."\n\r"; 
                                
                            }
                            
                        }
                    }  
                }
                             
              
            }           
        }
        echo $str;die;
    }
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('产品名称不能为空');
        }
		
		$data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('副标题不能为空');
        }
	
		$data['guige'] = htmlspecialchars($data['guige']);
        if (empty($data['guige'])) {
            $this->tuError('副标题不能为空');
        }
		$data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        } 
		$data['is_reight'] = (int) $data['is_reight'];
		
		$data['weight'] = (int) $data['weight'];
		if ($data['is_reight'] == 1) {
			if (empty($data['weight'])) {
             	$this->tuError('重量不能为空');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if ($data['is_reight'] == 1) {
			if (empty($data['kuaidi_id'])) {
				$this->tuError('运费模板不能为空');
			}
		}	
        $data['shop_id'] = $this->shop_id;
        $shopdetail = D('Shop')->find($this->shop_id);
        $parent_id=$this->_post('parent_id', false);
        if(empty($parent_id)){
            $this->tuError('请选择一级分类');
        }
        $data['parent_id']=$parent_id;
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }
		 $Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
		 $parent_id = $Goodscate['parent_id'];
		 if ($parent_id == 0) {
			$this->tuError('请选择二级分类');
		 }
        $data['shopcate_id'] = (int) $data['shopcate_id'];
		
		
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['city_id'] = $this->shop['city_id'];
		
		$data['goods_type'] = I('goods_type');
		
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        } 
		$data['mall_price'] = (float) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }

        $data['ping_money'] = (float) ($data['ping_money']);
        if(empty($data['ping_money'])){
            $this->tuError('拼团价格不能为空');
        }

        $data['is_pintuan'] = (int) ($data['is_pintuan']);
        if($data['is_pintuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }

        $data['readi']=htmlspecialchars($data['readi']);
        $data['numbers']=htmlspecialchars($data['numbers']);
        $data['huifu_money']=htmlspecialchars($data['huifu_money']);
        if($data['readi']==1){
            if(empty($data['numbers'])){
                $this->tuError('请填写销售数量');
            }
            if(empty($data['huifu_money'])){
                $this->tuError('请填写恢复价格');
            }
        }
        $data['day_date']=htmlspecialchars($data['day_date']);
        $data['huifu2_money']=htmlspecialchars($data['huifu2_money']);
        if($data['readi']==2){
            if(empty($data['day_date'])){
                $this->tuError('请选择日期');
            }
            if (!isDate($data['day_date'])) {
                $this->tuError('过期时间格式不正确');
            }
            if(empty($data['huifu2_money'])){
                $this->tuError('请填写恢复价格');
            }
        }
        $data['select5'] = (int) $data['select5'];
        $data['use_integral'] = (int) $data['use_integral'];
//		//商城检测积分合法性开始
//		if (!D('Goods')->check_add_use_integral($data['use_integral'],$data['mall_price'])) {//传2参数
//            $this->tuError(D('Goods')->getError(), 3000, true);
//        }
//		//商城检测积分合法性结束
//        $data['instructions'] = SecurityEditorHtml($data['instructions']);
        if (empty($data['instructions'])) {
            $this->tuError('购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        } $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('商品详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        } $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('过期时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('过期时间格式不正确');
        }
		$data['is_vs1'] = (int) $data['is_vs1'];
		$data['is_vs2'] = (int) $data['is_vs2'];
		$data['is_vs3'] = (int) $data['is_vs3'];
		$data['is_vs4'] = (int) $data['is_vs4'];
		$data['is_vs5'] = (int) $data['is_vs5'];
		$data['is_vs6'] = (int) $data['is_vs6'];
		$data['is_vs7'] = (int) $data['is_vs7'];
		$data['is_vs8'] = (int) $data['is_vs8'];
        $data['is_vs9'] = (int) $data['is_vs9'];

		$data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        
		$data['profit_enable'] = (int) $data['profit_enable'];
        $data['profit_rate1'] = (int) $data['profit_rate1'];
        $data['profit_rate2'] = (int) $data['profit_rate2'];
        $data['profit_rate3'] = (int) $data['profit_rate3'];
		
		if($res = D('Goods')->where(array('title'=>$data['title'],'details'=>$data['details'],'end_date'=>$data['end_date']))->find()){
			$this->tuError('请勿重复添加商品');
		}
		
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['sold_num'] = 0;
        $data['view'] = 0;
        $data['is_mall'] = 1;
		$data['audit'] = 1;
        return $data;
    }

    public function edit($goods_id = 0) {
        if ($goods_id = (int) $goods_id) {
            $obj = D('Goods');
            if (!$detail = $obj->find($goods_id)) {
                $this->error('请选择要编辑的商品');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->error('请不要试图越权操作其他人的内容');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['goods_id'] = $goods_id;
                if (!empty($detail['wei_pic'])) {
                    if (true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($goods_id, 3);
                        $data['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($goods_id, 3);
                    $data['wei_pic'] = $wei_pic;
                }

                if (false !== $obj->save($data)) {
                    if($data['is_yuyue']==1){
                        D('Goods')->where(['goods_id'=>$goods_id])->save(['mall_price'=>$data['yuyue_money']]);
                    }
					$photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Goodsphoto')->upload($goods_id, $photos);
                    }					
                    $this->shuxin($goods_id);
					$this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性
					
                    $this->tuSuccess('操作成功', U('goods/index'));
                }
                $this->tuError('操作失败');
            } else {
          
				$goodsInfo=D('Goods')->where('goods_id='.I('GET.goods_id',0))->find();
				$this->assign('goodsInfo',$goodsInfo);
				$this->assign('goodsType',M("TpGoodsType")->select());
                $shop_user= D('Shop')->where(['shop_id'=>$detail['shop_id']])->find();
                $this->assign('detail', $obj->_format($detail));
				$this->assign('parent_id',D('Goodscate')->getParentsId($detail['cate_id']));
				$this->assign('attrs', D('Integralgoodsattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                //$this->assign('cates', D('Goodscate')->fetchAll());
                $this->assign('shop',$shop_user);

                $shop_tel=$this->_CONFIG['goods']['shop_tel'];
                if($shop_user['mobile']==$shop_tel){
                    $this->assign('is_plat_admin', 1);
                    $this->assign('cates',D('Goodscate')->fetchAll());
                    $this->assign('parent',D('Goodscate')->select());
                }else{
                    $this->assign('is_plat_admin', 0);
                    $this->assign('cates',D('Goodscate')->where(array('cate_id'=>$shop_user['goods_cate']))->select());
                    $this->assign('parent',D('Goodscate')->where(['cate_id'=>$shop_user['goods_parent_id']])->select());
                }
				$this->assign('photos', D('Goodsphoto')->getPics($goods_id));
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的商品');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('产品名称不能为空');
        } $data['shop_id'] = (int) $this->shop_id;
        if (empty($data['shop_id'])) {
            $this->tuError('商家不能为空');
        }

		$data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('副标题不能为空');
        }

		$data['guige'] = htmlspecialchars($data['guige']);
        if (empty($data['guige'])) {
            $this->tuError('规格不能为空');
        }

		$data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        } 
		$data['is_reight'] = (int) $data['is_reight'];

		$data['weight'] = (int) $data['weight'];
		if ($data['is_reight'] == 1) {
			if (empty($data['weight'])) {
             	$this->tuError('重量不能为空');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if ($data['is_reight'] == 1) {
			if (empty($data['kuaidi_id'])) {
				$this->tuError('运费模板不能为空');
			}
		}	
        $shopdetail = D('Shop')->find($this->shop_id);
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }

        $data['shopcate_id'] = (int) $data['shopcate_id'];
        $data['area_id'] = $this->shop['area_id'];
        $data['business_id'] = $this->shop['business_id'];
        $data['city_id'] = $this->shop['city_id'];
		$data['goods_type'] = I('goods_type');
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        } 
        $data['mall_price'] = (float) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }

        $data['ping_money'] = (float) ($data['ping_money']);
        if(empty($data['ping_money'])){
            $this->tuError('拼团价格不能为空');
        }

        $data['is_pintuan'] = (int) ($data['is_pintuan']);
        if($data['is_pintuan']==1){
            $hong=D('Envelope')->where(['shop_id'=>$this->shop_id,'type'=>2,'closed'=>0,'status'=>1])->find();
            if(empty($hong)){
                $this->tuError('您未发布拼团红包，请您先发布拼团红包');
            }
        }
	
	 $data['readi']=htmlspecialchars($data['readi']);
        $data['numbers']=htmlspecialchars($data['numbers']);
        $data['huifu_money']=htmlspecialchars($data['huifu_money']);
        if($data['readi']==1){
            if(empty($data['numbers'])){
                $this->tuError('请填写销售数量');
            }
            if(empty($data['huifu_money'])){
                $this->tuError('请填写恢复价格');
            }
        }
        $data['day_date']=htmlspecialchars($data['day_date']);
        $data['huifu2_money']=htmlspecialchars($data['huifu2_money']);
        if($data['readi']==2){
            if(empty($data['day_date'])){
                $this->tuError('请选择日期');
            }
            if (!isDate($data['day_date'])) {
                $this->tuError('过期时间格式不正确');
            }
            if(empty($data['huifu2_money'])){
                $this->tuError('请填写恢复价格');
            }
        }
	
        $data['monry']=(float) ($data['mall_price']);
        $data['is_yuyue'] = (int) $data['is_yuyue'];
        $data['yuyue_time'] = htmlspecialchars($data['yuyue_time']);
        $data['yuyue_money'] = (float) $data['yuyue_money'];
        $data['explain'] = htmlspecialchars($data['explain']);
        $shop=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
        if($data['is_yuyue']==1 && $shop['is_yuyue']==0){
            $this->tuError('请到商城设置开启支持预约购买后，再上传预约产品');
        }
        if($data['is_yuyue']==1 && empty($data['yuyue_money'])){
            $this->tuError('请填写预订金额');
        }
        if($data['is_yuyue']==1 && empty($data['explain'])){
            $this->tuError('请填写出售说明');
        }
        if($data['is_yuyue']==1){
            if($data['yuyue_money']>=$data['mall_price']){
                $this->tuError('预约价格不能大于商城价格');
            }
        }

        if($data['is_yuyue']==1 && empty($data['yuyue_time'])){
            $this->tuError('请填写发货时间');
        }

        if (empty($data['instructions'])) {
            $this->tuError('购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        } $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->tuError('商品详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        } $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->tuError('过期时间不能为空');
        }
        if (!isDate($data['end_date'])) {
            $this->tuError('过期时间格式不正确');
        }
		$data['is_vs1'] = (int) $data['is_vs1'];
		$data['is_vs2'] = (int) $data['is_vs2'];
		$data['is_vs3'] = (int) $data['is_vs3'];
		$data['is_vs4'] = (int) $data['is_vs4'];
		$data['is_vs5'] = (int) $data['is_vs5'];
		$data['is_vs6'] = (int) $data['is_vs6'];
		$data['is_vs7'] = (int) $data['is_vs7'];
		$data['is_vs8'] = (int) $data['is_vs8'];
        $data['is_vs9'] = (int) $data['is_vs9'];

		$data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];
		$data['profit_enable'] = (int) $data['profit_enable'];
        $data['profit_rate1'] = (int) $data['profit_rate1'];
        $data['profit_rate2'] = (int) $data['profit_rate2'];
        $data['profit_rate3'] = (int) $data['profit_rate3'];
        $data['orderby'] = (int) $data['orderby'];
		$data['audit'] = 1;
        return $data;
    }
	  public function ajax($cate_id,$goods_id=0){
        if(!$cate_id = (int)$cate_id){
            $this->error('请选择正确的分类');
        }
        if(!$detail = D('Goodscate')->find($cate_id)){
            $this->error('请选择正确的分类');
        }
        $this->assign('cate',$detail);
        $this->assign('attrs',D('Goodscateattr')->order(array('orderby'=>'asc'))->where(array('cate_id'=>$cate_id))->select());
        if($goods_id){
            $this->assign('detail',D('Goods')->find($goods_id));
            $this->assign('maps',D('GoodsCateattr')->getAttrs($goods_id));
        }
        $this->display();
    }
	//置顶
	public function goods_top($goods_id = 0,$check_price,$check_price_money=0){
        if(IS_AJAX){
			$obj = D('Goods'); 
            $goods_id = I('goods_id', 0, 'trim,intval');
            if(!($detail = $obj->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品ID【'.$goods_id.'】不存在'));
            }
            if($detail['audit'] !=1){
                  $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品未通过审核，不能开启竞价'));
            }
            $check_price = I('check_price');
            $check_price_money = I('check_price_money');
            $end_time = strtotime(I('end_time'));
            // print_r(strtotime($end_time));die;
            // $this->(array('status' => 'error', 'msg' => strtotime($eajaxReturnnd_time)));
            $status = M('Jingjia')->where(['id'=>1])->find();
            if(!$check_price){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '暂未出价'));
            }
            // if(!$end_time){
            //     $this->ajaxReturn(array('status' => 'error', 'msg' => '必须选择置顶时间'));
            // }
            if($status['status'] ==1){
                $this->ajaxReturn(array('status'=>'error','msg'=>'竞价未开启'));
            }
            if(($status['min_price'])>$check_price){
                $this->ajaxReturn(array('status'=>'error','msg'=>'最低竞价金额￥'.$status['min_price']));
            }
			
			if(false == $obj->top_time($goods_id,$check_price,$check_price_money,$end_time)){
				$this->ajaxReturn(array('status' => 'error', 'msg' => $obj->getError()));
			}else{
				$this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', U('goods/index')));
			}
        }
    }
	
    //取消竞价
    public function update_top($goods_id)
    {
        if(!($goods = D('Goods')->find($goods_id))){
            $this->tuError('请选择要操作的商品');
        }
        if($goods['is_tui'] ==0){
            $this->tuError('该商品暂未参加竞价');
        }
        //查询开启竞价时间
        if(NOW_TIME <($goods['start_time']+86400)){
            $time = (int)(($goods['start_time']+86400 - NOW_TIME)/60);
            $this->tuError('未到规定时间，无法取消，需等待'.$time.'分钟');
        }
        if(!false == D('Goods')->where(['goods_id'])->save(array('check_price'=>0,'is_tui'=>0))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }	


 public function shuxin($goods_id){
         if($_POST['item']){
             $spec = M('TpSpec')->getField('id,name'); 
             $specItem = M('TpSpecItem')->getField('id,item');
                          
             $specGoodsPrice = M("TpSpecGoodsPrice"); 
             $specGoodsPrice->where('goods_id = '.$goods_id)->delete(); 
             foreach($_POST['item'] as $k => $v){
                   $v['price'] = trim($v['price']);
                   $v['pin_price'] = trim($v['pin_price']);
                   $store_count = $v['store_count'] = trim($v['store_count']);
                   $v['bar_code'] = trim($v['bar_code']);
                   $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'pin_price'=>$v['pin_price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
             }             
             $specGoodsPrice->addAll($dataList);             
         }   
         refresh_stock($goods_id); 

    }

    //0元购
    public function shuxins($goods_id){
        if($_POST['item']){
            $spec = D('Zeroelementspec')->getField('id,name');
            $specItem = D('Zeroelementspecitem')->getField('id,item');

            $specGoodsPrice = D("Zeroelementprice");
            $specGoodsPrice->where('goods_id = '.$goods_id)->delete();
            foreach($_POST['item'] as $k => $v){
                $v['price'] = trim($v['price']);
                $v['yun_price'] = trim($v['yun_price']);
                $store_count = $v['store_count'] = trim($v['store_count']);
                $v['bar_code'] = trim($v['bar_code']);
                $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'yun_price'=>$v['yun_price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);
            }
            $specGoodsPrice->addAll($dataList);
        }
        refresh_stock($goods_id);

    }


     /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('TpSpec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)        
            $specList[$k]['spec_item'] = D('TpSpecItem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项                
        $items_id = M('TpSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);       
        if($goods_id){
           $specImageList = M('TpSpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');                 
        }        
        $this->assign('specImageList',$specImageList);
        
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');        
    }    


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框=============0元购
     */
    public function ajaxGetSpecSelectS(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('Zeroelementspec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = D('Zeroelementspecitem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = D('Zeroelementprice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        if($goods_id){
            $specImageList = D('Zeroelementspecimg')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);

        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_selects');
    }

     /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id     
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);        
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
         $clo_name = array_keys($spec_arr2);         
         $spec_arr2 = combineDika($spec_arr2);            
                       
         $spec = M('TpSpec')->getField('id,name'); 
         $specItem = M('TpSpecItem')->getField('id,item,spec_id');
         $keySpecGoodsPrice = M('TpSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,pin_price,store_count,bar_code');
                          
       $str = "<table class='table table-bordered' id='spec_input_tab'>";
       $str .="<tr>";       
       foreach ($clo_name as $k => $v) {
           $str .=" <td><b>{$spec[$v]}</b></td>";
       }    
        $str .="<td><b>价格</b></td>
                <td><b>拼单价格</b></td>
               <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
       foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }   
            ksort($item_key_name);            
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);
            
            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][pin_price] ? false : $keySpecGoodsPrice[$item_key][pin_price] = 0; // 拼单价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
           $str .="<td><input name='item[$item_key][pin_price]' value='{$keySpecGoodsPrice[$item_key][pin_price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";            
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";           
       }
        $str .= "</table>";
       return $str;   
    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id==========================0元购
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInputS($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2);

        $spec = D('Zeroelementspec')->getField('id,name');
        $specItem = D('Zeroelementspecitem')->getField('id,item,spec_id');
        $keySpecGoodsPrice = D('Zeroelementprice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,yun_price,store_count,bar_code');
        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        foreach ($clo_name as $k => $v) {
            $str .=" <td><b>{$spec[$v]}</b></td>";
        }
        $str .="<td><b>价格</b></td>
                <td><b>运费价格</b></td>
               <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
        foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][yun_price] ? false : $keySpecGoodsPrice[$item_key][yun_price] = 0; // 运费价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][yun_price]' value='{$keySpecGoodsPrice[$item_key][yun_price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";           
       }
        $str .= "</table>";
       return $str;   
    }
	
	
	
	
	//动态获取商品属性入框根据不同的数据返回不同的输入框类型
    public function ajaxGetAttrInput(){
		$goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
		$type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
		$str = $this->getAttrInput($goods_id,$type_id);
        exit($str);
    }

    //动态获取商品属性入框根据不同的数据返回不同的输入框类型======0元购
    public function ajaxGetAttrInputS(){
        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        //var_dump($_REQUEST['goods_id']);die;
        $type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
        $str = $this->getAttrInputS($goods_id,$type_id);
        exit($str);
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        //var_dump($goods_id);die;
        $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
        exit($str);
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框==========0元购
     */
    public function ajaxGetSpecInputS(){

        $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
        $str = $this->getSpecInputS($goods_id,$_POST['spec_arr']);
        exit($str);
    }



	  /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id){
		
	  
		
        $attributeList = M('tpGoodsAttribute')->where(array('type_id'=>$type_id))->select();  
        foreach($attributeList as $key => $val){                                                                        
            
            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
             //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){                                        
                            $str .= "<tr class='attr_{$val['attr_id']}'>";            
                            $addDelAttr = ''; //加减符号
                            //单选属性或者复选属性
                            if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                                if($k == 0)                                
                                    $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";                                                                    
                                else                                
                                     $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";                               
                            }

                            $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";            

                            //手工录入
                            if($val['attr_input_type'] == 0){
                                $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                            }
                            //从下面的列表中选择（一行代表一个可选值）
                            if($val['attr_input_type'] == 1){
                                $str .= "<select name='attr_{$val['attr_id']}[]'>";
                                $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                                foreach($tmp_option_val as $k2=>$v2){
                                    //编辑的时候有选中值
                                    $v2 = preg_replace("/\s/","",$v2);
                                    if($v['attr_value'] == $v2)
                                        $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                                    else
                                        $str .= "<option value='{$v2}'>{$v2}</option>";
                                }
                                $str .= "</select>";                
                            }
                            //多行文本框
                            if($val['attr_input_type'] == 2){
                                $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                            }                                                        
                            $str .= "</td></tr>";
            }                        
            
        }        
        return  $str;
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型=============0元购
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInputS($goods_id,$type_id){
        //var_dump($goods_id);die;
        $attributeList = D('Zeroelementattribute')->where(array('type_id'=>$type_id))->select();
        foreach($attributeList as $key => $val){

            $curAttrVal = $this->getGoodsAttrValS(NULL,$goods_id, $val['attr_id']);
            //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; //加减符号
                //单选属性或者复选属性
                if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                    if($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";

                //手工录入
                if($val['attr_input_type'] == 0){
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                //从下面的列表中选择（一行代表一个可选值）
                if($val['attr_input_type'] == 1){
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                    foreach($tmp_option_val as $k2=>$v2){
                        //编辑的时候有选中值
                        $v2 = preg_replace("/\s/","",$v2);
                        if($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                }
                //多行文本框
                if($val['attr_input_type'] == 2){
                    $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                }
                $str .= "</td></tr>";
            }

        }
        return  $str;
    }
    
    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return M('tpGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->select(); 
        if($goods_id > 0 && $attr_id > 0)
            return M('tpGoodsAttr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();        
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组=========0元购
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrValS($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return D('zeroelementattr')->where(array('goods_attr_id'=>$goods_attr_id))->select();
        if($goods_id > 0 && $attr_id > 0)
            return D('zeroelementattr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();
    }
	
	 /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type){  
     
                
         // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除        
        if($goods_type == 0)  {
            M('tpGoodsAttr')->where(array('goods_id'=>$goods_id))->delete(); 
            return;
        }
        
            $GoodsAttrList = M('tpGoodsAttr')->where(array('goods_id'=>$goods_id))->select();
            
            $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
            foreach($GoodsAttrList as $k => $v){                
                $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
            }            
                              
            // post提交的属性以attr_id_和值的组合为键名    
            $post_goods_attr = array();
	
            foreach($_POST as $k => $v){
                $attr_id = str_replace('attr_','',$k);
                if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                   continue;                                 
               foreach ($v as $k2 => $v2){                      
                   $v2 = str_replace('_', '', $v2); //替换特殊字符
                   $v2 = str_replace('@', '', $v2); //替换特殊字符
                   $v2 = trim($v2);
                   
                   if(empty($v2))
                       continue;
                   $tmp_key = $attr_id."_".$v2;
                   $attr_price = $_POST["attr_price_$attr_id"][$k2]; 
                   $attr_price = $attr_price ? $attr_price : 0;
                   if(array_key_exists($tmp_key , $old_goods_attr)){  
				   //如果这个属性原来就存在 
                       if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){    
					   	//并且价格不一样就做更新处理                   
                           $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];                         
                           M('tpGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));                       
                       }
                   }else{
					   //否则这个属性 数据库中不存在 说明要做删除操作
                       M('tpGoodsAttr')->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));                       
                   }
                   unset($old_goods_attr[$tmp_key]);
               }
            }     
            //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
            foreach($old_goods_attr as $k => $v){                
               M('tpGoodsAttr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
            }                       
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr=============0元购
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttrS($tuan_id,$goods_type){


        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if($goods_type == 0)  {
           D('zeroelementattr')->where(array('goods_id'=>$tuan_id))->delete();
            return;
        }

        $GoodsAttrList = D('zeroelementattr')->where(array('goods_id'=>$tuan_id))->select();

        $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
        foreach($GoodsAttrList as $k => $v){
            $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
        }

        // post提交的属性以attr_id_和值的组合为键名
        $post_goods_attr = array();

        foreach($_POST as $k => $v){
            $attr_id = str_replace('attr_','',$k);
            if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2){
                $v2 = str_replace('_', '', $v2); //替换特殊字符
                $v2 = str_replace('@', '', $v2); //替换特殊字符
                $v2 = trim($v2);

                if(empty($v2))
                    continue;
                $tmp_key = $attr_id."_".$v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if(array_key_exists($tmp_key , $old_goods_attr)){
                    //如果这个属性原来就存在
                    if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){
                        //并且价格不一样就做更新处理
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        D('zeroelementattr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));
                    }
                }else{
                    //否则这个属性 数据库中不存在 说明要做删除操作
                    D('zeroelementattr')->add(array('goods_id'=>$tuan_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }

        }
        //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
        foreach($old_goods_attr as $k => $v){
            D('zeroelementattr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
        }
    }

    //0元购竞价
    public function tuan_top($goods_id = 0,$check_price,$check_price_money=0){
        if(IS_AJAX){
            $obj = D('Pindan');
            $goods_id = I('goods_id', 0, 'trim,intval');
            if(!($detail = $obj->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品ID【'.$goods_id.'】不存在'));
            }
            if($detail['audit'] !=1){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品未通过审核，不能开启竞价'));
            }
            $check_price = I('check_price');
            $check_price_money = I('check_price_money');
            $end_time = strtotime(I('end_time'));
            $status = M('Jingjia')->where(['id'=>1])->find();
            if(!$check_price){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '暂未出价'));
            }

            if($status['status'] ==1){
                $this->ajaxReturn(array('status'=>'error','msg'=>'竞价未开启'));
            }
            if(($status['min_price'])>$check_price){
                $this->ajaxReturn(array('status'=>'error','msg'=>'最低竞价金额￥'.$status['min_price']));
            }

            if(false == $obj->top_time($goods_id,$check_price,$check_price_money,$end_time)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => $obj->getError()));
            }else{
                $this->ajaxReturn(array('status' => 'success', 'msg' => '出价成功，一小时后可再次出价', U('goods/tuan_index')));
            }
        }
    }


    //0元购取消竞价
    public function update_tuantop($tuan_id)
    {
        if(!($goods = D('Pindan')->find($tuan_id))){
            $this->tuError('请选择要操作的商品');
        }
        if($goods['is_tui'] ==0){
            $this->tuError('该商品暂未参加竞价');
        }
        //查询开启竞价时间
        if(NOW_TIME <($goods['start_time']+86400)){
            $time = (int)(($goods['start_time']+86400 - NOW_TIME)/60);
            $this->tuError('未到规定时间，无法取消，需等待'.$time.'分钟');
        }
        if(!false == D('Pindan')->where(['tuan_id'])->save(array('check_price'=>0,'is_tui'=>0))){
            $this->tuSuccess('取消竞价成功，商品已退出排行');
        }else{
            $this->tuError('取消失败');
        }
    }

    public function goods_cat(){
        if(IS_AJAX){
            $cate_id=$_POST['codeid'];
            $goods=M('goods_cate')->where(['parent_id'=>$cate_id])->select();
            if(!empty($goods)){
                echoJson(['ret'=>1,'data'=>$goods]);
            }
        }

    }

}
