<?php
include('../../includes/db.php');

$lat = $_GET['lat'] ?? 0;
$lon = $_GET['lon'] ?? 0;
$cafe_lat = 6.9271;
$cafe_lon = 79.8612;

function calculateDistance($lat1,$lon1,$lat2,$lon2){
    $earth_radius = 6371;
    $dLat = deg2rad($lat2-$lat1);
    $dLon = deg2rad($lon2-$lon1);
    $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
    return $earth_radius * (2 * atan2(sqrt($a),sqrt(1-$a)));
}

$distance = calculateDistance($cafe_lat, $cafe_lon, $lat, $lon);
$rate_per_km = 50;
echo round($distance * $rate_per_km, 2);
?>