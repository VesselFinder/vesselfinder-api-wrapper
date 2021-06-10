<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $livedata = $client->liveData(['format' => 'xml']);
    $last_info = $client->getLastInfo();
    print_r($livedata);
    print_r($last_info);
} catch (\Exceptions\ApiErrorException $e) {
    print_r($e->getMessage() . PHP_EOL);
}