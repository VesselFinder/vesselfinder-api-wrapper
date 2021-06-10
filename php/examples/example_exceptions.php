<?php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;
use Exceptions\ApiErrorException;
use Exceptions\ApiRequestErrorException;
use Exceptions\ApiInvalidArgumentsException;

$client = new VesselFinderApi('-- Input your userkey here --');

try {
    $vessels = $client->vessels([9228801,9441271], 227441980);
} catch (ApiInvalidArgumentsException $e) {
    echo sprintf("This block is for Invalid Arguments: %s", $e->getMessage()) . PHP_EOL;
} catch (ApiRequestErrorException $e) {
    echo sprintf("This block is for Request Error: %s", $e->getMessage()) . PHP_EOL;
} catch (ApiErrorException $e) {
    echo sprintf("General error: %s", $e->getMessage()) . PHP_EOL;
}