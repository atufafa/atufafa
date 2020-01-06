<?php		
class ListAction extends CommonAction{
	 private $create_fields = array('title','city_id','cate_id','area_id','business_id','user_id','is_shop','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','urgent_date', 'top_date','photo','contact','mobile','qq','addr','views','money','lng','lat');

	  private $edit_fields = array('title','city_id','cate_id','area_id','business_id','user_id','is_shop','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','urgent_date', 'top_date','photo','contact','mobile','qq','addr','views','money','lng','lat');

      private $selledit_fields = array('title','city_id','cate_id','area_id','business_id','user_id','is_shop','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','urgent_date', 'top_date','photo','contact','mobile','qq','addr','views','money','lng','lat');
	//显示
	public function index(){
 	$Life = D('Lifes');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword){
            $map['qq|mobile|contact|title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
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
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Life->where($map)->order(array('life_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('cates', D('Llifeclass')->fetchAll());
        $this->assign('channelmeans', D('Lifetype')->select());
        $this->display();
	}

	//添加

    public function create(){
        if ($this->isPost()){
			$obj = D('Lifes');
            $data = $this->createCheck();
			$tag = $this->_post('tag', false);
            $tag = implode(',', $tag);
            $data['tag'] = $tag;
            
            $details = $this->_post('details', 'SecurityEditorHtml');
            if($words = D('Sensitive')->checkWords($details)) {
                $this->tuError('商家介绍含有敏感词：' . $words);
            }
            if($life_id = $obj->add($data)) {
                if ($details) {
                    D('Lifesdetails')->updateDetails($life_id, $details);
                }
                $photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Lifesphoto')->upload($life_id, $photos);
                }
                $this->tuSuccess('添加成功', U('list/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('cates', D('Llifeclass')->fetchAll());
            $this->assign('channelmeans', D('Lifetype')->select());
            $this->display();
        }
    }
     private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('标题不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->tuError('城市不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        $data['user_id'] = htmlspecialchars($data['user_id']);
        if (empty($data['user_id'])) {
            $this->tuError('用户不能为空');
        }
        $data['is_shop'] = (int) $data['is_shop'];
        $data['text1'] = htmlspecialchars($data['text1']);
        $data['text2'] = htmlspecialchars($data['text2']);
        $data['text3'] = htmlspecialchars($data['text3']);
		$data['text4'] = htmlspecialchars($data['text4']);
		$data['text5'] = htmlspecialchars($data['text5']);
        $data['num1'] = (int) $data['num1'];
        $data['num2'] = (int) $data['num2'];
        $data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];
        $data['urgent_date'] = htmlspecialchars($data['urgent_date']);
        $data['urgent_date'] = $data['urgent_date'] ? $data['urgent_date'] : TODAY;
        if (!empty($data['urgent_date']) && !isDate($data['urgent_date'])) {
            $this->tuError('火急日期格式不正确');
        }
        $data['top_date'] = htmlspecialchars($data['top_date']);
        $data['lng'] = htmlspecialchars(trim($data['lng']));
        $data['lat'] = htmlspecialchars(trim($data['lat']));
        $data['top_date'] = $data['top_date'] ? $data['top_date'] : TODAY;
        if (!empty($data['top_date']) && !isDate($data['top_date'])) {
            $this->tuError('置顶日期格式不正确');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (!empty($data['photo']) && !isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->tuError('联系人不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuError('电话不能为空');
        }
        if (!isMobile($data['mobile']) && !isPhone($data['mobile'])) {
            $this->tuError('电话格式不正确');
        }
        $data['qq'] = htmlspecialchars($data['qq']);
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['views'] = (int) $data['views'];
		$data['money'] = (float) ($data['money']);
        $data['create_time'] = NOW_TIME;
        $data['last_time'] = NOW_TIME + 86400 * 30;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    //编辑
     public function edit($life_id = 0){
        if ($life_id = (int) $life_id) {
            $obj = D('Lifes');
            if (!($detail = $obj->find($life_id))) {
                $this->tuError('请选择要编辑的生活信息');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['life_id'] = $life_id;
				$tag = $this->_post('tag', false);
				$tag = implode(',', $tag);
				$data['tag'] = $tag;
			
                $details = $this->_post('details', 'SecurityEditorHtml');
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuError('商家介绍含有敏感词：' . $words);
                }
                if (false !== $obj->save($data)) {
                    if ($details) {
                        D('Lifesdetails')->updateDetails($life_id, $details);
                    }
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Lifesphoto')->upload($life_id, $photos);
                    }
                    $this->tuSuccess('操作成功', U('list/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('cates', D('Llifeclass')->fetchAll());
                $this->assign('channelmeans', D('Lifetype')->select());
                $this->assign('cate', D('Llifeclass')->find($detail['cate_id']));
                $this->assign('ex', D('Lifesdetails')->find($life_id));
                $this->assign('attrs', D('Lifeclassattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('photos', D('Lifesphoto')->getPics($life_id));
				
				$this->assign('tags', D('Lifeclasstag')->order(array('orderby' => 'asc'))->where(array('cate_id' =>$detail['cate_id']))->select());
				$tags = explode(',', $detail['tag']);
                $this->assign('tag', $tag);
				
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的生活信息');
        }
    }
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('标题不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->tuError('城市不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        $data['user_id'] = htmlspecialchars($data['user_id']);
        if (empty($data['user_id'])) {
            $this->tuError('用户不能为空');
        }
        $data['is_shop'] = (int) $data['is_shop'];
        $data['text1'] = htmlspecialchars($data['text1']);
        $data['text2'] = htmlspecialchars($data['text2']);
        $data['text3'] = htmlspecialchars($data['text3']);
		$data['text4'] = htmlspecialchars($data['text4']);
		$data['text5'] = htmlspecialchars($data['text5']);
        $data['num1'] = (int) $data['num1'];
        $data['num2'] = (int) $data['num2'];
        $data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];
        $data['urgent_date'] = htmlspecialchars($data['urgent_date']);
        $data['urgent_date'] = $data['urgent_date'] ? $data['urgent_date'] : TODAY;
        if (!empty($data['urgent_date']) && !isDate($data['urgent_date'])) {
            $this->tuError('火急日期格式不正确');
        }
        $data['top_date'] = htmlspecialchars($data['top_date']);
        $data['lng'] = htmlspecialchars(trim($data['lng']));
        $data['lat'] = htmlspecialchars(trim($data['lat']));
        $data['top_date'] = $data['top_date'] ? $data['top_date'] : TODAY;
        if (!empty($data['top_date']) && !isDate($data['top_date']) && $data['top_date'] != '0000-00-00') {
            $this->tuError('置顶日期格式不正确');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (!empty($data['photo']) && !isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->tuError('联系人不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuError('电话不能为空');
        }
        if (!isMobile($data['mobile']) && !isPhone($data['mobile'])) {
            $this->tuError('电话格式不正确');
        }
        $data['qq'] = htmlspecialchars($data['qq']);
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['views'] = (int) $data['views'];
		$data['money'] = (float) ($data['money']);
        return $data;
    }

    //删除
 	public function delete($life_id = 0){
        if (is_numeric($life_id) && ($life_id = (int) $life_id)) {
            $obj = D('Lifes');
            $obj->save(array('life_id' => $life_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('list/index'));
        } else {
            $life_id = $this->_post('life_id', false);
            if (is_array($life_id)) {
                $obj = D('Lifes');
                foreach ($life_id as $id) {
                    $obj->save(array('life_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('list/index'));
            }
            $this->tuError('请选择要删除的生活信息');
        }
    }

    //审核
    public function audit($life_id = 0){
        if(is_numeric($life_id) && ($life_id = (int) $life_id)){
            $obj = D('Lifes');
			D('LifeSubscribe')->pushSubscribes($life_id);//推送分类信息返回真
            $obj->save(array('life_id' => $life_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('list/index'));
        }else{
            $life_id = $this->_post('life_id', false);
            if(is_array($life_id)){
                $obj = D('Lifes');
                foreach($life_id as $id){
					D('Lifessubscribe')->pushSubscribes($id);//推送分类信息返回真
                    $obj->save(array('life_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('批量审核成功', U('list/index'));
            }
            $this->tuError('请选择要审核的生活信息');
        }
    }


    //卖车信息列表
    public function sellindex(){
    $Life = D('Lifessell');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if($keyword){
            $map['qq|mobile|contact|title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
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
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Life->where($map)->order(array('life_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
            $list[$k]['city'] = D('City')->find($val['city_id']);
            $list[$k]['area'] = D('Area')->find($val['area_id']);
            $list[$k]['business'] = D('Business')->find($val['business_id']);
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('cates', D('Llifeclass')->fetchAll());
        $this->display();
    }

    //删除
    public function del($life_id=0){

         if (is_numeric($life_id) && ($life_id = (int) $life_id)) {
            $obj = D('Lifessell');
            $obj->save(array('life_id' => $life_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('list/index'));
        } else {
            $life_id = $this->_post('life_id', false);
            if (is_array($life_id)) {
                $obj = D('Lifessell');
                foreach ($life_id as $id) {
                    $obj->save(array('life_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('list/index'));
            }
            $this->tuError('请选择要删除的生活信息');
        } 
    }


    //审核
    public function shenhe($life_id=0){

         if(is_numeric($life_id) && ($life_id = (int) $life_id)){
            $obj = D('Lifessell');
            D('LifeSubscribe')->pushSubscribess($life_id);//推送分类信息返回真
            $obj->save(array('life_id' => $life_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('list/index'));
        }else{
            $life_id = $this->_post('life_id', false);
            if(is_array($life_id)){
                $obj = D('Lifessell');
                foreach($life_id as $id){
                    D('Lifessubscribe')->pushSubscribess($id);//推送分类信息返回真
                    $obj->save(array('life_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('批量审核成功', U('list/index'));
            }
            $this->tuError('请选择要审核的生活信息');
        }


    }


    //编辑
     public function selledit($life_id = 0){
        if ($life_id = (int) $life_id) {
            $obj = D('Lifessell');
            if (!($detail = $obj->find($life_id))) {
                $this->tuError('请选择要编辑的生活信息');
            }
            if ($this->isPost()) {
                $data = $this->selleditCheck();
                $data['life_id'] = $life_id;
                $tag = $this->_post('tag', false);
                $tag = implode(',', $tag);
                $data['tag'] = $tag;
            
                $details = $this->_post('details', 'SecurityEditorHtml');
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuError('商家介绍含有敏感词：' . $words);
                }
                if (false !== $obj->save($data)) {
                    if ($details) {
                        D('Lifesdetails')->updateDetails($life_id, $details);
                    }
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Lifesphoto')->upload($life_id, $photos);
                    }
                    $this->tuSuccess('操作成功', U('list/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('cates', D('Llifeclass')->fetchAll());
                $this->assign('channelmeans', D('Lifetype')->select());
                $this->assign('cate', D('Llifeclass')->find($detail['cate_id']));
                $this->assign('ex', D('Lifesdetails')->find($life_id));
                $this->assign('attrs', D('Lifeclassattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('photos', D('Lifesphoto')->getPics($life_id));
                
                $this->assign('tags', D('Lifeclasstag')->order(array('orderby' => 'asc'))->where(array('cate_id' =>$detail['cate_id']))->select());
                $tags = explode(',', $detail['tag']);
                $this->assign('tag', $tag);
                
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的生活信息');
        }
    }
    private function selleditCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->selledit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuError('标题不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->tuError('城市不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        $data['user_id'] = htmlspecialchars($data['user_id']);
        if (empty($data['user_id'])) {
            $this->tuError('用户不能为空');
        }
        $data['is_shop'] = (int) $data['is_shop'];
        $data['text1'] = htmlspecialchars($data['text1']);
        $data['text2'] = htmlspecialchars($data['text2']);
        $data['text3'] = htmlspecialchars($data['text3']);
        $data['text4'] = htmlspecialchars($data['text4']);
        $data['text5'] = htmlspecialchars($data['text5']);
        $data['num1'] = (int) $data['num1'];
        $data['num2'] = (int) $data['num2'];
        $data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];
        $data['urgent_date'] = htmlspecialchars($data['urgent_date']);
        $data['urgent_date'] = $data['urgent_date'] ? $data['urgent_date'] : TODAY;
        if (!empty($data['urgent_date']) && !isDate($data['urgent_date'])) {
            $this->tuError('火急日期格式不正确');
        }
        $data['top_date'] = htmlspecialchars($data['top_date']);
        $data['lng'] = htmlspecialchars(trim($data['lng']));
        $data['lat'] = htmlspecialchars(trim($data['lat']));
        $data['top_date'] = $data['top_date'] ? $data['top_date'] : TODAY;
        if (!empty($data['top_date']) && !isDate($data['top_date']) && $data['top_date'] != '0000-00-00') {
            $this->tuError('置顶日期格式不正确');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (!empty($data['photo']) && !isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        }
        $data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->tuError('联系人不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuError('电话不能为空');
        }
        if (!isMobile($data['mobile']) && !isPhone($data['mobile'])) {
            $this->tuError('电话格式不正确');
        }
        $data['qq'] = htmlspecialchars($data['qq']);
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['views'] = (int) $data['views'];
        $data['money'] = (float) ($data['money']);
        return $data;
    }

}