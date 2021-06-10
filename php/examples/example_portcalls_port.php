<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $port_calls = $client->portCalls(720, ['locode' => 'BGVAR', 'extradata' => 'voyage']);
    print_r($port_calls);
    print_r($client->getLastInfo());
} catch (\Exceptions\ApiErrorException $e) {
    print_r($e->getMessage() . PHP_EOL);
}