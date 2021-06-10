<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $masterdata = $client->masterData([9466233, 9723708, 9328649]);
    print_r($masterdata);
} catch (\Exceptions\ApiErrorException $e) {
    print_r($e->getMessage() . PHP_EOL);
}