<?php
class ComplaintAction extends CommonAction {
    //外卖
    public function ele(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>1);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //菜市场
    public function market(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>3);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //便利店
    public function store(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>2);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //家政
    public function appoint(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>6);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //教育
    public function edu(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>7);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //农家乐
    public function farm(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>8);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //商城
    public function goods(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>9);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //酒店
    public function hotels(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>4);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //ktv
    public function ktv(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>5);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //订座
    public function booking(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>10);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //装修
    public function decorate(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0,'type'=>11);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id|shop_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }






    //九大板块审核通用
    public function shenghe($id,$type){
        $obj = D('Shopcomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('status' =>1, 'id' => $id);
        $obj->save($data);
        if($type==1){
            $this->tuSuccess('操作成功', U('complaint/ele'));
        }elseif($type==2){
            $this->tuSuccess('操作成功', U('complaint/store'));
        }elseif($type==3){
            $this->tuSuccess('操作成功', U('complaint/market'));
        }elseif($type==4){
            $this->tuSuccess('操作成功', U('complaint/hotels'));
        }elseif($type==5){
            $this->tuSuccess('操作成功', U('complaint/ktv'));
        }elseif($type==6){
            $this->tuSuccess('操作成功', U('complaint/appoint'));
        }elseif($type==7){
            $this->tuSuccess('操作成功', U('complaint/edu'));
        }elseif($type==8){
            $this->tuSuccess('操作成功', U('complaint/farm'));
        }elseif($type==9){
            $this->tuSuccess('操作成功', U('complaint/goods'));
        }elseif($type==10){
            $this->tuSuccess('操作成功', U('complaint/booking'));
        }elseif($type==11){
            $this->tuSuccess('操作成功', U('complaint/decorate'));
        }
    }

    //删除
    public function del($id,$type){
        $obj = D('Shopcomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('colse' =>1, 'id' => $id);
        $obj->save($data);
        if($type==1){
            $this->tuSuccess('操作成功', U('complaint/ele'));
        }elseif($type==2){
            $this->tuSuccess('操作成功', U('complaint/store'));
        }elseif($type==3){
            $this->tuSuccess('操作成功', U('complaint/market'));
        }elseif($type==4){
            $this->tuSuccess('操作成功', U('complaint/hotels'));
        }elseif($type==5){
            $this->tuSuccess('操作成功', U('complaint/ktv'));
        }elseif($type==6){
            $this->tuSuccess('操作成功', U('complaint/appoint'));
        }elseif($type==7){
            $this->tuSuccess('操作成功', U('complaint/edu'));
        }elseif($type==8){
            $this->tuSuccess('操作成功', U('complaint/farm'));
        }elseif($type==9){
            $this->tuSuccess('操作成功', U('complaint/goods'));
        }elseif($type==10){
            $this->tuSuccess('操作成功', U('complaint/booking'));
        }elseif($type==11){
            $this->tuSuccess('操作成功', U('complaint/decorate'));
        }
    }

    //九大板块通用惩罚
    public function fakuan($id,$type){
        $obj=D('Shopcomplaint');
        $list = $obj->where(array('id' => $id,'type'=>$type))->find();
        if($this->ispost()){
            $money=I('post.money');
            if($money==0){
                $this->tuError('请选择');
            }
            $shop=D('Shop')->where(array('shop_id'=>$list['shop_id']))->find();
            $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
            if($user['gold']<$money){
                $this->tuError('商家商户资金不足，请通知商家进行充值罚款');
            }

            if(false != D('Users')->addGold($user['user_id'],$money,'您于'.$list['time'].',被投诉，平台对您惩罚'.$money.'元作为提醒。')){
                $obj->save(array('id'=>$id,'stu'=>1,'money'=>$money));
                if($type==1){
                    $this->tuSuccess('操作成功', U('complaint/ele'));
                }elseif($type==2){
                    $this->tuSuccess('操作成功', U('complaint/store'));
                }elseif($type==3){
                    $this->tuSuccess('操作成功', U('complaint/market'));
                }elseif($type==4){
                    $this->tuSuccess('操作成功', U('complaint/hotels'));
                }elseif($type==5){
                    $this->tuSuccess('操作成功', U('complaint/ktv'));
                }elseif($type==6){
                    $this->tuSuccess('操作成功', U('complaint/appoint'));
                }elseif($type==7){
                    $this->tuSuccess('操作成功', U('complaint/edu'));
                }elseif($type==8){
                    $this->tuSuccess('操作成功', U('complaint/farm'));
                }elseif($type==9){
                    $this->tuSuccess('操作成功', U('complaint/goods'));
                }elseif($type==10){
                    $this->tuSuccess('操作成功', U('complaint/booking'));
                }elseif($type==11){
                    $this->tuSuccess('操作成功', U('complaint/decorate'));
                }

            }else {
                $this->tuError('操作失败');
            }
        }else {
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 积分商城
     */
    public function jifen(){
        $obj = D('Integralcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function deljifen($id){
        $obj = D('Integralcomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('colse' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/jifen'));

    }

    //审核
    public function shenghes($id){
        $obj = D('Integralcomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('status' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/jifen'));

    }

    //罚款
    public function fakuans($id){
        $obj=D('Integralcomplaint');
        $list = $obj->where(array('id' => $id))->find();
        if($this->ispost()){
            $money=I('post.money');
            if($money==0){
                $this->tuError('请选择');
            }
            $shop=D('Shop')->where(array('shop_id'=>$list['shop_id']))->find();
            $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
            if($user['gold']<$money){
                $this->tuError('商家商户资金不足，请通知商家进行充值罚款');
            }
            if(false != D('Users')->addGold($user['user_id'],$money,'您于'.$list['time'].',被投诉，平台对您惩罚'.$money.'元作为提醒。')){
                $obj->save(array('id'=>$id,'stu'=>1,'money'=>$money));
                $this->tuSuccess('惩罚完成',U('complaint/jifen'));
            }else {
                $this->tuError('操作失败');
            }

        }else {
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 0元购
     */
    public function zero(){
        $obj = D('Pindancomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function delzero($id){
        $obj = D('Pindancomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('colse' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/zero'));

    }

    //审核
    public function shenzero($id){
        $obj = D('Pindancomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('status' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/zero'));

    }

    //罚款
    public function fakuanzero($id){
        $obj=D('Pindancomplaint');
        $list = $obj->where(array('id' => $id))->find();
        if($this->ispost()){
            $money=I('post.money');
            if($money==0){
                $this->tuError('请选择');
            }
            $shop=D('Shop')->where(array('shop_id'=>$list['shop_id']))->find();
            $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
            if($user['gold']<$money){
                $this->tuError('商家商户资金不足，请通知商家进行充值罚款');
            }
            if(false != D('Users')->addGold($user['user_id'],$money,'您于'.$list['time'].',被投诉，平台对您惩罚'.$money.'元作为提醒。')){
                $obj->save(array('id'=>$id,'stu'=>1,'money'=>$money));
                $this->tuSuccess('惩罚完成',U('complaint/zero'));
            }else {
                $this->tuError('操作失败');
            }

        }else {
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 在线抢购
     */
    public function rush(){
        $obj = D('Tuancomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function delrush($id){
        $obj = D('Tuancomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('colse' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/rush'));

    }

    //审核
    public function shenrush($id){
        $obj = D('Tuancomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('status' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('complaint/rush'));

    }

    //罚款
    public function fakuanrush($id){
        $obj=D('Tuancomplaint');
        $list = $obj->where(array('id' => $id))->find();
        if($this->ispost()){
            $money=I('post.money');
            if($money==0){
                $this->tuError('请选择');
            }
            $shop=D('Shop')->where(array('shop_id'=>$list['shop_id']))->find();
            $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
            if($user['gold']<$money){
                $this->tuError('商家商户资金不足，请通知商家进行充值罚款');
            }
            if(false != D('Users')->addGold($user['user_id'],$money,'您于'.$list['time'].',被投诉，平台对您惩罚'.$money.'元作为提醒。')){
                $obj->save(array('id'=>$id,'stu'=>1,'money'=>$money));
                $this->tuSuccess('惩罚完成',U('complaint/rush'));
            }else {
                $this->tuError('操作失败');
            }

        }else {
            $this->assign('list', $list);
            $this->display();
        }
    }


}