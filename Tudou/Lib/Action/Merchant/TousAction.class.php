<?php
class TousAction extends CommonAction{
    //显示
    public function index(){
        $obj = D('Shopcomplaint');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'colse'=>0,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //回复
    public function tousureply($id){
        $id = (int) $id;
        $where =array('id' => $id);
        $hf = D('Shopcomplaint')->where($where)->find();

        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){
                $data=array('id'=>$id,'sjcontent'=>$sjcontent);
                $con=D('Shopcomplaint')->where($where)->save($data);
                if($con>0){
                    $this->tuSuccess('回复成功', U('tous/index'));
                }else{
                    $this->tuError('回复失败');
                }
            }
            $this->tuError('请填写回复信息');
        }else{

            $this->assign('list', $hf);
            $this->display();

        }

    }

    //0元购投诉列表
    public function tszero(){
        $obj = D('Pindancomplaint');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'colse'=>0,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }


    //回复
    public function zerotousureply($id){
        $id = (int) $id;
        $where =array('id' => $id);
        $hf = D('Pindancomplaint')->where($where)->find();

        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){
                $data=array('id'=>$id,'sjcontent'=>$sjcontent);
                $con=D('Pindancomplaint')->where($where)->save($data);
                if($con>0){
                    $this->tuSuccess('回复成功', U('tous/tszero'));
                }else{
                    $this->tuError('回复失败');
                }
            }
            $this->tuError('请填写回复信息');
        }else{
            $this->assign('list', $hf);
            $this->display();

        }

    }

    //积分商城投诉列表
    public function integral(){
        $obj = D('Integralcomplaint');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'colse'=>0,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //回复
    public function tousintegral($id){
        $id = (int) $id;
        $where =array('id' => $id);
        $hf = D('Integralcomplaint')->where($where)->find();
        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){
                $data=array('id'=>$id,'sjcontent'=>$sjcontent);
                $con=D('Integralcomplaint')->where($where)->save($data);
                if($con>0){
                    $this->tuSuccess('回复成功', U('tous/integral'));
                }else{
                    $this->tuError('回复失败');
                }
            }
            $this->tuError('请填写回复信息');
        }else{
            $this->assign('list', $hf);
            $this->display();
        }
    }



}