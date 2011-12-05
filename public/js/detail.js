function initDetail(markers) {
    initDetailMap(markers);
    
    // Show only 10 first tips, other on demand
    var allTips = $('#venue-tips').next('ul').children();
    var tipsLimit = 10;
    if (allTips.length > tipsLimit) {
        var hideTips = allTips.slice(tipsLimit);
        hideTips.hide();
        $('<li><a href=\"#\">Only ' + tipsLimit + ' first tips are shown. Click here to show remaining ' + hideTips.length + '.</a></li>')
            .appendTo($('#venue-tips').next('ul'))
            .click(function() {
                hideTips.show();
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

function initDetailMap(markers) {
    var latlng = new google.maps.LatLng(" . $this->pois[0]->lat . "," . $this->pois[0]->lng . ");
    
    var mapOptions = {
        zoom: 16,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
        // TODO: hide some control elements?
    };
    var map = new google.maps.Map(
        document.getElementById('detail-map'),
        mapOptions
    );    
    
    var bounds = new google.maps.LatLngBounds ();
    $.each(markers, function(i, marker) {    
        new google.maps.Marker({
            position: marker[0],
            map: map,
            title: marker[1]
        });
      bounds.extend(marker[0]);
    });    
    var boundsInitListener = google.maps.event.addListener(map, 'bounds_changed', function() { // limit maximum zoom after fit bounds 
        google.maps.event.removeListener(boundsInitListener); // do it only once
        if (map.getZoom() > 17) {
            map.setZoom(17);
        }
        // Why use listener? It is way how to ensure is is called after map init.
    });
    map.fitBounds(bounds);   
}
