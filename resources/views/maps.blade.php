<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>Google Maps - Show nearest restaurants</title>

    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places"></script>

    <script type="text/javascript">

        var map, places, iw;
        var markers = [];
        var centerMarker;
        var mylat;
        var mylon;
        var myLatlng;


        var hostnameRegexp = new RegExp('^https?://.+?/');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(initialize);
        } else {
            error('not supported');
        }


        function initialize(position) {

            var myLatlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

            mylat = myLatlng.lat();
            mylon = myLatlng.lng();
            var myOptions = {
                zoom: 15,
                center: myLatlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            }
            map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
            places = new google.maps.places.PlacesService(map);
            google.maps.event.addListener(map, 'tilesloaded', tilesLoaded);
        }

        function tilesLoaded() {

            var type = "restaurant";
            var keyword = "";
            var rankBy = "distance";
            var search = {};

            search.types = [type];
            search.rankBy = google.maps.places.RankBy.DISTANCE;
            search.location = map.getCenter();

            centerMarker = new google.maps.Marker({
                position: search.location,
                animation: google.maps.Animation.DROP,
                map: map
            });

            places.search(search, function(results, status) {
                if (status == google.maps.places.PlacesServiceStatus.OK) {
                    //alert(results.length); die();
                    for (var i = 0; i < results.length; i++) {
                        var icon = 'icons/number_' + (i+1) + '.png';
                        markers.push(new google.maps.Marker({
                            position: results[i].geometry.location,
                            animation: google.maps.Animation.DROP,
                            icon: icon
                        }));
                        google.maps.event.addListener(markers[i], 'click', getDetails(results[i], i));
                        window.setTimeout(dropMarker(i), i * 100);
                        addResult(results[i], i);
                    }
                }
            });
            google.maps.event.clearListeners(map, 'tilesloaded');
        }

        function dropMarker(i) {
            return function() {
                if (markers[i]) {
                    markers[i].setMap(map);
                }
            }
        }

        function addResult(result, i) {
            var results = document.getElementById('results');
            var tr = document.createElement('tr');
            tr.style.backgroundColor = (i% 2 == 0 ? '#F0F0F0' : '#FFFFFF');
            tr.onclick = function() {
                google.maps.event.trigger(markers[i], 'click');
            };

            var iconTd = document.createElement('td');
            var nameTd = document.createElement('td');
            var icon = document.createElement('img');
            icon.src = 'icons/number_' + (i+1) + '.png';
            icon.setAttribute('class', 'placeIcon');
            icon.setAttribute('className', 'placeIcon');
            var name = document.createTextNode(result.name);
            iconTd.appendChild(icon);
            nameTd.appendChild(name);
            tr.appendChild(iconTd);
            tr.appendChild(nameTd);
            results.appendChild(tr);
        }

        function getDetails(result, i) {
            return function() {
                places.getDetails({
                    reference: result.reference
                }, showInfoWindow(i));
            }
        }

        function showInfoWindow(i) {
            return function(place, status) {
                if (iw) {
                    iw.close();
                    iw = null;
                }

                if (status == google.maps.places.PlacesServiceStatus.OK) {
                    iw = new google.maps.InfoWindow({
                        content: getIWContent(place)
                    });
                    iw.open(map, markers[i]);
                }
            }
        }

        function getIWContent(place) {
            var directionsDisplay = new google.maps.DirectionsRenderer;
            var directionsService = new google.maps.DirectionsService;

            var dstlat = place.geometry.location.lat();
            var dstlon = place.geometry.location.lng();

            var myOptions = {
                zoom: 15,
                center: myLatlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            }
            map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);

            directionsDisplay.setMap(map);
            calculateAndDisplayRoute(directionsService, directionsDisplay, dstlat, dstlon);

            var content = '';
            content += '<table>';
            content += '<tr class="iw_table_row">';
            content += '<td style="text-align: right"><img class="hotelIcon" src="' + place.icon + '"/></td>';
            content += '<td><b><a href="' + place.url + '">' + place.name + '</a></b></td></tr>';
            content += '<tr class="iw_table_row"><td class="iw_attribute_name">Address:</td><td>' + place.vicinity + '</td></tr>';

            if (place.formatted_phone_number) {
                content += '<tr class="iw_table_row"><td class="iw_attribute_name">Telephone:</td><td>' + place.formatted_phone_number + '</td></tr>';
            }
            if (place.rating) {
                var ratingHtml = '';
                for (var i = 0; i < 5; i++) {
                    if (place.rating < (i + 0.5)) {
                        ratingHtml += '&#10025;';
                    } else {
                        ratingHtml += '&#10029;';
                    }
                }
                content += '<tr class="iw_table_row"><td class="iw_attribute_name">Rating:</td><td><span id="rating">' + ratingHtml + '</span></td></tr>';
            }
            if (place.website) {
                var fullUrl = place.website;
                var website = hostnameRegexp.exec(place.website);
                if (website == null) {
                    website = 'http://' + place.website + '/';
                    fullUrl = website;
                }
                content += '<tr class="iw_table_row"><td class="iw_attribute_name">Website:</td><td><a href="' + fullUrl + '">' + website + '</a></td></tr>';
            }
            content += '</table>';
            return content;
        }

        function calculateAndDisplayRoute(directionsService, directionsDisplay, dstlat, dstlon) {

            directionsService.route({
                origin: {lat: mylat, lng: mylon},  // Haight.
                destination: {lat: dstlat, lng: dstlon},
                travelMode: google.maps.TravelMode.DRIVING
            }, function(response, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    directionsDisplay.setDirections(response);
                } else {
                    window.alert('Directions request failed due to ' + status);
                }
            });
        }

        google.maps.event.addDomListener(window, 'load', initialize);

    </script>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            margin:0;
            padding:0;
        }
        table {
            font-size: 12px;
        }
        #map_canvas {
            position: absolute;
            width: 80%;
            height: 100%;
            float: left;
            top: 0px;
            border: 1px solid grey;
        }
        #listing {
            position: relative;
            width: 20%;
            height: 100%;
            overflow: auto;
            float: right;
            top: 0px;
            cursor: pointer;
            overflow-x: hidden;
            border: 1px solid lightgrey;
        }

        .hotelIcon {
            width: 24px;
            height: 24px;
        }
        #resultsTable {
            border-collapse: collapse;
            width: 240px;
        }
        #rating {
            font-size: 13px;
            font-family: "Times New Roman";
        }

        .iw_table_row {
            height: 18px;
        }
        .iw_attribute_name {
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>

<div id="map_canvas"></div>
<div id="listing"><table id="resultsTable"><tbody id="results"></tbody></table></div>


</body>
</html>
