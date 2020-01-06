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
                $.get('/api/ZeroBuy/hotGoods?page='+page, function(res){
                    var html=template('template_integral_goods',res);
                    //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                    //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                    next(html, page < res.pages);
                });
            },1);
        }
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
                    $.get('/api/ZeroBuy/categoryGoods?page='+page+'&parent_id='+parent_id, function(res){
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
                    $.get('/api/ZeroBuy/selectGoods?page='+page+'&keyword='+keyword, function(res){
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
