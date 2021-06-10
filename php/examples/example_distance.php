<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $distance = $client->distance("1.24703,51.94967", "28.68018,40.96205");
    print_r($distance);
} catch (\Exceptions\ApiErrorException $e) {
    print_r($e->getMessage() . PHP_EOL);
}