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

    $('#header-mobile-menu a').click(function(e) {
        e.preventDefault();
        $('#header-mobile-menu-list').toggleClass('active');
    });

    $('#header-mobile-menu-list li > a').click(function(e) {
        e.preventDefault();
        $(this).parent('li').toggleClass('active').siblings().removeClass('active');
    });

    $('#header-search a').click(function(e) {
        e.preventDefault();
        $('#header-search-field').toggleClass('active').find('input').val('');
    });

    $(window).click(function(e) {
        if (!$(e.target).parents().is('#header-search') && !$(e.target).parents().is('#header-search-field')) {
            $('#header-search-field').removeClass('active').find('input').val('');
        }
    })

});