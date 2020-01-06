<?php
class LifeAction extends CommonAction{
	
    private $create_fields = array('title','city_id','cate_id','area_id','business_id','user_id','is_shop','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','urgent_date', 'top_date','photo','contact','mobile','qq','addr','views','money','lng','lat');
    private $edit_fields = array('title','city_id','cate_id','area_id','business_id','user_id','is_shop','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','urgent_date', 'top_date','photo','contact','mobile','qq','addr','views','money','lng','lat');
	private  $dakuan=array('time','create_time');
    public function index(){
        $Life = D('Life');
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
        $this->assign('cates', D('Lifecate')->fetchAll());
        $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->display();
    }
	
    public function create(){
        if ($this->isPost()){
			$obj = D('Life');
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
                    D('Lifedetails')->updateDetails($life_id, $details);
                }
                $photos = $this->_post('photos', false);
                if (!empty($photos)) {
                    D('Lifephoto')->upload($life_id, $photos);
                }
                $this->tuSuccess('添加成功', U('life/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('cates', D('Lifecate')->fetchAll());
            $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
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
    public function edit($life_id = 0){
        if ($life_id = (int) $life_id) {
            $obj = D('Life');
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
                        D('Lifedetails')->updateDetails($life_id, $details);
                    }
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Lifephoto')->upload($life_id, $photos);
                    }
                    $this->tuSuccess('操作成功', U('life/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('cates', D('Lifecate')->fetchAll());
                $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
                $this->assign('cate', D('Lifecate')->find($detail['cate_id']));
                $this->assign('ex', D('Lifedetails')->find($life_id));
                $this->assign('attrs', D('Lifecateattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('photos', D('Lifephoto')->getPics($life_id));
				
				$this->assign('tags', D('LifeCateTag')->order(array('orderby' => 'asc'))->where(array('cate_id' =>$detail['cate_id']))->select());
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
    public function delete($life_id = 0){
        if (is_numeric($life_id) && ($life_id = (int) $life_id)) {
            $obj = D('Life');
            $obj->save(array('life_id' => $life_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('life/index'));
        } else {
            $life_id = $this->_post('life_id', false);
            if (is_array($life_id)) {
                $obj = D('Life');
                foreach ($life_id as $id) {
                    $obj->save(array('life_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('life/index'));
            }
            $this->tuError('请选择要删除的生活信息');
        }
    }
    public function audit($life_id = 0){
        if(is_numeric($life_id) && ($life_id = (int) $life_id)){
            $obj = D('Life');
			D('LifeSubscribe')->pushSubscribe($life_id);//推送分类信息返回真
            $obj->save(array('life_id' => $life_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('life/index'));
        }else{
            $life_id = $this->_post('life_id', false);
            if(is_array($life_id)){
                $obj = D('Life');
                foreach($life_id as $id){
					D('LifeSubscribe')->pushSubscribe($id);//推送分类信息返回真
                    $obj->save(array('life_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('批量审核成功', U('life/index'));
            }
            $this->tuError('请选择要审核的生活信息');
        }
    }

    //招聘信息审核列表    ---- 新增
    public function life_audit()
    {
        $Life = M('LifeAudit');
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
        $list = $Life->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('cates', D('Lifecate')->fetchAll());
        $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->display();
    }
    //招聘信息审核列表    ---- 新增
    public function life_audit_type($user_id)
    {
        if(is_numeric($user_id) && ($user_id = (int) $user_id)){
            $obj = M('LifeAudit');
            $obj->where(array('user_id' => $user_id))->save(array('audit' => 1));
            $this->tuSuccess('审核成功', U('life/life_audit'));
        }else{
            $user_id = $this->_post('user_id', false);
            if(is_array($user_id)){
                $obj = M('LifeAudit');
                foreach($user_id as $id){
                    $obj->where(array('user_id' => $user_id))->save(array('audit' => 1));
                }
                $this->tuSuccess('批量审核成功', U('life/life_audit'));
            }
            $this->tuError('请选择要审核的信息');
        }
    }
    //招聘信息删除     ----新增
    public function life_delete($user_id = 0){
        if (is_numeric($user_id) && ($user_id = (int) $user_id)) {
            $obj = M('LifeAudit');
            $obj->save(array('user_id' => $user_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('life/life_audit'));
        } else {
            $user_id = $this->_post('user_id', false);
            if (is_array($user_id)) {
                $obj = M('LifeAudit');
                foreach ($user_id as $id) {
                    $obj->save(array('user_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('life/life_audit'));
            }
            $this->tuError('请选择要删除的审核信息');
        }
    }
    //押金退款管理   ----新增
    public function life_refund()
    {
        $Life = M('LifeHandle');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if($userid = (int) $this->_param('userid')){
            $map['user_id']=$userid;
            $this->assign('usreid',$userid);
        }
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Life->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val){
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //押金审核   ---- 新增
    public function lifeRefundAudit($handel_id)
    {   
        if(is_numeric($handel_id) && ($handel_id = (int) $handel_id)){
            $obj = M('LifeHandle');
            $obj->where(array('handel_id' => $handel_id))->save(array('audit' => 1));
            $this->tuSuccess('审核成功', U('life/life_refund'));
        }else{
            $this->tuError('请选择要审核的信息');
        }
    }
    //押金信息删除      ----新增
    public function lifeRefundDel($handel_id)
    {
        if (is_numeric($handel_id) && ($handel_id = (int) $handel_id)) {
            $obj = M('LifeHandle');
            $obj->where(array('handel_id' => $handel_id))->save(array('handel_id' => $handel_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('life/life_refund'));
        } else {
            $handel_id = $this->_post('handel_id', false);
            if (is_array($handel_id)) {
                $obj = M('LifeHandle');
                foreach ($handel_id as $id) {
                    $obj->where(array('handle_id' => $handel_id))->save(array('handel_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('life/life_refund'));
            }
            $this->tuError('请选择要删除的审核信息');
        }
    }

    //打款时间
    public function edits($handel_id){
        if (is_numeric($handel_id) && ($handel_id = (int) $handel_id)) {
            $obj=M('LifeHandle');
            if($this->ispost()){
                $data = $this->checkFields($this->_post('data', false), $this->dakuan);
                $data['create_time'] = strtotime(htmlspecialchars($data['create_time']));

                $data['time'] = strtotime(htmlspecialchars($data['time']));

                if (empty($data['time'])) {
                    $this->tuError('打款日期格式不正确');
                }

                $row=$obj->where(array('handel_id'=>$handel_id))->save($data);
                $this->automaticMoney($handel_id);//自动打款
                if($row>0){
                    $this->tuSuccess('编辑成功');
                }else{
                    $this->tuError('编辑失败');
                }

            }else{

                $show=$obj->where(array('handel_id'=>$handel_id))->find();
                $this->assign('list',$show);
            }
        }
        $this->display();
    }

    //自动到账
    public function automaticMoney($handel_id=0){
        $LifeInfo=M('LifeHandle')->where(array('handel_id'=>$handel_id))->find();
        $time = time();  //当前时间
        $shopout_time = $LifeInfo['time'];  //自动确认打款
        $shoptime = $time - $shopout_time;  //自动确认打款时间戳
        $map1['time'] = array(array('ELT', $shoptime));
        $map1['audit'] = 1;
        $shopOrderList = M('LifeHandle')->where($map1)->select();
        //申请记录
        if (is_array($shopOrderList)) {
            switch ($LifeInfo['type']) {
                case  '0':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的商户入驻押金已退款至您的账号中,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '1':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的发布信息押金已退款至您的账户,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '2':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的配送押金已退款至您的账户,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '3':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的配送管理员押金已退款至您的账户,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '4':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的代理押金已退款至您的账户,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '5':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                        D('Users')->addMoney($LifeInfo['user_id'],$LifeInfo['money'],'您的司机押金已退款至您的账户,金额为'.$LifeInfo['money'].',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                default:
                    if($LifeInfo['paycode'] =='alipay'){
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        $logs = D('Paymentlogs')->where(['user_id'=>$LifeInfo['user_id'],'type'=>'shop'])->find();
                        if(false !== D('Refund')->alipay_refund($payinfo,$logs['need_pay'],$logs['return_trade_no'],$logs['return_msg'].$logs['log_id'])){
                            if(false !== D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"预约订单取消，退款至支付宝，退款金额为".$info['need_pay'])){
                                $this->tuSuccess('支付宝成功打款');
                            }else{
                                $this->tuError('支付宝打款失败');
                            }
                        }else{
                            $this->tuError('支付宝打款失败');
                        }

                    }
                    break;
            }

        }
        else {
            return '-1';
        }
    }

    //押金信息打款     ----新增
    public function lifeRefundGetMoney($handel_id)
    {
        if (is_numeric($handel_id) && ($handel_id = (int) $handel_id)) {
            $obj = M('LifeHandle');

            $LifeInfo=$obj->where(array('handel_id'=>$handel_id))->find();

            if(empty($LifeInfo['handel_id'])){
                $this->tuError('请选择要确认打款的押金退款信息');
            }

            if($LifeInfo['is_getpay'] ==1){
                $this->tuError('该信息已处理，无需再次处理');
            }

		           $end=(int) $LifeInfo['time'];
		            $now=time();
		        if($end>$now){
		            $this->tuError('还未到打款时间');
		        }
		        if($end==$now||$now>$end){
            switch ($LifeInfo['type']) {
                case  '0':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的商户入驻押金已退款至您的账号中,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '1':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的发布信息押金已退款至您的账户,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '2':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的配送押金已退款至您的账户,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '3':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的配送管理员押金已退款至您的账户,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '4':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的代理押金已退款至您的账户,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                case '5':
                    if(false !== $obj->where(['handel_id'=>$handel_id])->save(['is_getpay'=>1,'make_time'=>NOW_TIME])){
                            D('Users')->addMoney($LifeInfo['user_id'], $LifeInfo['money'], '您的司机押金已退款至您的账户,金额为' . $LifeInfo['money'] . ',请注意查收');
                        D('Depositmanagement')->where(array('user_id'=>$LifeInfo['user_id'],'type'=>$LifeInfo['type']))->save(array('closed'=>1));
                        $this->tuSuccess('确认打款成功');
                    }else{
                        $this->tuError('确认打款失败');
                    }
                    break;

                default:
                    if($LifeInfo['paycode'] =='alipay'){
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        $logs = D('Paymentlogs')->where(['user_id'=>$LifeInfo['user_id'],'type'=>'shop'])->find();
                        if(false !== D('Refund')->alipay_refund($payinfo,$logs['need_pay'],$logs['return_trade_no'],$logs['return_msg'].$logs['log_id'])){
                                if (false !== D('Users')->addMoneyLogs($info['user_id'], $info['need_pay'], "预约订单取消，退款至支付宝，退款金额为" . $info['need_pay'])) {
                                $this->tuSuccess('支付宝成功打款');
                            }else{
                                $this->tuError('支付宝打款失败');
                            }
                        }else{
                            $this->tuError('支付宝打款失败');
                        }
                        
                    }
                    break;
            }
        //    }
        }else{
            $this->tuError('请选择要确认打款的押金退款信息');
        }
    }
    }

	public function buy(){
        $obj = D('LifeBuy');
        import('ORG.Util.Page');
        $map = array();
        if ($user_id = (int) $this->_param('user_id')) {
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
		if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('buy_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = array();
        foreach ($list as $k => $val) {
			$user_ids[$val['user_id']] = $val['user_id'];
			$life_ids[$val['life_id']] = $val['life_id'];
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
        }
		$this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('lifes', D('Life')->itemsByIds($life_ids));
		$this->assign('sum', $sum = $obj->where($map)->sum('money'));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
	public function subscribe(){
        $obj = D('LifeSubscribe');
        import('ORG.Util.Page');
        $map = array();
        if ($user_id = (int) $this->_param('user_id')) {
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
		if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('subscribe_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = array();
        foreach ($list as $k => $val) {
			$user_ids[$val['user_id']] = $val['user_id'];
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
            $list[$k] = $val;
        }
		$this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('cates', D('Lifecate')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	 public function subscribe_delete($subscribe_id = 0){
        if ($subscribe_id = (int) $subscribe_id) {
            $obj = D('LifeSubscribe');
			if($detail = $obj->find($subscribe_id)){
				if($obj->delete($subscribe_id)){
					$this->tuSuccess('删除成功', U('life/subscribe'));
				}
			}else{
				$this->tuError('没找到');
			}
            
        } else {
            $this->tuError('ID不正确');
        }
    }
	
	
	
	public function share(){
        $obj = D('LifeShareLogs');
        import('ORG.Util.Page');
        $map = array();
		if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['log_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))){
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        }else{
            if($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
		if($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $life_ids = $user_ids = array();
        foreach ($list as $val) {
            $life_ids[$val['life_id']] = $val['life_id'];
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        $this->assign('lifes', D('Life')->itemsByIds($life_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
}