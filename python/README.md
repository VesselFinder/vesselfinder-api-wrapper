# Python Wrapper
Python wrapper for the AIS API of [vesselfinder.com](vesselfinder.com) along with some examples.</br>
[You can find the full API documentation here.](https://api.vesselfinder.com/docs/).

## Initialise API
On initialization you should set these parameters:
* **userkey**: This is your personal userkey for the API
* **errormode**: If this parameter is set to True, every error from the API will be with Response Code **409**. But if it is set to False, the Response Code will be always **200**, doesn't matter if the API is returning an error or data. Default is **False**.
* **save_last_info**: If this parameter is set to True, the wrapper will save the info from your last request (all `X-API` headers), and you will be able to see it from `get_last_info()` function. But if it is set to False, you will not be able to use that function. Default is **True**.
```python
from vesselfinder_api import VesselFinderApi

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)
```
## API Calls
* (**GET**) VesselFinderApi.status(format)
* (**GET**) VesselFinderApi.vessels(imo\*, mmsi\*, format, extradata, sat, interval)
* (**GET**) VesselFinderApi.vessels_list(format, interval)
* (**GET**) VesselFinderApi.live_data(format, interval)
* (**GET**) VesselFinderApi.port_calls(interval\*, format, imo, mmsi, locode, extradata, limit, event, fromdate, todate)
* (**GET**) VesselFinderApi.expected_arrivals(locode\*, format, interval, fromdate, todate, extradata, limit)
* (**GET**) VesselFinderApi.master_data(imo\*, format)
* (**GET**) VesselFinderApi.distance(_from\*, _to\*, gateways, eca, epsg3857)
* (**GET**) VesselFinderApi.get_list_manager()
* (**POST**) VesselFinderApi.list_manager_add_vessels(imo, mmsi)
* (**PUT**) VesselFinderApi.list_manager_replace_all_vessels(imo, mmsi)
* (**DELETE**) VesselFinderApi.list_manager_delete_vessels(imo, mmsi)
<br>
*These parameters are **required**
## Error Handling
### Exceptions in the wrapper:
* `ApiErrorException`: this is the general Exception, and you can use it to catch all Exceptions from the wrapper
* `ApiInvalidArgumentsException`: this Exception is being thrown if user input is invalid
* `ApiRequestErrorException`: this Exception is being thrown when API request returns error
```python
from vesselfinder_api.exceptions import ApiErrorException, ApiInvalidArgumentsException, ApiRequestErrorException

try:
    print(v.vessels(imo=[imo1,imo2,imo3 ...], mmsi=some_mmsi))
except ApiInvalidArgumentsException as e:
    print("This block is for Invalid Arguments: {}".format(e))
except ApiRequestErrorException as e:
    print("This block is for Request Error: {}".format(e))
except ApiErrorException as e:
    print("General error: {}".format(e))
```
or you can just catch `ApiErrorException`
```python
from vesselfinder_api.exceptions import ApiErrorException

try:
    print(v.vessels(imo=[imo1,imo2,imo3 ...], mmsi=some_mmsi))
except ApiErrorException as e:
    print("General error: {}".format(e))
```
## Usage of methods
See `/examples` directory for full examples for the methods. By default, all methods are sending `GET` requests.
### Get last request info
This method will give you the last request `X-API` headers, which may contain how much Credits you have left, or how many IMOs you have added to your List Manager and etc.
```python
last_info = v.get_last_info()
```
### Status
```python
status = v.status()
```
### Vessels
```python
vessels = v.vessels(imo=[imo1, imo2, imo3 ...], mmsi=[mmsi1, mmsi2, mmsi3 ...])
```
### Vessels List
```python
vessels_list = v.vessels_list()
```
### Live Data
```python
liver_data = v.live_data()
```
### Port Calls
This method can be executed by one of the two following examples only
```python
port_calls = v.port_calls(interval=some_interval, imo=some_imo)
```
or
```python
port_calls = v.port_calls(interval=some_interval, locode=some_locode)
```
### Expected Arrivals
```python
expected_arrivals = v.expected_arrivals(locode=some_locode, interval=some_interval)
```
### Master Data
```python
master_data = v.master_data(imo=[imo1, imo2, imo3 ...])
```
### Distance
```python
distance = v.distance(_from=some_coordinate, _to=some_coordinate)
```
### List Manager
```python
liver_data = v.get_list_manager()
```
### Add vessels to List Manager
This method is sending `POST` request.

```python
v.list_manager_add_vessels(imo=[imo1, imo2], mmsi=[mmsi1, mmsi2])
```
### Replace all vessels in your List Manager with `imo` and/or `mmsi` you send
This method is sending `PUT` request.

```python
v.list_manager_replace_all_vessels(imo=[imo1, imo2], mmsi=[mmsi1, mmsi2])
```
### Delete vessels from your List Manager
This method is sending `DELETE` request.

```python
v.list_manager_delete_vessels(imo=[imo1, imo2], mmsi=[mmsi1, mmsi2])
```