<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $expected_arrivals = $client->expectedArrivals("GIGIB", ['interval' => 10080, 'limit' => 5]);
    print_r($expected_arrivals);
} catch (\Exceptions\ApiErrorException $e) {
    print_r($e->getMessage() . PHP_EOL);
}