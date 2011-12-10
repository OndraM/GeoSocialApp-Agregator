var detailMap;
var venueMarkers;

function initDetail(markers) {
    venueMarkers = markers;
    initDetailMap();

    $('#venue-checkin a').click(function() {
       $('span', this).toggle();
       $('#checkin-form').slideToggle();
    });

    // Init checkin actions
    $('#checkin-submit').button();
    $('#checkin-form').submit(function() {
        $('#checkin-submit').slideUp(function(){ $('#checkin-submit').remove(); });
        $('#checkin-message-label').slideUp(function(){ $('#checkin-message').remove(); });
        $('#checkin-form input[type=checkbox]').attr('disabled', 'disabled');
        $('#checkin-form input[type=checkbox]:checked').each(function() {
            var element = $(this).parent();
            var type = encodeURIComponent($(this).parent().attr('data-type'));
            var poiId = encodeURIComponent($(this).parent().attr('data-id'));
            var comment = encodeURIComponent($('#checkin-message').val());
            $(this).parent().html('<img src="/images/spinner.gif" />');
            $.getJSON('/user/checkin/type/' + type + '/id/' + poiId + '/comment/' + comment, function(response) {
                if (typeof response.message !== "undefined" && response.message) {
                    $(element).html('<img src="/images/ok.png" class="icon-left result" alt="OK" title="' + response.message + '" />');
                } else {
                    $(element).html('<img src="/images/ko.png" class="icon-left result" alt="Error" title="Error executing checking" />');
                }
            }).error(function() {
                $(element).html('<img src="/images/ko.png" class="icon-left result" alt="Error" title="Check-in not available" />');
            })
        })
        return false;
    });
    doConnectionsCheck('detail');
    $('#checkin-form div span[id^="checkin-select"] a').live('click', function() {
        doConnection(this, 'detail');
        return false;
    });

    // Show only 10 first tips, other on demand
    var allTips = $('#venue-tips').next('ul').children();
    var tipsLimit = 10;
    if (allTips.length > tipsLimit) {
        var hideTips = allTips.slice(tipsLimit);
        hideTips.hide();
        $('<li><a href=\"#\">Only ' + tipsLimit + ' first tips are shown. Click here to show remaining ' + hideTips.length + '.</a></li>')
            .appendTo($('#venue-tips').next('ul'))
            .click(function() {
                hideTips.slideDown();
                $(this).remove();
                return false;
            });
    }

    // init photo carousel
    $('#photos-carousel').bxSlider({
        displaySlideQty: 5,
        moveSlideQty: 5,
        infiniteLoop: false,
        hideControlOnEnd: true,
        startingSlide: 0
    });

    // define photo tooltip options
    $('#photos-carousel li a').tipsy({
        gravity: $.fn.tipsy.autoNS,
        html: true,
        opacity: 1,
        trigger: 'manual'
    });

    // show tooltip photo on click
    $('#photos-carousel li a').click(function() {
        console.log($(this).attr('data-detail'));
        $(this).attr('original-title',
            '<div class=\"photo-tooltip\">'
            + '<div class=\"photo-tooltip-image\"><img src=\"' + $(this).attr('href') + '\" height=\"210\"/></div>'
            + '<div class=\"photo-tooltip-title\">'
            + '<img src="/images/icon-'
            +  $(this).attr('data-type')
            + '.png" alt="'
            +  $(this).attr('data-type')
            + '" class="icon-left" />'
            + '' + $(this).attr('data-date') + ''
            + ($(this).attr('data-title').length > 0 ? ' | ' + $(this).attr('data-title') : '')
            + '</div>'
            + '</div>'
        );
        $(this).tipsy('show');
        return false;
    });
    // hide tooltip photo on mouse leave
    $('#photos-carousel li a').mouseout(function() {
        $(this).tipsy('hide');
    });

    // show Remove icon on mousover
    $('#venue-source').next('ul').children().mouseover(function(){
        $('.removeService', this).show();
    });
    // hide Remove icon on mousout
    $('#venue-source').next('ul').children().mouseout(function(){
        $('.removeService', this).hide();
    });

    // make Remove icon color on mouseenter in
    $('.removeService').mouseenter(function(){
        $('span:eq(1)', this).show();
        $('span:eq(0)', this).hide();
    });
    // make Remove icon again BW on mouseleave
    $('.removeService').mouseleave(function(){
        $('span:eq(1)', this).hide();
        $('span:eq(0)', this).show();
    });
    // We are in standalone window, so don't open fancybox when removing service,
    // rather leave it to redirect
    if ($('div.fancybox-wrap').length == 0 && $('#detail').length > 0) {
        $('.removeService').removeClass('popUp');
    }
}

function initDetailMap() {
    var centerLatLng = new google.maps.LatLng(venueMarkers[0].lat, venueMarkers[0].lng);
    var mapOptions = {
        zoom: 16,
        center: centerLatLng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControlOptions: {
            // disable terrain map:
            mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE]

        }
    };
    var detailMap = new google.maps.Map(
        document.getElementById('detail-map'),
        mapOptions
    );

    var bounds = new google.maps.LatLngBounds();
    var latSum = 0;
    var lngSum = 0;
    $.each(venueMarkers, function(i, marker) {
        var markerLatLng = new google.maps.LatLng(marker.lat, marker.lng);
        new google.maps.Marker({
            position: markerLatLng,
            map: detailMap,
            title: 'Venue location on ' + marker.serviceName
        });
        bounds.extend(markerLatLng);
        latSum += parseFloat(marker.lat);
        lngSum += parseFloat(marker.lng);
    });
    // Add average location marker
    if (venueMarkers.length > 1) { // only when merging venues
        var markerLatLng = new google.maps.LatLng(latSum / venueMarkers.length, lngSum / venueMarkers.length);
        new google.maps.Marker({
            position: markerLatLng,
            map: detailMap,
            title: 'Average venue location',
            icon: 'https://chart.googleapis.com/chart?chst=d_map_pin_icon&chld=glyphish_star|36c',
            zIndex: 99999
        });
    }
    var boundsInitListener = google.maps.event.addListener(detailMap, 'bounds_changed', function() { // limit maximum zoom after fit bounds
        google.maps.event.removeListener(boundsInitListener); // do it only once
        if (detailMap.getZoom() > 17) {
            detailMap.setZoom(17);
        }
        // Why use listener? It is way how to ensure is is called after map init.
    });
    detailMap.fitBounds(bounds);
}
