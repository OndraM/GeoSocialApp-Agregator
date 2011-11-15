$(document).ready(function() {
	// Open external Links in new window
	$('a[href^="http://"]').click(function() {
		this.target = '_blank';
	});

    


});


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

var map;
var geocoder = new google.maps.Geocoder();
var poiMarkers = [];

function mapInit() {
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
        
    google.maps.event.addListener(map, 'dragend', function() {
		var latlng = map.getCenter();
        //console.log(latlng.toUrlValue());
        $('#searchform input[name=lat]').val(latlng.lat().toFixed(6));
        $('#searchform input[name=long]').val(latlng.lng().toFixed(6));
        $('#searchform').submit();
	});
}

function setMapCenter(lat, lng) {
    if (map != undefined) {
        var newMapCenter = new google.maps.LatLng(lat, lng);
        var mapCenter = map.getCenter();
        if (!mapCenter.equals(newMapCenter)) {
            map.panTo(newMapCenter);
        }
    }
}

function clearMap() {
    // $.each wont work on associative array, see jQuery issue #4319
    for (var marker in poiMarkers) {
		poiMarkers[marker].setMap(null);
	}
    poiMarkers = [];

}

function addPoisOnMap(pois) {
    // clear current markers first
    clearMap();
    
    // put pois on map
    $.each(pois, function(i, poi) {
        poiMarkers[poi.id] = new google.maps.Marker({
                position: new google.maps.LatLng(poi.location.lat, poi.location.lng),
                map: map,
                title: poi.name
            });
    })
}

$(document).ready(function() {
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

})
