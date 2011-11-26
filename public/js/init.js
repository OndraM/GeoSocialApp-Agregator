/*
 * Global document.ready initialization
 */
$(document).ready(function() {
	// Open external Links in new window
	$('a[href^="http://"]').click(function() {
		this.target = '_blank';
	});
    
    // Fancybox init
    $(".fancybox").fancybox();
    
    // Fancubox popUp windows
    $(".popUp").fancybox({
        maxWidth	: 800,
        /*maxHeight	: 600,*/
        fitToView	: false,
        width		: '70%',
        height		: '70%',
        autoSize	: false,
        closeClick	: false,
        openEffect	: 'none',
        closeEffect	: 'none',
        helpers:  {
            title : null
        }
    });
    
});

/*
 * Global variables
 */
var map;
var geocoder = new google.maps.Geocoder();
var poiMarkers = [];
var mapCenterPoiner;
var mapCenterImage = './images/pointer-small.png';
var mapCenterTimeout;
var getNearbyUrl = '/poi/get-nearby';
var infoWindow;
var xhrRequest;

/*
 * Init main page:
 *  Bind events to forms
 *  Init map
 *  Do geolocate
 */
function init() {
    $.ajaxSetup({
        "error": function(jqXHR, textStatus, errorThrown) {
            if (textStatus == 'abort') { // don't throw error when xhr request aborted from client side
               return;
            }
            toggleVenuesLoading(true);
            // show error message
            $('#venues-list').html('<p>Sorry, there was an error loading your request :-(.</p>');
        },
            timeout: 60000 // set timeout to 60 seconds
    });


    $('#searchform').submit(function() {
        // show ajax spinner
        toggleVenuesLoading();
        
        // reset possible highlight
        $('#searchform input[type=submit]').stop().css({backgroundColor: "#F6F6F6"});
        
        // check whether xhrRequest isn't already running
        if (xhrRequest && xhrRequest.readyState != 4){
            xhrRequest.abort(); // if so, abort it first
        }
        
        // do the xhr request itself
        xhrRequest = $.ajax({
          url: getNearbyUrl + '?' + $('#searchform input').serialize(),
          dataType: 'json',
          success: function(data) {
            // got result; don't show venues loading anymore
            toggleVenuesLoading(true);
            
            // something found:
            if (typeof data == 'object'
                    && typeof data.pois != 'undefined'
                    && data.pois.length > 0) {
                var listItems = [];
                var mapItems = [];
                $.each(data.pois, function(key, poi) {
                    var itemContent = '<li id="' + poi.id + '"><a>' + poi.name + '</a>';
                    if (typeof poi.distance !== "undefined")
                        itemContent  += ' <span>('  + poi.distance + ' m)</span>'
                    $.each(poi.types, function(i, type) {
                        itemContent  += '<img src="/images/icon-'
                                     + type
                                     + '.png" alt="'
                                     + type
                                     + '" class="icon" />';
                    });
                    itemContent  += '</li>';
                    listItems.push(itemContent);
                    mapItems.push(poi);
                });

                $('#venues-list').html(
                    $('<ul/>', {
                        html: listItems.join('')
                    })
                );
                addPoisOnMap(mapItems);
            } else {  // no pois found
                // clear the previous ones
                clearMap();
                // show error message
                $('#venues-list').html('<p>Sorry, no matching places nearby.</p><p>Try zooming out the map or redefining name or category filter.</p>');
            }

        }});
        // move map to values in from (in case they have been changed by user input, not by dragging map)
        setMapCenter( $('#searchform input[name=lat]').val(), $('#searchform input[name=long]').val());
        
        return false;
    });
    
    // when any text value is changed by manual user input, highlight submit button
    $('#searchform input[type=text]').change(function() {
        $('#searchform input[type=submit]').animate({backgroundColor: "#ffe45c"}, 500);
    });
    
    // also when any checbox is clicked, highlight submit button
    $('#searchform input[type=checkbox]').click(function() {
        $('#searchform input[type=submit]').animate({backgroundColor: "#ffe45c"}, 500);
    });    
    
    $('#addressform').submit(function() {
        geocoder.geocode({"address": $('#addressform input[name=address]').val()}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var latlng = results[0].geometry.location;
				map.panTo(latlng);
                // when map is so zoomed in or out, zoom to default 
                if (map.getZoom() > 17 || map.getZoom() < 12) {
					map.setZoom(14);
                    getAndSetRadius();
                }
                $('#searchform input[name=lat]').val(latlng.lat().toFixed(6));
                $('#searchform input[name=long]').val(latlng.lng().toFixed(6));
                $('#searchform').submit();
			} else {
				// console.error("Geolocation error: " + status);
            }
		});
        return false;
    });
    
    $('#searchform').submit();
    initMap();
    //doGeolocate(); // commented just for testing purposes
}

