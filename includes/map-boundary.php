<?php
// The bundled Irisan file contains one polygon. Test its edges to keep saved
// coordinates inside the study area; a rectangular check would include other areas.
function inside_irisan($lat, $lng) {
    $data = json_decode(file_get_contents(__DIR__ . '/../assets/map/irisan.geojson'), true);
    $points = $data['features'][0]['geometry']['coordinates'][0];
    $inside = false;
    $previous = count($points) - 1;
    for ($i = 0; $i < count($points); $i++) {
        [$x, $y] = $points[$i];
        [$old_x, $old_y] = $points[$previous];
        if (($y > $lat) !== ($old_y > $lat)
            && $lng < ($old_x - $x) * ($lat - $y) / ($old_y - $y) + $x) {
            $inside = !$inside;
        }
        $previous = $i;
    }
    return $inside;
}
