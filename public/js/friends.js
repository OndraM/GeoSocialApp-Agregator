function initFriends(markers) {
    initFriendsMap(markers);
}

function initFriendsMap(markers) {
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