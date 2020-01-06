<?php
class RuralAction extends CommonAction
{
    public function _initialize(){
        parent::_initialize();

    }

    //首页管理
    public function index(){
        $Village=D('Village')->where(['user_id'=>$this->uid])->find();
        if(empty($Village)){
            $this->tuError('您还没有入驻乡村!');
        }
        $obj=M('village_users');
        $map=array('village_id'=>$Village['village_id']);
        import('ORG.Util.Page');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('join_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $val){
            $user_id=$val['user_id'];
        }
        $this->assign('user',D('Users')->itemsByIds($user_id));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //帖子管理
    public function  bbs(){
        $Village=D('Village')->where(['user_id'=>$this->uid])->find();
        if(empty($Village)){
            $this->tuError('您还没有入驻乡村!');
        }

        $obj=M('village_bbs');
        $map=array('village_id'=>$Village['village_id']);
        import('ORG.Util.Page');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //审核
    public function examine($post_id){
        if (is_numeric($post_id) && ($post_id = (int) $post_id)) {
            $obj = M('village_bbs');
            $detail=$obj->where(['post_id'=>$post_id])->find();
            if(false == $detail){
                $this->tuError('请选择审核的帖子');
            }
            $obj->where(['post_id'=>$post_id])->save(['audit'=>1]);
            $this->tuSuccess('审核成功', U('rural/bbs'));
        }

    }

    //意见反馈
    public function feedback(){
        $Village=D('Village')->where(['user_id'=>$this->uid])->find();
        if(empty($Village)){
            $this->tuError('您还没有入驻乡村!');
        }

        $obj=M('village_suggestion');
        $map=array('village_id'=>$Village['village_id']);
        import('ORG.Util.Page');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('addtime' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }

    //乡村通知
    public function notice(){
        $Village=D('Village')->where(['user_id'=>$this->uid])->find();
        if(empty($Village)){
            $this->tuError('您还没有入驻乡村!');
        }

        $obj=M('village_notice');
        $map=array('village_id'=>$Village['village_id'],'type'=>1);
        import('ORG.Util.Page');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('addtime' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();

    }

    //活动展示
    public function activity(){
        $Village=D('Village')->where(['user_id'=>$this->uid])->find();
        if(empty($Village)){
            $this->tuError('您还没有入驻乡村!');
        }

        $obj=M('village_notice');
        $map=array('village_id'=>$Village['village_id'],'type'=>2);
        import('ORG.Util.Page');
        $count = $obj->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('addtime' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);

        $this->display();
    }




}