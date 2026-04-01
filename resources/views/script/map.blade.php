<script>
    function initMap() {
        var map = document.getElementById('maps'); // Replace with your map element ID
        var googleMap = new google.maps.Map(map, {
            center: { lat: {{ $site->coordinate['lat'] }}, lng: {{ $site->coordinate['lng'] }} },
            zoom: 12
        });

        var polylineCoordinates = {{ json_encode($polyline_coordinates) }};
        var polyline = new google.maps.Polyline({
            path: polylineCoordinates,
            strokeColor: '#FF0000', // red
            strokeWeight: 5,
            strokeOpacity: 0.8
        });

        polyline.setMap(googleMap);
    }
</script>