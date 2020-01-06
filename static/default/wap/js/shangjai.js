
$(".num").bind("input propertychange",function(event){
    var data = {};
    var product_id = $(this).attr('did');
    var price = $(this).attr('price');
    var nums = $(this).val();
    var totalprice=product_totalPrice();

    $(".sum").html(totalprice);
    data['product_id'] = product_id;
    data['price'] =price;

    console.log(product_id);
    console.log(totalprice);
});

//获取所有商品价格
function  product_totalPrice(){
    var total_price=0;
    $.each($('.num'),function(index,obj){
        var price=$(this).attr('price');
        var nums=$(this).val();
        total_price=price*nums;
    });
    return total_price;
}
