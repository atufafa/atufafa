<?php
class ShoppingcartAction extends CommonAction
{
    public function index(){
        $goods = cookie('goods_spec');
        $this->assign('cartnum', (int) array_sum($goods));
        $goodss = cookie('goods_specs');
        $this->assign('cartnums', (int) array_sum($goodss));
        $jifen = cookie('goodsjifen');
        $this->assign('jifennum', (int) array_sum($jifen));

        $cart_store = cookie('goods_store');
        $this->assign('cart_store_num', (int) array_sum($cart_store));

        $cart_market = cookie('goods_market');
        $this->assign('cart_market_num', (int) array_sum($cart_market));

        $cart_ele = cookie('goods_ele');
        $this->assign('cart_ele_num', (int) array_sum($cart_ele));

        $this->display();
    }

}