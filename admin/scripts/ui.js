$(function() {

    $('#header-gnb li').mouseenter(function() {
        var idx = $(this).index();

        $('#header-gnb-sub').addClass('active');
        $('.header-gnb-sub-inner').eq(idx).addClass('active').siblings().removeClass('active');
    });
    $('#header-gnb-sub').mouseenter(function() {
        $('#header-gnb-sub').addClass('active');
    });
    $('#header-gnb li, #header-gnb-sub').mouseleave(function() {
        $('#header-gnb-sub').removeClass('active');
    });
});