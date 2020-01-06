<?php
class GlassAction extends CommonAction{

    //首页
    public function index(){
        $Ele = M('glass');
        import('ORG.Util.Page');
        $count = $Ele->count();
        $counts = $Ele->sum('glass_num');
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Ele->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as  $val){
            $shop_ids[]=$val['shop_id'];
        }
        $this->assign('shop',D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('count',$counts);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function del($id){
        if(is_numeric($id) && ($id = (int) $id)){
            $obj = M('glass');
            $obj->delete($id);
            $this->tuSuccess('删除成功', U('glass/index'));
        }else{
            $shop_id = $this->_post('id', false);
            if(is_array($shop_id)){
                $obj = M('glass');
                foreach($shop_id as $id){
                    $obj->delete($id);
                }
                $this->tuSuccess('批量删除成功', U('glass/index'));
            }
            $this->tuError('请选择要删除的餐饮商家');
        }
    }




}