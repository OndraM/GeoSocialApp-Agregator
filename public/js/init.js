/*
 * Global document.ready initialization
 */
$(document).ready(function() {
	// Open external Links in new window
	$('a[href^="http://"]').click(function() {
		this.target = '_blank';
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
var getNearbyUrl = '/poi/get-nearby';
var infoWindow;


/*
 * Init main page:
 *  Bind events to forms
 *  Init map
 *  Do geolocate
 */
function init() {
    $('#searchform').submit(function() {
        
        toggleVenuesLoading();
        $.getJSON(getNearbyUrl + '?' + $('#searchform input[type=text]').serialize(), function(data) {
            toggleVenuesLoading();
            if (typeof(data) == 'object'
                    && typeof(data.venues) != 'undefined'
                    && data.venues.length > 0) {
                var listItems = [];
                var mapItems = [];
                $.each(data.venues, function(key, val) {
                    listItems.push('<li id="' + val.id + '">' + val.name + '</li>');
                    mapItems.push(val);
                });

                addPoisOnMap(mapItems);

                $('#venues-list').html(
                    $('<ul/>', {
                        html: listItems.join('')
                    })
                );
            } else {
                $('#venues-list').html('<p>Sorry, no matching places nearby.</p>');
            }

        });
        setMapCenter( $('#searchform input[name=lat]').val(), $('#searchform input[name=long]').val());
        return false;
    });
    
    $('#addressform').submit(function() {
        geocoder.geocode({"address": $('#addressform input[name=address]').val()}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				var latlng = results[0].geometry.location;
				map.panTo(latlng);
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
    mapCenterPoiner = new google.maps.Marker({
        position: latlng,
        map: map,
        icon: mapCenterImage,
        zIndex: -1
    });
        
    google.maps.event.addListener(map, 'dragend', function() {
		var latlng = map.getCenter();
        //console.log(latlng.toUrlValue());
        $('#searchform input[name=lat]').val(latlng.lat().toFixed(6));
        $('#searchform input[name=long]').val(latlng.lng().toFixed(6));
        $('#searchform').submit();
	});
    
    infoWindow = new google.maps.InfoWindow({
        maxWidth: 350
    });
}

/*
 * Set map center to lat, lng
 */

function setMapCenter(lat, lng) {
    if (map != undefined) {
        var newMapCenter = new google.maps.LatLng(lat, lng);
        var mapCenter = map.getCenter();
        if (!mapCenter.equals(newMapCenter)) {
            map.panTo(newMapCenter);
            mapCenterPoiner.setMap(null);
            mapCenterPoiner = new google.maps.Marker({
                position: newMapCenter,
                map: map,
                icon: mapCenterImage,
                zIndex: -1
            });
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
    var content;
    // clear current markers first
    clearMap();
    
    // put pois on map
    $.each(pois, function(i, poi) {
        poiMarkers[poi.id] = new google.maps.Marker({
                position: new google.maps.LatLng(poi.location.lat, poi.location.lng),
                map: map,
                title: poi.name
            });
            
        google.maps.event.addListener(poiMarkers[poi.id], 'click', function() {
            content = '<div id="infoWindow">'
                + '<div><b>' + poi.name + ' </b></div>'
                + '<div>' + poi.location.address + '</div>'
                + '<div>' + poi.location.postalCode + '  ' + poi.location.city + '</div>'
                + ''
                + ''
                + '</div>';
            infoWindow.setContent(content);
            infoWindow.open(map, poiMarkers[poi.id]);
        });
    })
}

/*
 * Toggle venues loading spinner & wait cursor
 */
function toggleVenuesLoading() {
    if ($('#venues-list').hasClass('loading')) {
        $('#venues-list').removeClass('loading');
        $('#venues-wrapper').css('cursor', 'default')
    } else {
        $('#venues-list').html('<ul><li><img src="/images/spinner.gif" alt="" /> Loading...</li></ul>');
        $('#venues-list').addClass('loading');
        $('#venues-wrapper').css('cursor', 'progress')
    }        
}
