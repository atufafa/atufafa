$(function(){
    setInterval(function(){
        $(".times").each(function(){
            var obj = $(this);
            var shijian=obj.attr('value');
            var end_str = (shijian).replace(/-/g,"/");
            var endTime = new Date(end_str);
            var nowTime = new Date();
            var nMS=endTime.getTime() - nowTime.getTime() ;
            var myD=Math.floor(nMS/(1000 * 60 * 60 * 24)); //天
            var myH=Math.floor(nMS/(1000*60*60)) % 24; //小时
            var myM=Math.floor(nMS/(1000*60)) % 60; //分钟
            var myS=Math.floor(nMS/1000) % 60; //秒
            var myMS=Math.floor(nMS/100) % 10; //拆分秒
            myH = myH < 10 ? '0'+myH : myH;
            myM = myM < 10 ? '0'+myM : myM;
            myS = myS < 10 ? '0'+myS : myS;
            if(myM>= 0){
                //var str = myD+"天"+myH+"小时"+myM+"分"+myS+"."+myMS+"秒";
                var str='距离结束:'+myH+':'+myM+':'+myS;
            }
            obj.next().html(str);
        });
    }, 1000); //每个0.1秒执行一次
});


