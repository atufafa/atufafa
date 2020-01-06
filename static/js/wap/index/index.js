// TouchSlide({
//     slideCell: "#tudou-slide-lif",
//     titCell: ".hds ul",
//     mainCell: ".bd ul",
//     effect: "left",
//     autoPlay: true,
//     autoPage: true,
//     switchLoad: "_src",
// });
// TouchSlide({
//     slideCell: "#tudou-slide-zhong",
//     titCell: ".hds ul",
//     mainCell: ".bd ul",
//     effect: "left",
//     autoPlay: true,
//     autoPage: true,
//     switchLoad: "_src",
// });
// TouchSlide({
//     slideCell: "#tudou-slide-rifg",
//     titCell: ".hds ul",
//     mainCell: ".bd ul",
//     effect: "left",
//     autoPlay: true,
//     autoPage: true,
//     switchLoad: "_src",
// });
//回到顶部
$(window).on('scroll',function(){
    var st = $(document).scrollTop();
    if( st>800 ){
        $('.dingbu2').fadeIn(600);
    }else{
        $('.dingbu2').fadeOut(600);
    }
});
$(".dingbu2").click(function(){
    if(scroll=="off"){
        return;
    }
    $("html,body").animate({scrollTop: 0}, 600);
});

$(".iopen").click(function(){
    $(".ljgz_img").show();
    $(".mask").show();
});

$(".ljgz_img").click(function(){
    $(this).hide();
    $(".mask").hide();
});
$(function(){
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
});

$(window).scroll(function(){
    if(($(".top-fixed").length > 0)) {
        if(($(this).scrollTop() > 0) && ($(window).width() > 100)){
            $("header").removeClass("header");
            $("#search-btn").addClass("search-btn");
            $("#home-follow-public").addClass("home-follow-public-none");
            $("#home-follow-public").show(200);
        } else {
            $("#home-follow-public").hide(200);
            $("header").addClass("header");
            $("#search-btn").removeClass("search-btn");
        }
    };
});

function closeDiv(){
    var p = $("#home-follow-public").css("display");
    if(typeof(p)=="undefined"||p==""||p=="block"){
        $("#home-follow-public").css("display","none");
    }else{
        $("#home-follow-public").css("display","block");
    }
}

$(document).ready(function (){
    $('.navigation_index_cate').flexslider({
        directionNav: true,
        pauseOnAction: false,
    });
    $('.flexslider_cate').flexslider({
        directionNav: true,
        pauseOnAction: false,
    });
});
TouchSlide({ slideCell:"#index-notice",autoPlay:true,effect:"leftLoop",interTime:3000});
TouchSlide({slideCell: "#focus",titCell: ".hd ul", mainCell: ".bd ul",effect: "left",autoPlay: true,autoPage: true, switchLoad: "_src",});

layui.use('flow', function(){
    var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
    var flow = layui.flow;
    flow.load({
        elem: '#hot_goods', //指定列表容器
        mb:200,
        isAuto:true,
        isLazyimg:true
        ,done: function(page, next){
            //到达临界点（默认滚动触发），触发下一页
            //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
            setTimeout(function () {
                $.get('/api/wap/hotGoods?page='+page, function(res){
                    var html=template('template_hot_goods',res);
                    //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                    //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                    next(html, page < res.pages);
                });
            },1);
        }
    });
});

$('.icons2').click(function(){
    $('.icons2').css({"color":"inherit"});
    $(this).css({"color":"red"});
    $('#hot_goods').html('');
    var parent_id=$(this).attr('parent_id');
    layui.use('flow', function(){
        var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
        var flow = layui.flow;
        flow.load({
            elem: '#hot_goods', //指定列表容器
            mb:200,
            isAuto:true,
            isLazyimg:true
            ,done: function(page, next){
                //到达临界点（默认滚动触发），触发下一页
                //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
                setTimeout(function () {
                    $.get('/api/wap/categoryGoods?page='+page+'&parent_id='+parent_id, function(res){
                        var html=template('template_category_goods',res);
                        //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                        //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                        next(html, page < res.pages);
                    });
                },1);
            }
        });
    });
});

$('#hot_click').click(function(){
    $('.icons2').css({"color":"inherit"});
    $(this).css({"color":"red"});
    $('#hot_goods').html('');
    layui.use('flow', function(){
        var $ = layui.jquery; //不用额外加载jQuery，flow模块本身是有依赖jQuery的，直接用即可。
        var flow = layui.flow;
        flow.load({
            elem: '#hot_goods', //指定列表容器
            mb:200,
            isAuto:true,
            isLazyimg:true
            ,done: function(page, next){
                //到达临界点（默认滚动触发），触发下一页
                //以jQuery的Ajax请求为例，请求下一页数据（注意：page是从2开始返回）
                setTimeout(function () {
                    $.get('/api/wap/hotGoods?page='+page, function(res){
                        var html=template('template_hot_goods',res);
                        //执行下一页渲染，第二参数为：满足“加载更多”的条件，即后面仍有分页
                        //pages为Ajax返回的总页数，只有当前页小于总页数的情况下，才会继续出现加载更多
                        next(html, page < res.pages);
                    });
                },1);
            }
        });
    });
});

    timer=3000;
    index=1;
    //滚动函数
    function scroll() {

        var speed=600;
        var _this=$('#scrollDiv'+index).eq(0).find("ul:first");
        var line=$('#scrollDiv'+index).eq(0).find("ul li").size();
        var lineH=_this.find("li:first").height() + 20, //获取行高
            line=line?parseInt(line,10):parseInt(this.height() / lineH,10), //每次滚动的行数，默认为一屏，即父容器高度
            speed=speed?parseInt(speed,10):500; //卷动速度，数值越大，速度越慢（毫秒）
        if(line==0) line=1;
        var upHeight=0-line*lineH/3;
        console.log(upHeight);
        _this.animate({
            marginTop:-150
        },speed,function(){
            _this.find("li:first").appendTo(_this);
            _this.css({marginTop:0});
        });
        index++;
        if(index>=4){
            index=1;
        }
    }

    //Shawphy:自动播放
    var autoPlay = function(){
        if(timer)timerID = window.setInterval(scroll,timer);
    };

    var autoStop = function(){
        if(timer)window.clearInterval(timerID);
    };

    autoPlay();

