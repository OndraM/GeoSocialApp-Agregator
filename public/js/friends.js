function initFriends(friends) {
    initFriendsMap(friends);
}

function initFriendsMap(friends) {
    var centerLatLng;
    if ( $('#friends-map').attr('data-cLat') != ''
        && $('#friends-map').attr('data-cLng') != '' ) { // if set, get location from form values
        centerLatLng = new google.maps.LatLng($('#friends-map').attr('data-cLat'),
                                $('#friends-map').attr('data-cLng'));
    } else { // otherwise use default location
        centerLatLng = new google.maps.LatLng(50.087811, 14.42046);
    }
    console.log(centerLatLng);
    var mapOptions = {
        zoom: 16,
        center: centerLatLng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControlOptions: {
            // disable terrain map:
            mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE]

        }
    };
    var map = new google.maps.Map(
        document.getElementById('friends-map'),
        mapOptions
    );

    var bounds = new google.maps.LatLngBounds();
    $.each(friends, function(i, friend) {
        var friendLatLng = new google.maps.LatLng(friend.lat, friend.lng);
        new google.maps.Marker({
            position: friendLatLng,
            map: map,
            title: friend.name + ' at ' + friend.poiName + ' (on ' + friend.serviceName + ')'
        });
        bounds.extend(friendLatLng);
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