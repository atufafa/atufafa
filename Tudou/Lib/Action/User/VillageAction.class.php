<?php
class VillageAction extends CommonAction
{
    private $create_entry = array('user_id', 'province_id','city_id', 'area_id', 'street_id', 'cate', 'name', 'addr', 'tel', 'pic', 'lng', 'lat', 'profiles', 'create_time', 'create_ip', 'info', 'is_bbs');

    protected function _initialize()
    {
        parent::_initialize();
        if(!$this->_CONFIG['operation']['village']) {
            $this->error('此功能已关闭');die;
        }
        $this->assign('province',D('province')->where(['is_open'=>1])->select());
    }
    public function index() {
        $this->display(); 
    }


    public function loaddata() {
        $map = array('user_id' => $this->uid);
		$joined = D('Villagejoin')->where($map)->order(array('join_id' => 'desc'))->limit(0,20)-> select();	
		foreach ($joined as $val) {
			$cmm_ids[$val['village_id']] = $val['village_id'];
		}
		$this->assign('list', D('Village')->itemsByIds($cmm_ids));		
        $this->display();

    }

    //代理或商家，入驻乡村
    public function entry(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        //查询当前用户是不是代理
        $agent = M('user_agent_applys')->where(['user_id' => $this->uid, 'audit' => 1, 'closed' => 0])->find();
        $shop = D('Shop')->where(['user_id' => $this->uid, 'audit' => 1, 'closed' => 0])->find();
        if (empty($agent) && empty($shop)) {
            $this->error('您不是代理或商家身份，不能入驻乡村！');
        }
        $entrs = D('village')->where(['user_id' => $this->uid])->find();
        if ($this->ispost()) {
            $data = $this->createCheck();
            $obj = D('Village');
            $cate = $this->_post('cate', false);
            $cate = implode(',', $cate);
            $data['cate'] = $cate;
            if ($obj->add($data)) {
                $this->tuMsg('申请成功', U('user/member/index'));
            }
            $this->tuMsg('申请失败');

        }else{
            $getVillageCate = D('Village')->getVillageCate();
            $this->assign('getVillageCate', $getVillageCate);
            $this->assign('entrys',$entrs);
        }

	    $this->display();
    }

    public function  createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_entry);
        $data['user_id'] = $this->uid;
        $data['pic'] = htmlspecialchars($data['pic']);
        if(empty($data['pic'])){
            $this->tuMsg('形象图片不能为空');
        }
        if (!isImage($data['pic'])) {
            $this->tuMsg('形象图片证格式不正确');
        }

        $data['name'] = htmlspecialchars($data['name']);
        if(empty($data['name'])){
            $this->tuMsg('乡村名称不能为空');
        }
        $data['tel'] = (int) $data['tel'];
        if(empty($data['tel'])){
            $this->tuMsg('联系电话不能为空');
        }
        $data['province_id'] = (int) $data['province_id'];
        if(empty($data['province_id'])){
            $this->tuMsg('请选择省市..');
        }
        $data['city_id'] = (int) $data['city_id'];
        if($data['city_id']==0){
            $this->tuMsg('所在城市不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if($data['area_id']==0){
            $this->tuMsg('所在区域不能为空');
        }
        $data['street_id'] = (int) $data['street_id'];
        $data['addr'] = htmlspecialchars($data['addr']);
        if(empty($data['addr'])){
            $this->tuMsg('详细地址不能为空');
        }
        $data['lng'] = htmlspecialchars($data['lng']) ;
        $data['lat'] = htmlspecialchars($data['lat']) ;
        $data['profiles'] = htmlspecialchars($data['profiles']);
        if(empty($data['profiles'])){
            $this->tuMsg('简介不能为空');
        }
        $data['info'] = htmlspecialchars($data['info']);
        if(empty($data['info'])){
            $this->tuMsg('详细介绍不能为空');
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['is_bbs']=1;

        return $data;
    }


}