import requests
from vesselfinder_api.exceptions import ApiErrorException, ApiRequestErrorException, ApiInvalidArgumentsException
from datetime import datetime


class VesselFinderApi(object):
    _endpoint = 'https://api.vesselfinder.com'

    _error_format = "Invalid format "
    _error_mmsi = "Invalid MMSI "
    _error_imo = "Invalid IMO "
    _error_data_type = "Invalid ExtraData type "
    _error_port_call = "Invalid PortCall type "
    _error_input_time = "Invalid Date format "

    _last_info = {}

    def __init__(self, userkey, errormode=False, save_last_info=True):
        self.userkey = userkey
        self.errormode = errormode
        self.save_last_info = save_last_info

    def _parse_headers(self, response_headers):
        self._last_info = {}

        for k,v in response_headers.items():
            if 'X-API' in k:
                new_k = k.replace("X-API-", '').replace('-', '_')
                self._last_info[new_k.lower()] = v

    def _is_numeric(self, _string):
        try:
            a = float(_string)
        except ValueError:
            return False

        return True

    def _validate_date(self, date_text):
        try:
            datetime.strptime(date_text, '%Y-%m-%d %H:%M:%S')
        except ValueError:
            return False

        return True

    def _get_point(self, parameter, point):
        point_value = point.split(',')
        if len(point_value) != 2 or self._is_numeric(point_value[0]) is False or self._is_numeric(point_value[1]) is False:
            raise ApiInvalidArgumentsException('Invalid format of parameter "{}" coordinate'.format(parameter))

    def validate_params(self, params):
        params_keys = params.keys()

        if 'format' in params_keys:
            if params['format'] != 'xml' and params['format'] != 'json':
                raise ApiInvalidArgumentsException('{}"{}"'.format(self._error_format, params['format']))

        if 'interval' in params_keys:
            if self._is_numeric(params['interval']) is False:
                raise ApiInvalidArgumentsException('{}"interval={}"'.format(self._error_format, params['interval']))

        if 'imo' in params_keys:
            if type(params['imo']) != list:
                params['imo'] = [params['imo']]

            for imo in params['imo']:
                if type(imo) != int:
                    raise ApiInvalidArgumentsException('IMO "{}" is not an integer'.format(imo))
                elif imo < 1000000 or imo > 9999999:
                    raise ApiInvalidArgumentsException('{}"{}"'.format(self._error_imo, imo))

        if 'mmsi' in params_keys:
            if type(params['mmsi']) != list:
                params['mmsi'] = [params['mmsi']]

            for mmsi in params['mmsi']:
                if type(mmsi) != int:
                    raise ApiInvalidArgumentsException('MMSI "{}" is not an integer'.format(mmsi))
                elif mmsi < 200000000 or mmsi > 799999999:
                    raise ApiInvalidArgumentsException('{}"{}"'.format(self._error_mmsi, mmsi))

        if 'extradata' in params_keys:
            extradata_list = params['extradata'].split(',')
            for ed in extradata_list:
                if ed != 'ais' and ed != 'voyage' and ed != 'master':
                    raise ApiInvalidArgumentsException('{}"{}"'.format(self._error_data_type, ed))

        if 'event' in params_keys:
            port_call_event = params['event'].upper()
            if port_call_event != 'ARRIVAL' and port_call_event != 'DEPARTURE':
                raise ApiInvalidArgumentsException('{}"{}"'.format(self._error_port_call, params['event']))

        if 'fromdate' in params_keys:
            if self._validate_date(params['fromdate']) is False:
                raise ApiInvalidArgumentsException('{}"fromdate={}"'.format(self._error_input_time, params['fromdate']))

        if 'todate' in params_keys:
            if self._validate_date(params['todate']) is False:
                raise ApiInvalidArgumentsException('{}"todate={}"'.format(self._error_input_time, params['todate']))

        if 'from' in params_keys:
            self._get_point('_from', params['from'])

        if 'to' in params_keys:
            self._get_point('_to', params['to'])

    def _url(self, resource):
        return '{endpoint}/{resource}'.format(
            endpoint=self._endpoint,
            resource=resource
        )

    def _call(self, resource, method_type='get', custom_success_response='', **params):
        self._raw_result, self.status_code = None, None

        params['userkey'] = self.userkey
        if self.errormode:
            params['errormode'] = 409

        # Parameters that are dynamic for request
        params = {k: v for k, v in params.items() if v is not None}
        if resource == 'distance':
            params['from'] = params.pop('_from')
            params['to'] = params.pop('_to')

        self.validate_params(params)

        if 'imo' in params.keys():
            if type(params['imo']) == list:
                params['imo'] = ','.join(str(x) for x in params['imo'])

        if 'mmsi' in params.keys():
            if type(params['mmsi']) == list:
                params['mmsi'] = ','.join(str(x) for x in params['mmsi'])

        # Request is sent here
        if method_type == 'post':
            r = requests.post(self._url(resource), data=params)
        elif method_type == 'put':
            r = requests.put(self._url(resource), data=params)
        elif method_type == 'delete':
            r = requests.delete(self._url(resource), params=params)
        else:
            r = requests.get(self._url(resource), params=params)

        self.status_code = r.status_code
        self._raw_result = r.text
        self._parse_headers(r.headers)

        # Simple request error handling
        if self.status_code == 409:
            raise ApiRequestErrorException(r.text)
        else:
            if len(self._last_info) > 0 and 'error' in self._last_info.keys():
                raise ApiRequestErrorException(self._last_info['error'])

        if len(self._raw_result) == 0 and len(custom_success_response) != 0:
            return {'success': custom_success_response}
        elif len(self._raw_result) == 0:
            return {'success': ''}

        if 'format' in params.keys() and params['format'] == 'xml':
            return self._raw_result
        else:
            return r.json()

    def get_last_info(self):
        if not self.save_last_info:
            raise ApiErrorException("To use this function you should set 'save_last_info' to True when initializing your VesselFinderApi class instance.")

        return self._last_info

    def status(self, format=None, **params):
        return self._call('status', format=format, **params)

    def vessels(self, imo, mmsi, format=None, extradata=None, sat=None, interval=None, **params):
        return self._call('vessels', imo=imo, mmsi=mmsi, format=format, extradata=extradata, sat=sat, interval=interval, **params)

    def vessels_list(self, format=None, interval=None, **params):
        return self._call('vesselslist', format=format, interval=interval, **params)

    def live_data(self, format=None, interval=None, **params):
        return self._call('livedata', format=format, interval=interval, **params)

    def port_calls(self, interval, format=None, imo=None, mmsi=None, locode=None, extradata=None, limit=None, event=None, fromdate=None, todate=None, **params):
        if imo is None and mmsi is None and locode is None:
            raise ApiInvalidArgumentsException("At least one IMO number or MMSI number or Port LOCODE is required. Any combination between several IMO and MMSI numbers is possible but combination between IMO/MMSI numbers and Port LOCODE is not allowed.")
        elif imo is not None and mmsi is not None and locode is not None:
            raise ApiInvalidArgumentsException("Combination between IMO/MMSI numbers and Port LOCODE is not allowed.")
        else:
            return self._call('portcalls', interval=interval, format=format, imo=imo, mmsi=mmsi, locode=locode, extradata=extradata, limit=limit, event=event, fromdate=fromdate, todate=todate, **params)

    def expected_arrivals(self, locode, format=None, interval=None, fromdate=None, todate=None, extradata=None,
                          limit=None, **params):
        if interval is None and fromdate is None and todate is None:
            raise ApiInvalidArgumentsException("The request should contain timespan specified by interval or fromdate / todate parameters!")
        else:
            return self._call('expectedarrivals', locode=locode, format=format, interval=interval, fromdate=fromdate, todate=todate, extradata=extradata, limit=limit, **params)

    def master_data(self, imo, format=None, **params):
        return self._call('masterdata', imo=imo, format=format, **params)

    def distance(self, _from, _to, gateways=None, eca=None, epsg3857=None, **params):
        return self._call('distance', _from=_from, _to=_to, gateways=gateways, ECA=eca, EPSG3857=epsg3857, **params)

    def get_list_manager(self, **params):
        return self._call('listmanager', **params)

    def list_manager_add_vessels(self, imo=None, mmsi=None, **params):
        if imo is None and mmsi is None:
            raise ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!")

        return self._call('listmanager', 'post', custom_success_response="Successfully added to your ListManager.", imo=imo, mmsi=mmsi, **params)

    def list_manager_replace_all_vessels(self, imo=None, mmsi=None, **params):
        if imo is None and mmsi is None:
            raise ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!")

        return self._call('listmanager', 'put', custom_success_response="Successfully replaced your ListManager.", imo=imo, mmsi=mmsi, **params)

    def list_manager_delete_vessels(self, imo=None, mmsi=None, **params):
        if imo is None and mmsi is None:
            raise ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!")

        return self._call('listmanager', 'delete', custom_success_response="Successfully deleted from your ListManager.", imo=imo, mmsi=mmsi, **params)

