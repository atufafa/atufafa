<?php

class GoodsAction extends CommonAction {

       private $create_fields = array('title','intro','guige', 'num','is_reight','weight','kuaidi_id','shop_id', 'photo', 'cate_id', 'price', 'mall_price','settlement_price','use_integral','mobile_fan', 'commission', 'sold_num', 'orderby', 'views', 'instructions', 'details', 'end_date', 'orderby','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','profit_enable','profit_rate1','profit_rate2','profit_rate3','profit_rank_id',);
    private $edit_fields = array('title','intro','guige','num', 'is_reight','weight','kuaidi_id','shop_id', 'photo', 'cate_id', 'price', 'mall_price','settlement_price','use_integral','mobile_fan', 'commission', 'sold_num', 'orderby', 'views', 'instructions', 'details', 'end_date', 'orderby','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','profit_enable','profit_rate1','profit_rate2','profit_rate3','profit_rank_id',);
    public function _initialize() {
        parent::_initialize();
        $this->assign('ranks',D('Userrank')->fetchAll());
    }
    public function index() {
        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'is_mall' => 1,'city_id'=>$this->city_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($parent_id = (int) $this->_param('parent_id')) {
            $this->assign('parent_id', $parent_id);
        }

        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Goods');
            if ($goods_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($goods_id, 3); //购物类型是3
                $obj->save(array('goods_id'=>$goods_id,'wei_pic'=>$wei_pic));
                $photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Goodsphoto')->upload($goods_id, $photos);
                }

                $this->tuSuccess('添加成功', U('goods/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('cates', D('Goodscate')->fetchAll());
            $this->display();
        }
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
			if ($data['weight'] % 1 != 0) {
				$this->tuError('重量必须为1的倍数');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if ($data['is_reight'] == 1) {
			if (empty($data['kuaidi_id'])) {
				$this->tuError('运费模板不能为空');
			}
		}	
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->tuError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if (empty($shop)) {
            $this->tuError('请选择正确的商家');
        }
   
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }
		
		 $Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
		 $parent_id = $Goodscate['parent_id'];
		 if ($parent_id == 0) {
			$this->tuError('请选择二级分类');
		 }
		$data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        } $data['mall_price'] = (float) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }
        $data['settlement_price'] = (float) ($data['settlement_price']);
        if(empty($data['settlement_price'])){
            $this->tuError('结算价格必须填写');
        }
        $data['mobile_fan'] = (float) ($data['mobile_fan']);
        if($data['mobile_fan'] < 0 || $data['mobile_fan'] >= $data['settlement_price']){
            $this->tuError('手机下单优惠金额不正确！不能大于等于结算价格');
        }
        $data['commission'] = (float) ($data['commission']);
        if ($data['commission'] < 0) {
            $this->tuError('佣金不能为负数');
        }
		$data['views'] = (int) $data['views'];
      	$data['instructions'] = SecurityEditorHtml($data['instructions']);
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
        $data['use_integral'] = (int) $data['use_integral'];
        $data['sold_num'] = (int) $data['sold_num'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int) $data['orderby'];
        $data['is_mall'] = 1;
        $data['profit_enable'] = (int) $data['profit_enable'];
        $data['profit_rate1'] = (int) $data['profit_rate1'];
        $data['profit_rate2'] = (int) $data['profit_rate2'];
        $data['profit_rate3'] = (int) $data['profit_rate3'];
        $data['profit_prestige'] = (int) $data['profit_prestige'];
        return $data;
    }

    public function edit($goods_id = 0) {
        if ($goods_id = (int) $goods_id) {

		$shop_ids = D('Goods')->find($goods_id);
		$shop_id = $shop_ids['shop_id'];
		$city_ids = D('Shop')->find($shop_id);
		$citys = $city_ids['city_id'];
		if ($citys != $this->city_id) {
	       $this->error('非法操作', U('goods/index'));
        }
            $obj = D('Goods');
            if (!$detail = $obj->find($goods_id)) {
                $this->tuError('请选择要编辑的商品');
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
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Goodsphoto')->upload($goods_id, $photos);
                    }
                    $this->tuSuccess('操作成功', U('goods/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $obj->_format($detail));
				$this->assign('parent_id',D('Goodscate')->getParentsId($detail['cate_id']));
				$this->assign('attrs', D('Goodscateattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('cates', D('Goodscate')->fetchAll());
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
				$this->assign('photos', D('Goodsphoto')->getPics($goods_id));
				$this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$detail['shop_id'],'type'=>goods))->select());
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
			if ($data['weight'] % 1 != 0) {
				$this->tuError('重量必须为1的倍数');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if ($data['is_reight'] == 1) {
			if (empty($data['kuaidi_id'])) {
				$this->tuError('运费模板不能为空');
			}
		}	
		$data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->tuError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if (empty($shop)) {
            $this->tuError('请选择正确的商家');
        }
    
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }
		
		 $Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
		 $parent_id = $Goodscate['parent_id'];
		 if ($parent_id == 0) {
			$this->tuError('请选择二级分类');
		 }
	
		$data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } 
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        } 
        $data['mall_price'] = (float) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }
        $data['settlement_price'] = (float) ($data['settlement_price']);
        if(empty($data['settlement_price'])){
            $this->tuError('结算价格必须填写');
        }
        $data['mobile_fan'] = (float) ($data['mobile_fan']);
        if($data['mobile_fan'] < 0 || $data['mobile_fan'] >= $data['settlement_price']){
            $this->tuError('手机下单优惠金额不正确');
        }
        $data['commission'] = (float) ($data['commission']);
        if ($data['commission'] < 0 ||($data['commission'] > ($data['settlement_price']-$data['mobile_fan']) )) {
            $this->tuError('佣金不能为负数,佣金不能大于结算价格减去手机优惠');
        }
        $data['views'] = (int) $data['views'];
		$data['instructions'] = SecurityEditorHtml($data['instructions']);
      
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
        $data['use_integral'] = (int) $data['use_integral'];
        $data['sold_num'] = (int) $data['sold_num'];
        $data['orderby'] = (int) $data['orderby'];
        $data['profit_enable'] = (int) $data['profit_enable'];
        $data['profit_rate1'] = (int) $data['profit_rate1'];
        $data['profit_rate2'] = (int) $data['profit_rate2'];
        $data['profit_rate3'] = (int) $data['profit_rate3'];
        $data['profit_prestige'] = (int) $data['profit_prestige'];
        return $data;
    }

    public function delete($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
		$shop_ids = D('Goods')->find($goods_id);
		$shop_id = $shop_ids['shop_id'];
		$city_ids = D('Shop')->find($shop_id);
		$citys = $city_ids['city_id'];
		if ($citys != $this->city_id) {
	       $this->error('非法操作', U('goods/index'));
        }
            $obj = D('Goods');
            $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('goods/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                foreach ($goods_id as $id) {
                    $obj->save(array('goods_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('删除成功', U('goods/index'));
            }
            $this->tuError('请选择要删除的商家');
        }
    }

    public function audit($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
		$shop_ids = D('Goods')->find($goods_id);
		$shop_id = $shop_ids['shop_id'];
		$city_ids = D('Shop')->find($shop_id);
		$citys = $city_ids['city_id'];
		if ($citys != $this->city_id) {
	       $this->error('非法操作', U('goods/index'));
        }
            $obj = D('Goods');
            $r = $obj -> where('goods_id ='.$goods_id) -> find();
            if(empty($r['settlement_price'])){
                $this->tuError('不设置结算价格无法审核通过');
            }
            $obj->save(array('goods_id' => $goods_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('goods/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                $error = 0;
                foreach ($goods_id as $id) {
                    $r = $obj -> where('goods_id ='.$id) -> find();
                    if(empty($r['settlement_price'])){
                       $error++;
                        $this->tuError('遇到了结算价格没有设置的，该条无法审核通过');
                    }
                    $obj->save(array('goods_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('审核成功！'.$error.'条失败', U('goods/index'));
            }
            $this->tuError('请选择要审核的商品');
        }
    }
}
