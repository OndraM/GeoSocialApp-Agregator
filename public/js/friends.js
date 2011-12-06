function initFriends(friends) {
    initFriendsMap(friends);
}

function initFriendsMap(friends) {
    //var latlng = new google.maps.LatLng(" . $this->pois[0]->lat . "," . $this->pois[0]->lng . ");

    var mapOptions = {
        zoom: 16,
        //center: latlng,
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
    $.each(friends, function(i, marker) {
        new google.maps.Marker({
            position: marker.location,
            map: map,
            title: marker.name + ' at ' + marker.poiName + ' (on ' + marker.service + ')'
        });
        bounds.extend(marker.location);
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