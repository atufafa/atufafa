

$('.num').bind('input propertychange',function(event){

    var data = {};
    var pei=document.getElementById('peis').value;
    var product_id = $(this).attr('did');
    var price = $(this).attr('price');
    var nums = $(this).val();
    var totalprice=product_totalPrice();
    var sum=eval(totalprice+"+"+pei);
    $('#prices').val(totalprice);
    $('#total_price').val(sum);
    data['product_id'] = product_id;
    data['price'] =price;
});

//获取所有商品价格
function  product_totalPrice(){
    var total_price=0;
    $.each($('.num'),function(index,obj){
        var price=$(this).attr('price');
        var nums=$(this).val();
        $(this).parent().next().find('.sum').val(price*nums);
        total_price+=price*nums;
    });
    return total_price;
}
