$("#search-btn").click(function(){
    if($(".top-search").css("display")=='block'){
        $(".top-search").hide();
        $(".top-title").show(200);
    }
    else{
        $(".top-search").show();
        $(".top-title").hide(200);
    }
});
$("#search-bar li").each(function(e){
    $(this).click(function(){
        if($(this).hasClass("on")){
            $(this).parent().find("li").removeClass("on");
            $(this).removeClass("on");
            $(".serch-bar-mask").hide();
        }
        else{
            $(this).parent().find("li").removeClass("on");
            $(this).addClass("on");
            $(".serch-bar-mask").show();
        }
        $(".serch-bar-mask .serch-bar-mask-list").each(function(i){

            if(e==i){
                $(this).parent().find(".serch-bar-mask-list").hide();
                $(this).show();
            }
            else{
                $(this).hide();
            }
            $(this).find("li").click(function(){
                $(this).parent().find("li").removeClass("on");
                $(this).addClass("on");
            });
        });
    });
});


layui.use('flow', function(){
    var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
    var flow = layui.flow;
    flow.load({
        elem: '#tuan_goods_list', //指定列表容器
        mb:200,
        isAuto:true,
        isLazyimg:true
        ,done: function(page, next){
            //到达临界点（默认滚动触发），触发下一页
            //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
            setTimeout(function () {
                $.get('/api/OnlineBuy/hotGoods?page='+page, function(res){
                    var html=template('template_integral_goods',res);
                    //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                    //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                    next(html, page < res.pages);
                });
            },1);
        }
    });
});
$('#hot_click').click(function () {
    $('.icon2').css({"color":"inherit"});
    $('.click_parent_id').css({"color":"inherit"});
    $(this).css({"color":"red"});
    $('#tuan_goods_list').html('');
    layui.use('flow', function(){
        var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
        var flow = layui.flow;
        flow.load({
            elem: '#tuan_goods_list', //指定列表容器
            mb:200,
            isAuto:true,
            isLazyimg:true
            ,done: function(page, next){
                //到达临界点（默认滚动触发），触发下一页
                //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
                setTimeout(function () {
                    $.get('/api/OnlineBuy/hotGoods?page='+page, function(res){
                        var html=template('template_integral_goods',res);
                        //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                        //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                        next(html, page < res.pages);
                    });
                },1);
            }
        });
    });
});

$('.icon2').click(function(){
    $('.icon2').css({"color":"inherit"});
    $('#hot_click').css({"color":"inherit"});
    $(this).css({"color":"red"});
    $('#tuan_goods_list').html('');
    var parent_id=$(this).attr('parent_id');
    layui.use('flow', function(){
        var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
        var flow = layui.flow;
        flow.load({
            elem: '#tuan_goods_list', //指定列表容器
            mb:200,
            isAuto:true,
            isLazyimg:true
            ,done: function(page, next){
                //到达临界点（默认滚动触发），触发下一页
                //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
                setTimeout(function () {
                    $.get('/api/OnlineBuy/categoryGoods?page='+page+'&parent_id='+parent_id, function(res){
                        var html=template('template_category_integral_goods',res);
                        //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                        //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                        next(html, page < res.pages);
                    });
                },1);
            }
        });
    });
});



//根据条件查询
$('#sele').click(function(){
    var keyword=$(this).prev().val();
    $('#tuan_goods_list').html('');
    layui.use('flow', function(){
        var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
        var flow = layui.flow;
        flow.load({
            elem: '#tuan_goods_list', //指定列表容器
            mb:200,
            isAuto:true,
            isLazyimg:true
            ,done: function(page, next){
                //到达临界点（默认滚动触发），触发下一页
                //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
                setTimeout(function () {
                    $.get('/api/OnlineBuy/selectGoods?page='+page+'&keyword='+keyword, function(res){
                        var html=template('template_category_integral_goods',res);
                        //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                        //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                        next(html, page < res.pages);
                    });
                },1);
            }
        });
    });
});