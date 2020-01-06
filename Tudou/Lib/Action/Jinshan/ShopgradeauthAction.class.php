<?php
class ShopgradeAuthAction extends CommonAction{
    private $create_fields = array('grade_name', 'photo', 'money', 'gold', 'content', 'orderby');
    private $edit_fields = array('grade_name', 'photo', 'money', 'gold', 'content', 'orderby');
	private $edit_fields_jurisdiction = array('is_mall', 'is_tuan', 'is_ele','is_market','is_store','is_news', 'is_hotel', 'is_booking', 'is_farm', 'is_appoint', 'is_huodong', 'is_coupon', 'is_life', 'is_jifen', 'is_cloud','is_book','is_stock','is_edu','is_zhe','is_ktv', 'is_decorate','is_decorate_num','is_mall_num', 'is_tuan_num', 'is_ele_num','is_market_num','is_store_num','is_news_num', 'is_hotel_num', 'is_booking_num', 'is_farm_num', 'is_appoint_num', 'is_huodong_num', 'is_coupon_num', 'is_life_num', 'is_jifen_num', 'is_cloud_num', 'is_book_num', 'is_stock_num', 'is_edu_num', 'is_zhe_num', 'is_ktv_num');
	
	
    public function index(){
        $Shopgrade = D('ShopgradeAuth');
        import('ORG.Util.Page');
        $map = array('status'=>0);
        // dump($Shopgrade);die;
        $count = $Shopgrade->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Shopgrade->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		// foreach ($list as $k => $val){
  //           $list[$k]['shop_count'] = $Shopgrade->get_shop_count($val['grade_id']);
  //       }
        // print_r($list);dies;
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
    public function create(){
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('ShopgradeAuth');
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->tuSuccess('添加成功', U('Shopgradeauth/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['grade_name'] = htmlspecialchars($data['grade_name']);
        if (empty($data['grade_name'])) {
            $this->tuError('等级名称不能为空');
        }
		$data['content'] = htmlspecialchars($data['content']);
		if (empty($data['content'])) {
            $this->tuError('还是写下等级介绍吧');
        }
        return $data;
    }
	
	
    public function edit($grade_id = 0){
        if ($grade_id = (int) $grade_id){
            $obj = D('ShopgradeAuth');
            if(!($detail = $obj->find($grade_id))){
                $this->tuError('请先选择要编辑的分类');
            }
            if($this->isPost()){
                $data = $this->editCheck();
                $data['grade_id'] = $grade_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->tuSuccess('操作成功', U('Shopgradeauth/index'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $detail);
                $this->display();
            }
        } else{
            $this->tuError('请先选择要编辑的分类');
        }
    }
	
	
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['grade_name'] = htmlspecialchars($data['grade_name']);
        if (empty($data['grade_name'])) {
            $this->tuError('等级名称不能为空');
        }
		$data['content'] = htmlspecialchars($data['content']);
		if (empty($data['content'])) {
            $this->tuError('还是写下等级介绍吧');
        }
        return $data;
    }
    public function delete($grade_id = 0){
        if (is_numeric($grade_id) && ($grade_id = (int) $grade_id)) {
            $obj = D('Shopgradeauth');
            $obj->save(array('grade_id' => $grade_id, 'status' => 1));
            $this->tuSuccess('删除成功', U('Shopgradeauth/index'));
        } else {
            $grade_id = $this->_post('grade_id', false);
            if (is_array($grade_id)) {
                $obj = D('Shopgradeauth');
                foreach ($grade_id as $id) {
                    $obj->save(array('grade_id' => $id, 'status' => 1));
                }
                $this->tuSuccess('删除成功', U('Shopgradeauth/index'));
            }
            $this->tuError('请先选择要删除的分类');
        }
    }
	
	public function edit_jurisdiction($grade_id = 0){
		if ($grade_id = (int) $grade_id) {
            $obj = D('ShopgradeAuth');
            if (!($detail = $obj->find($grade_id))) {
                $this->tuError('请先选择要编辑的分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck_jurisdiction();
                $data['grade_id'] = $grade_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->tuSuccess('操作成功', U('Shopgradeauth/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请先选择要编辑的分类');
        }
    }
	
	 private function editCheck_jurisdiction(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields_jurisdiction);
        $data['is_mall'] = (int) $data['is_mall'];
		$data['is_tuan'] = (int) $data['is_tuan'];
		$data['is_ele'] = (int) $data['is_ele'];
		$data['is_market'] = (int) $data['is_market'];
		$data['is_store'] = (int) $data['is_store'];
		$data['is_news'] = (int) $data['is_news'];
		$data['is_hotel'] = (int) $data['is_hotel'];
		$data['is_booking'] = (int) $data['is_booking'];
		$data['is_farm'] = (int) $data['is_farm'];
		$data['is_appoint'] = (int) $data['is_appoint'];
		$data['is_huodong'] = (int) $data['is_huodong'];
		$data['is_coupon'] = (int) $data['is_coupon'];
		$data['is_life'] = (int) $data['is_life'];
		$data['is_jifen'] = (int) $data['is_jifen'];
		$data['is_cloud'] = (int) $data['is_cloud'];
		$data['is_book'] = (int) $data['is_book'];
		$data['is_stock'] = (int) $data['is_stock'];
		$data['is_edu'] = (int) $data['is_edu'];
		$data['is_zhe'] = (int) $data['is_zhe'];
		$data['is_ktv'] = (int) $data['is_ktv'];
        $data['is_decorate'] = (int) $data['is_decorate'];

        $data['is_decorate_num'] = (int) $data['is_decorate_num'];
		$data['is_mall_num'] = (int) $data['is_mall_num'];
		$data['is_tuan_num'] = (int) $data['is_tuan_num'];
		$data['is_ele_num'] = (int) $data['is_ele_num'];
		$data['is_market_num'] = (int) $data['is_market_num'];
		$data['is_store_num'] = (int) $data['is_store_num'];
		$data['is_news_num'] = (int) $data['is_news_num'];
		$data['is_hotel_num'] = (int) $data['is_hotel_num'];
		$data['is_booking_num'] = (int) $data['is_booking_num'];
		$data['is_farm_num'] = (int) $data['is_farm_num'];
		$data['is_appoint_num'] = (int) $data['is_appoint_num'];
		$data['is_huodong_num'] = (int) $data['is_huodong_num'];
		$data['is_coupon_num'] = (int) $data['is_coupon_num'];
		$data['is_life_num'] = (int) $data['is_life_num'];
		$data['is_jifen_num'] = (int) $data['is_jifen_num'];
		$data['is_cloud_num'] = (int) $data['is_cloud_num'];
		$data['is_book_num'] = (int) $data['is_book_num'];
		$data['is_stock_num'] = (int) $data['is_stock_num'];
		$data['is_edu_num'] = (int) $data['is_edu_num'];
		$data['is_zhe_num'] = (int) $data['is_zhe_num'];
		$data['is_ktv_num'] = (int) $data['is_ktv_num'];
        return $data;
    }
}