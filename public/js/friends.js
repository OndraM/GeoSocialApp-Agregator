/* Global variables */
var friendsMap;
var infoWindow;
var friends;
var friendMarkers = [];
var isFit;
var bounds = new google.maps.LatLngBounds();

/**
 * Init friends scripts
 */
function initFriends(f) {
    friends = f;
    // init map
    initFriendsMap();

    // init buttons
    $('#friends-time-filter').buttonset();
    $('#friend-fitbouds').button();

    // execute filter when clicked
    $('#friends-time-filter label').click(function(){
        filterMarkers($('#' + $(this).attr('for')).val());
    });

    $('#friend-fitbouds').click(function() {
        friendsMap.fitBounds(bounds);
        return false;
    });
}

function filterMarkers(time) {
    var limit;
    var d = new Date();
    switch (time) {
        case '1':
        case '12':
        case '24':
            limit = time * 60 * 60;
            break;
        default:
            limit = 0;
            break;
    }
    $.each(friends, function(i, friend) {
        // limit is all or it has nat yet passed
        if (limit == 0 || (d.getTime()/1000) - limit < friend.date) {
            if (!friendMarkers[friend.id].getMap()) { // marker not present on the map right now
                friendMarkers[friend.id].setMap(friendsMap); // put it here
            }
        } else { // remove marker
            friendMarkers[friend.id].setMap(null);
        }

    })
}

/**
 * Init friends map
 */
function initFriendsMap() {
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
    friendsMap = new google.maps.Map(
        document.getElementById('friends-map'),
        mapOptions
    );
    infoWindow = new google.maps.InfoWindow({
        maxWidth: 400
    });

    var bgMarker;
    $.each(friends, function(i, friend) {
        var friendLatLng = new google.maps.LatLng(friend.lat, friend.lng);
        bounds.extend(friendLatLng);
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
            map           : friendsMap,
            title         : friend.userName + ' at ' + friend.poiName,
            labelClass    : 'friend-marker',
            labelAnchor   : new google.maps.Point(18, 44),
            labelContent  : friendAvatar,
            icon          : bgMarker
        });
        var content = '<div id="infoWindow"><div class="infoWindow-wrapper">'
        content += '<img src="' + friend.avatar + '" alt="' + friend.userName + '" class="avatar" style="width: 64px; height: 64px;" width="64" height="64" />'
                + '<div><strong>' + friend.userName + ' </strong>';
        content += '<img src="/images/icon-'
                + friend.type
                + '.png" alt="'
                + friend.serviceName
                + '" class="icon-right" />';
        content += ' </div>';
        content += '<div>' + friend.poiName + '</div>';
        content += '<div>' + friend.dateFormatted + '</div>';
        if (friend.comment != '') {
            content += '<div><i>' + friend.comment + '</i></div>';
        }
        content += '</div></div>';
        friendMarkers[friend.id] = marker;
        google.maps.event.addListener(marker, 'click', function() {
            infoWindow.setContent(content);
            infoWindow.open(friendsMap, this);
        });

    });

    // Limit maximum and minimum zoom after fit bounds.
    // Why use listener? It is way how to ensure is is called after map init.
    var boundsInitListener = google.maps.event.addListener(friendsMap, 'bounds_changed', function() {
        google.maps.event.removeListener(boundsInitListener); // do it only once
        // zoom is too big, leave it at 16
        if (friendsMap.getZoom() > 16) {
            friendsMap.setZoom(16);
        } else if (friendsMap.getZoom() < 7    // friends did't fit map even at zoom 7 => center it to main map position and zoom to center
            && centerLatLng)            // if centerLatLng not set, leave it automatic
        {
            friendsMap.setZoom(7);
            friendsMap.setCenter(centerLatLng);
        }
        // else do nothing, map center is adjusted automatically from bounds
    });
    friendsMap.fitBounds(bounds);
}
