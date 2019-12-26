$(function() {

    gnbControl();

});



var gnbControl = function () {

    $('.header_gnb_1depth, .header_gnb_2depth').on({

        'mouseenter': function () {

            $('.header_gnb_2depth').fadeIn(100);

        }

    });



    $('#container, .header_top').on({

        'mouseenter': function () {

            $('.header_gnb_2depth').fadeOut(100);

        }

    });



    $('.header_gnb_1depth li').on({

        'mouseenter': function () {

            $('.header_gnb_2depth_inner').hide();



            if($(this).hasClass('gnb-manage')) {

                $('.header_gnb_2depth-manage').show();

            } else if($(this).hasClass('gnb-form')) {

                $('.header_gnb_2depth-form').show();

            } else if($(this).hasClass('gnb-resources')) {

                $('.header_gnb_2depth-resources').show();

			}else if($(this).hasClass('gnb-review')) {
                $('.header_gnb_2depth-review').show();
            } else if($(this).hasClass('gnb-news')) {

                $('.header_gnb_2depth-news').show();

            } else if($(this).hasClass('gnb-support')) {

                $('.header_gnb_2depth-support').show();

            } else if($(this).hasClass('gnb-company')) {

                $('.header_gnb_2depth-company').show();

            }



        }

    });

};

function initLayer(target)
{
    jQuery.ajax({
        url: target,
        type: "post",
        success: function (result) {
            $("#layer_comment").html(result);
			$("#layer_comment").find("script").each(function (i) {
                eval($(this).text());
            });
            $("#layer_comment").show();
        },
        error: function (result) {
            //alert("Error");
        }
    });
}

function changeLayer(target)
{
    $("#layer_comment").html('');
    jQuery.ajax({
        url: target,
        type: "post",
        success: function (result) {
            $("#layer_comment").html(result);
            $("#layer_comment").find("script").each(function (i) {
                eval($(this).text());
            });
        },
        error: function (result) {
            //alert("Error");
        }
    });
}

function initLayerPost(target, frmdata)
{
    jQuery.ajax({
        url: target,
        type: "post",
        data: frmdata,
        success: function (result) {
            $("#layer_comment").html(result);
			$("#layer_comment").find("script").each(function (i) {
                eval($(this).text());
            });
            $("#layer_comment").show();
        },
        error: function (result) {
            //alert("Error");
        }
    });
}

function changeLayerPost(target, frmdata)
{
    $("#layer_comment").html('');
    jQuery.ajax({
        url: target,
        type: "post",
        data: frmdata,
        success: function (result) {
            $("#layer_comment").html(result);
            $("#layer_comment").find("script").each(function (i) {
                eval($(this).text());
            });
        },
        error: function (result) {
            //alert("Error");
        }
    });
}

function LayerClose()
{
	$('#layer_comment').hide();
	$("#layer_comment").html('');
}