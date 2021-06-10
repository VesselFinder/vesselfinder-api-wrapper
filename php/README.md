# PHP Wrapper
PHP wrapper for the AIS API of [vesselfinder.com](vesselfinder.com) along with some examples.</br>
[You can find the full API documentation here...](https://api.vesselfinder.com/docs/)
## Initialise API
* **$userkey**: This is your personal userkey for the API
* **$errormode**: If this parameter is set to true, every error from the API will be with Response Code **409**. But if it is set to False, the Response Code will be always **200**, doesn't matter if API is returning an error or data. Default is **false**.
* **$save_last_info**: If this parameter is set to true, the wrapper will save the info from your last request (all `X-API` headers), and you will be able to see it from `get_last_info()` function. But if it is set to False, you will not be able to use that function. Default is **true**.
```php
include __DIR__ . '/../VesselFinderApi.php';

use API\VesselFinderApi;

$client = new VesselFinderApi('-- Input your userkey here --', false, true);
```
## API Calls
* (**GET**) VesselFinderApi->status($format)
* (**GET**) VesselFinderApi->vessels($imo\*, $mmsi\*, $format, $extradata, $sat, $interval)
* (**GET**) VesselFinderApi->vesselsList($format, $interval)
* (**GET**) VesselFinderApi->liveData($format, $interval)
* (**GET**) VesselFinderApi->portCalls($interval\*, $format, $imo, $mmsi, $locode, $extradata, $limit, $event, $fromdate, $todate)
* (**GET**) VesselFinderApi->expectedArrivals($locode\*, $format, $interval, $fromdate, $todate, $extradata, $limit)
* (**GET**) VesselFinderApi->masterData($imo\*, $format)
* (**GET**) VesselFinderApi->distance($from\*, $to\*, $gateways, $eca, $epsg3857)
* (**GET**) VesselFinderApi->getListManager()
* (**POST**) VesselFinderApi->listManagerAddVessels($imo, $mmsi)
* (**PUT**) VesselFinderApi->listManagerReplaceAllVessels($imo, $mmsi)
* (**DELETE**) VesselFinderApi->listManagerDeleteVessels($imo, $mmsi)
  <br>
  *These parameters are **required**
## Error Handling
* `ApiErrorException`: this is the general Exception, and you can use it to catch all Exceptions from the wrapper
* `ApiInvalidArgumentsException`: this Exception is being thrown if user input is invalid
* `ApiRequestErrorException`: this Exception is being thrown when the API request returns error
```php
use Exceptions\ApiErrorException;
use Exceptions\ApiRequestErrorException;
use Exceptions\ApiInvalidArgumentsException;

try {
    $vessels = $client->vessels([$imo1, $imo2], $some_mmsi);
} catch (ApiInvalidArgumentsException $e) {
    echo sprintf("This block is for Invalid Arguments: %s", $e->getMessage()) . PHP_EOL;
} catch (ApiRequestErrorException $e) {
    echo sprintf("This block is for Request Error: %s", $e->getMessage()) . PHP_EOL;
} catch (ApiErrorException $e) {
    echo sprintf("General error: %s", $e->getMessage()) . PHP_EOL;
}
```
or you can just catch `ApiErrorException`
```php
use Exceptions\ApiErrorException;

try {
    $vessels = $client->vessels([$imo1, $imo2], $some_mmsi);
} catch (ApiErrorException $e) {
    echo sprintf("General error: %s", $e->getMessage()) . PHP_EOL;
}
```
## Usage of methods
See `/examples` directory for full examples for the methods. By default, all methods are sending `GET` requests.
### Get last request info
This method will give you the last request `X-API` headers, which may contain how much Credits you have left, or how many IMOs you have added to your List Manager and etc.
```php
$last_info = $client->getLastInfo();
```
### Status
```php
$status = $client->status();
```
### Vessels
```php
$vessels = $client->vessels([$imo1, $imo2, $imo3 ...], [$mmsi1, $mmsi2, $mmsi3 ...]);
```
### Vessels List
```php
$vessels_list = $client->vesselsList();
```
### Live Data
```php
$live_data = $client->liveData();
```
### Port Calls
This method can be executed by one of the two following examples only
```php
$port_calls = $client->portCalls($some_interval, ['imo' => $some_imo]);
```
or
```php
$port_calls = $client->portCalls($some_interval, ['locode' => $some_locode]);
```
### Expected Arrivals
```php
$expected_arrivals = $client->expectedArrivals($some_locode, ['interval' => $some_interval, 'limit' => $some_limit]);
```
### Master Data
```php
$master_data = $client->masterData([$imo1, $imo2, $imo3 ...]);
```
### Distance
```php
$distance = $client->distance($some_coordinate_from, $some_coordinate_to);
```
### List Manager
```php
$list_manager = $client->getListMManager();
```
### Add vessels to List Manager
This method is sending `POST` request.
```php
$client->listManagerAddVessels(['imo' => [$imo1, $imo2], 'mmsi' => [$mmsi1, $mmsi2]]);
```
### Replace all vessels in your List Manager with `imo` and/or `mmsi` you send
This method is sending `PUT` request.
```php
$client->listManagerReplaceAllVessels(['imo' => [$imo1, $imo2], 'mmsi' => [$mmsi1, $mmsi2]]);
```
### Delete vessels from your List Manager
This method is sending `DELETE` request.
```php
$client->listManagerDeleteVessels(['imo' => [$imo1, $imo2], 'mmsi' => [$mmsi1, $mmsi2]]);
```