/*
 * Do geolocation (if available)
 */

function doGeolocate() {
    if (navigator.geolocation) {
        // TODO: show note about geolocation beeing loaded?
        navigator.geolocation.getCurrentPosition(
            function(position) {
                   $('#searchform input[name=lat]').val(position.coords.latitude);
                   $('#searchform input[name=long]').val(position.coords.longitude);
                   $('#searchform').submit();
            }
        );
    }
}

/*
 * Initialize google map
 */

function initMap() {
    var latlng;
    if ( $('#searchform input[name=lat]').val() != ''
        && $('#searchform input[name=long]').val() != '' ) { // if set, get location from form values
        latlng = new google.maps.LatLng($('#searchform input[name=lat]').val(),
                                            $('#searchform input[name=long]').val());
    } else { // otherwise use default location
        latlng = new google.maps.LatLng(50.087811, 14.42046);
    }
    
    var mapOptions = {
        zoom: 14,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(
        document.getElementById('venues-map'),
        mapOptions
    );     
    showMapCenterPointer(latlng, 5000);
        
    google.maps.event.addListener(map, 'dragend', function() {
		var latlng = map.getCenter();
        //console.log(latlng.toUrlValue());
        $('#searchform input[name=lat]').val(latlng.lat().toFixed(6));
        $('#searchform input[name=long]').val(latlng.lng().toFixed(6));
        $('#searchform').submit();
	});
    
    google.maps.event.addListener(map, 'zoom_changed', function() {
		var latlng = map.getCenter();
        getAndSetRadius();
        $('#searchform input[name=lat]').val(latlng.lat().toFixed(6));
        $('#searchform input[name=long]').val(latlng.lng().toFixed(6));        
        showMapCenterPointer(latlng, 1000);
	});    
    
    infoWindow = new google.maps.InfoWindow({
        maxWidth: 400
    });
    
    var input = document.getElementById('search-address');
    var autocomplete = new google.maps.places.Autocomplete(input, {types: ['geocode']});
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
       getAndSetRadius(); 
    });

    autocomplete.bindTo('bounds', map);
}

/*
 * Set map center to lat, lng
 */

function setMapCenter(lat, lng) {
    if (typeof map !== "undefined") {
        var newMapCenter = new google.maps.LatLng(lat, lng);
        var mapCenter = map.getCenter();
        if (!mapCenter.equals(newMapCenter)) {            
            map.panTo(newMapCenter);
            showMapCenterPointer(newMapCenter, 3000);
        }
        
    }
}

/*
 * Clear pointer from map
 */

function clearMap() {
    // $.each wont work on associative array, see jQuery issue #4319
    for (var marker in poiMarkers) {
		poiMarkers[marker].setMap(null);
	}
    poiMarkers = [];

}

/*
 * Add pois object to map
 */

