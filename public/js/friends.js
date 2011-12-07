function initFriends(friends) {
    initFriendsMap(friends);
}

function initFriendsMap(friends) {
    var centerLatLng;
    if ( $('#friends-map').attr('data-cLat') != ''
        && $('#friends-map').attr('data-cLng') != '' ) { // if set, get location from form values
        centerLatLng = new google.maps.LatLng($('#friends-map').attr('data-cLat'),
                                $('#friends-map').attr('data-cLng'));
    } else { // otherwise keep unset - center will by automatic from bounds
        //centerLatLng = new google.maps.LatLng(50.087811, 14.42046);
    }
    var mapOptions = {
        zoom: 11,
        // center: centerLatLng, // center is either automatic (when friends fits map) or according to main map (when friends don't fit)
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
    var bgMarker;
    $.each(friends, function(i, friend) {
        var friendLatLng = new google.maps.LatLng(friend.lat, friend.lng);
        bgMarker = new google.maps.MarkerImage(
                'https://chart.googleapis.com/chart?chst=d_bubble_texts_big&chld=edge_bc|000|000|.',
                new google.maps.Size(27, 26),   // size
                new google.maps.Point(0, 0),    // origin coordinates in image
                new google.maps.Point(14, 26),  // anchor coordinates
                new google.maps.Size(27, 26));  // size after resize
        var friendAvatar = document.createElement('img');
     	friendAvatar.src = friend.avatar;
     	friendAvatar.width = 32;
		var marker = new MarkerWithLabel({
              position      : friendLatLng,
			  map           : map,		      
		      title         : friend.name + ' at ' + friend.poiName,
		      labelClass    : 'friend-marker',
		      labelAnchor   : new google.maps.Point(18, 44),
		      labelContent  : friendAvatar,
              icon          : bgMarker
		});
        bounds.extend(friendLatLng);
    });

    // Limit maximum and minimum zoom after fit bounds.
    // Why use listener? It is way how to ensure is is called after map init.
    var boundsInitListener = google.maps.event.addListener(map, 'bounds_changed', function() {
        google.maps.event.removeListener(boundsInitListener); // do it only once
        // zoom is too big, leave it at 16
        if (map.getZoom() > 16) {
            map.setZoom(16);
        } else if (map.getZoom() < 7    // friends did't fit map even at zoom 7 => center it to main map position and zoom to center
            && centerLatLng)            // if centerLatLng not set, leave it automatic
        {
            map.setZoom(7);
            map.setCenter(centerLatLng);
        }
        // else do nothing, map center is adjusted automatically from bounds
    });
    map.fitBounds(bounds);
}