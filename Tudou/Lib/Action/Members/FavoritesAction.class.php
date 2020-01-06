<?php
class FavoritesAction extends CommonAction{
    public function index(){
        $Shopfavorites = D('Shopfavorites');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid);
        $count = $Shopfavorites->where($map)->count();
        $Page = new Page($count, 16);
        $show = $Page->show();
        $list = $Shopfavorites->where($map)->order('favorites_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('prices', D('Shopdetails')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function deletefavo($favorites_id) {
        $favorites_id = (int) $favorites_id;
        if ($detial = D('Shopfavorites')->find($favorites_id)) {
            if ($detial['user_id'] == $this->uid) {
                D('Shopfavorites')->delete($favorites_id);
                //$this->tuSuccess('取消关注成功!', U('favorites/index'));
                //文字乱码 取消关注成功 改成 关注取消成功
                $this->tuSuccess('关注店铺取消成功!', U('favorites/index'));
            }
        }
        $this->tuError('参数错误');
    }
	
	public function sms($favorites_id) {
        $favorites_id = (int) $favorites_id;
		$obj = D('Shopfavorites');
        if ($detial = $obj->find($favorites_id)) {
            if ($detial['user_id'] == $this->uid) {
                $data = array('is_sms' => 0, 'favorites_id' => $favorites_id);

				 if ($detial['is_sms'] == 0) {
					 $data['is_sms'] = 1;
				 }
				
				$obj->save($data);
                $this->tuSuccess('设置短信推送成功!', U('favorites/index'));
            }
        }
        $this->tuError('参数错误');
    }
	
	public function weixin($favorites_id) {
        $favorites_id = (int) $favorites_id;
		$obj = D('Shopfavorites');
        if ($detial = $obj->find($favorites_id)) {
            if ($detial['user_id'] == $this->uid) {
                $data = array('is_weixin' => 0, 'favorites_id' => $favorites_id);
				 if ($detial['is_weixin'] == 0) {
					 $data['is_weixin'] = 1;
				 }
				$obj->save($data);
                $this->tuSuccess('设置微信推送成功!', U('favorites/index'));
            }
        }
        $this->tuError('参数错误');
    }
	
	public function msg($favorites_id) {
        $favorites_id = (int) $favorites_id;
		$obj = D('Shopfavorites');
        if ($detial = $obj->find($favorites_id)) {
            if ($detial['user_id'] == $this->uid) {
                $data = array('is_msg' => 0, 'favorites_id' => $favorites_id);
				 if ($detial['is_msg'] == 0) {
					 $data['is_msg'] = 1;
				 }
				$obj->save($data);
                $this->tuSuccess('设置站内信推送成功!', U('favorites/index'));
            }
        }
        $this->tuError('参数错误');
    }
	
	
	
}