function addPoisOnMap(pois) {
    // clear current markers first
    clearMap();
    
    // put pois on map
    $.each(pois, function(i, poi) {
        var content;
        poiMarkers[poi.id] = new google.maps.Marker({
                position: new google.maps.LatLng(poi.lat, poi.lng),
                map: map,
                title: poi.name
            });
        content = '<div id="infoWindow">'
            + '<div><strong>' + poi.name + ' </strong>';
        $.each(poi.types, function(i, type) {
            content += '<img src="/images/icon-'
                + type
                + '.png" alt="'
                + type
                + '" class="icon" />';
        });
        content += ' </div>';
        if (typeof poi.address !== "undefined" && poi.address)
            content += '<div>' + poi.address + '</div>';        
        
        
        if (poi.pois.length > 1) { // if this is more merged venues, list original names
            content += '<ul>'
            $.each(poi.pois, function(i, specificPoi) {
                content += '<li>' + specificPoi.name
                        + '<img src="/images/icon-'
                        + specificPoi.type
                        + '.png" alt="'
                        + specificPoi.type
                        + '" class="icon" />'
                        + '</li>';
            });
            content += '</ul>';
        }
        
        detailPois = [];
        $.each(poi.pois, function(i, specificPoi) {
            // "id = type" order is there on purpose (to allow multiple pois from one service)
            detailPois.push(encodeURIComponent(
                                specificPoi.type == 'gg' ?
                                    specificPoi.reference : // exception for google places - use reference instead of ID
                                    specificPoi.id)
                            + '='
                            + encodeURIComponent(specificPoi.type));
        });

        detailUrl = '/poi/show-detail?' + detailPois.join('&');
        
        content += '<div>' 
                + '<a href="' + detailUrl + '" class ="popUp fancybox.ajax" title="Show all details of this venue">Show details &raquo;</a>'
                + '</div>';        
        
        content += '</div>';

        google.maps.event.addListener(poiMarkers[poi.id], 'click', function() {
            infoWindow.setContent(content);
            infoWindow.open(map, poiMarkers[poi.id]);
        });
        
        $('li#' + poi.id).click(function() {
            infoWindow.setContent(content);
            infoWindow.open(map, poiMarkers[$(this).attr('id')]);
        });
        
        /*$('li#' + poi.id).mouseover(function() {
            if (poiMarkers[$(this).attr('id')].getAnimation() == null) {
                    poiMarkers[$(this).attr('id')].setAnimation(google.maps.Animation.BOUNCE);
                    var markerId = $(this).attr('id');
                    setTimeout(function() {poiMarkers[markerId].setAnimation(null);},750);
                    
                }
        });*/
        
        
    })
}

/*
 * Toggle venues loading spinner & wait cursor
 * 
 * @param forceRemove If set, force loading state to off.
 */
function toggleVenuesLoading(forceRemove) {
    if (forceRemove || $('#venues-list').hasClass('loading')) {
        $('#venues-list').removeClass('loading');
        $('#venues-wrapper').css('cursor', 'default')
    } else {
        $('#venues-list').html('<ul><li><img src="/images/spinner.gif" alt="" /> Loading...</li></ul>');
        $('#venues-list').addClass('loading');
        $('#venues-wrapper').css('cursor', 'progress')
    }        
}

/**
 * Calculate radius of current map (if defined) and set it as <input name=radius> value.
 * In map is not defined, return default radius.
 * 
 * Algorithm source: 
 * http://stackoverflow.com/questions/3525670/radius-of-viewable-region-in-google-maps-v3/3527136#3527136
 * 
 * @return int Radius in meters.
 */

function getAndSetRadius() {
    if (typeof map == "undefined") {
        return 2500;
    }
    bounds = map.getBounds();

    center = bounds.getCenter();
    ne = bounds.getNorthEast();

    // r = radius of the earth
    var r = 6378;  

    // Convert lat or lng from decimal degrees into radians (divide by 57.2958)
    var lat1 = (center.lat() / 180) * Math.PI;
    var lng1 = (center.lng() / 180) * Math.PI;
    var lat2 = (ne.lat() / 180) * Math.PI;
    var lng2 = (ne.lng() / 180) * Math.PI;

    // radius = circle radius from center to Northeast corner of bounds
    var radius = Math.round(
        1000 * r * Math.acos(Math.sin(lat1) * Math.sin(lat2) + 
      Math.cos(lat1) * Math.cos(lat2) * Math.cos(lng2 - lng1))
      );
    
    var prevVal = $('#searchform input[name=radius]').val();
    if (prevVal != radius) {
        $('#searchform input[name=radius]').val(radius).change();
    }
    //$('#searchform input[name=radius]')

    return radius;        
}

/**
 * Show map center crosshair pointer for specified time.
 * 
 * @param latlng google.maps.LatLng object
 * @param timeout Timeout in miliseconds
 */

function showMapCenterPointer (latlng, timeout) {
    if (typeof mapCenterPoiner !== "undefined" && mapCenterPoiner) {
        mapCenterPoiner.setMap(null);
    }
    mapCenterPoiner = new google.maps.Marker({
        position: latlng,
        map: map,
        icon: mapCenterImage,
        zIndex: -1
    });
    clearTimeout(mapCenterTimeout);
    mapCenterTimeout = setTimeout("mapCenterPoiner.setMap(null)", timeout);
}
