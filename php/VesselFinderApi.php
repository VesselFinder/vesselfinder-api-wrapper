<?php
namespace API;

include __DIR__ . '/exceptions.php';

use DateTime;
use Exceptions\ApiErrorException;
use Exceptions\ApiInvalidArgumentsException;
use Exceptions\ApiRequestErrorException;

class VesselFinderApi
{
    public $userKey;
    public $errorMode;
    public $saveLastInfo;

    private $endpoint = 'https://api.vesselfinder.com';
    private $rawResult = null;
    private $statusCode = null;
    private $lastInfo = [];

    const ERROR_FORMAT = 'Invalid format ';
    const ERROR_MMSI = 'Invalid MMSI ';
    const ERROR_IMO = 'Invalid IMO ';
    const ERROR_PORT_CALL = 'Invalid PortCall type ';
    const ERROR_EVENT = 'Invalid Event type ';
    const ERROR_INPUT_TIME = 'Invalid Date format ';
    const ERROR_DATA_TYPE = 'Invalid ExtraData type ';

    public function __construct($userkey, $errormode = false, $save_last_info = true)
    {
        $this->userKey = $userkey;
        $this->errorMode = $errormode;
        $this->saveLastInfo = $save_last_info;
    }

    private function validateDate($date)
    {
        $d = DateTime::createFromFormat("Y-m-d H:i:s", $date);
        return $d && $d->format("Y-m-d H:i:s") === $date;
    }

    private function getPoint($parameter, $point)
    {
        $point_value = explode(',', $point);
        if (count($point_value) != 2 || !is_numeric($point_value[0]) || !is_numeric($point_value[1])) {
            throw new ApiInvalidArgumentsException(sprintf('Invalid format of parameter "%s" coordinate', $parameter));
        }
    }

    private function parseResponse($ch, $response)
    {
        if (curl_errno($ch)) {
            throw new ApiErrorException(sprintf("Error: %s", curl_error($ch)));
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->rawResult = substr($response, $headerSize);
        $this->lastInfo = $this->getHeaders($header);

        curl_close($ch);
    }

    private function validateParams($params)
    {
        if (array_key_exists('format', $params)) {
            if ($params['format'] != 'xml' && $params['format'] != 'json') {
                throw new ApiInvalidArgumentsException(sprintf('%s"%s"', self::ERROR_FORMAT, $params['format']));
            }
        }

        if (array_key_exists('interval', $params)) {
            if (!is_numeric($params['interval'])) {
                throw new ApiInvalidArgumentsException(sprintf('%s"interval=%s"', self::ERROR_FORMAT, $params['interval']));
            }
        }

        if (array_key_exists('imo', $params)) {
            if (!is_array($params['imo'])) $params['imo'] = [$params['imo']];

            foreach ($params['imo'] as $imo) {
                if (!is_integer($imo)) {
                    throw new ApiInvalidArgumentsException(sprintf('IMO "%s" is not an integer', $imo));
                } else if ($imo < 1000000 || $imo > 9999999) {
                    throw new ApiInvalidArgumentsException(sprintf('%s"%s"', self::ERROR_IMO, $imo));
                }
            }
        }

        if (array_key_exists('mmsi', $params)) {
            if (!is_array($params['mmsi'])) $params['mmsi'] = [$params['mmsi']];

            foreach ($params['mmsi'] as $mmsi) {
                if (!is_integer($mmsi)) {
                    throw new ApiInvalidArgumentsException(sprintf('MMSI "%s" is not an integer', $mmsi));
                } else if ($mmsi < 200000000 || $mmsi > 799999999) {
                    throw new ApiInvalidArgumentsException(sprintf('%s"%s"', self::ERROR_MMSI, $mmsi));
                }
            }
        }

        if (array_key_exists('extradata', $params)) {
            $extradata_list = explode(',', $params['extradata']);
            foreach ($extradata_list as $item) {
                if ($item != 'ais' && $item != 'voyage' && $item != 'master') {
                    throw new ApiInvalidArgumentsException(sprintf('%s"%s"', self::ERROR_DATA_TYPE, $item));
                }
            }
        }

        if (array_key_exists('event', $params)) {
            $port_call_event = strtoupper($params['event']);
            if ($port_call_event != 'ARRIVAL' && $port_call_event != 'DEPARTURE') {
                throw new ApiInvalidArgumentsException(sprintf('%s"%s"', self::ERROR_PORT_CALL, $params['event']));
            }
        }

        if (array_key_exists('fromdate', $params)) {
            if (!$this->validateDate($params['fromdate'])) {
                throw new ApiInvalidArgumentsException(sprintf('%s"fromdate=%s"', self::ERROR_INPUT_TIME, $params['fromdate']));
            }
        }

        if (array_key_exists('tomdate', $params)) {
            if (!$this->validateDate($params['todate'])) {
                throw new ApiInvalidArgumentsException(sprintf('%s"todate=%s"', self::ERROR_INPUT_TIME, $params['todate']));
            }
        }

        if (array_key_exists('from', $params)) {
            $this->getPoint('from', $params['from']);
        }

        if (array_key_exists('to', $params)) {
            $this->getPoint('to', $params['to']);
        }
    }

    private function _getMethod($resource, $params)
    {
        $request_params = http_build_query($params);

        $ch = curl_init($this->getUrl($resource) . "?" . $request_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Connection: Close"
        ]);
        $response = curl_exec($ch);

        $this->parseResponse($ch, $response);
    }

    private function _postMethod($resource, $params)
    {
        $request_params = http_build_query($params);

        $ch = curl_init($this->getUrl($resource));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        $this->parseResponse($ch, $response);
    }

    private function _putMethod($resource, $params)
    {
        $request_params = http_build_query($params);

        $ch = curl_init($this->getUrl($resource));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        $this->parseResponse($ch, $response);
    }

    private function _deleteMethod($resource, $params)
    {
        $request_params = http_build_query($params);

        $ch = curl_init($this->getUrl($resource) . "?" . $request_params);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        $this->parseResponse($ch, $response);
    }

    private function getUrl($resource)
    {
        return sprintf("%s/%s", $this->endpoint, $resource);
    }

    private function getHeaders($raw_headers)
    {
        if (preg_match_all("/X-API-([^:]+):\s*([^\r\n]*)\r\n/", $raw_headers, $matches) !== false) {
            return array_combine(array_map(function ($val) {
                return str_replace('-', '_', strtolower($val));
            }, $matches[1]), $matches[2]);
        }

        return [];
    }

    private function makeRequest($resource, $params, $method_type = 'get', $custom_access_response = '')
    {
        $this->rawResult = null;
        $this->statusCode = null;

        $params['userkey'] = $this->userKey;
        if ($this->errorMode) $params['errormode'] = 409;

        foreach ($params as $key => $value) {
            if (is_null($value)) unset($params[$key]);
        }

        $this->validateParams($params);

        if (array_key_exists('imo', $params)) {
            if (is_array($params['imo'])) $params['imo'] = implode(',', $params['imo']);
        }

        if (array_key_exists('mmsi', $params)) {
            if (is_array($params['mmsi'])) $params['mmsi'] = implode(',', $params['mmsi']);
        }

        if ($method_type == 'post') {
            $this->_postMethod($resource, $params);
        } else if ($method_type == 'put') {
            $this->_putMethod($resource, $params);
        } else if ($method_type == 'delete') {
            $this->_deleteMethod($resource, $params);
        } else {
            $this->_getMethod($resource, $params);
        }

        if ($this->statusCode == 409) {
            throw new ApiRequestErrorException($this->rawResult);
        } else {
            if (array_key_exists('error', $this->lastInfo)) {
                throw new ApiRequestErrorException($this->lastInfo['error']);
            }
        }

        if (strlen($this->rawResult) == 0 && strlen($custom_access_response) != 0) {
            return ['success' => $custom_access_response];
        } else if (strlen($this->rawResult) == 0) {
            return ['success' => ''];
        }

        if (array_key_exists('format', $params) && $params['format'] == 'xml') {
            return $this->rawResult;
        } else {
            return json_decode($this->rawResult, true);
        }
    }

    public function getLastInfo()
    {
        if (!$this->saveLastInfo) {
            throw new ApiErrorException("To use this function you should set 'save_last_info' to True when initializing your VesselFinderApi class instance.");
        }

        return $this->lastInfo;
    }

    public function status($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'format' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('status', $filtered_params);
    }

    public function vessels($imo, $mmsi, $params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'imo' => $imo,
            'mmsi' => $mmsi,
            'format' => null,
            'extradata' => null,
            'sat' => null,
            'interval' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('vessels', $filtered_params);
    }

    public function vesselsList($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'format' => null,
            'interval' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('vesselslist', $filtered_params);
    }

    public function liveData($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'format' => null,
            'interval' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('livedata', $filtered_params);
    }

    public function portCalls($interval, $params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'interval' => $interval,
            'format' => null,
            'imo' => null,
            'mmsi' => null,
            'locode' => null,
            'extradata' => null,
            'limit' => null,
            'event' => null,
            'fromdate' => null,
            'todate' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        if (is_null($filtered_params['imo']) && is_null($filtered_params['mmsi']) && is_null($filtered_params['locode'])) {
            throw new ApiInvalidArgumentsException("At least one IMO number or MMSI number or Port LOCODE is required. Any combination between several IMO and MMSI numbers is possible but combination between IMO/MMSI numbers and Port LOCODE is not allowed.");
        } else if (!is_null($filtered_params['imo']) && !is_null($filtered_params['mmsi']) && !is_null($filtered_params['locode'])) {
            throw new ApiInvalidArgumentsException("Combination between IMO/MMSI numbers and Port LOCODE is not allowed.");
        }

        return $this->makeRequest('portcalls', $filtered_params);
    }

    public function expectedArrivals($locode, $params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'locode' => $locode,
            'format' => null,
            'interval' => null,
            'fromdate' => null,
            'todate' => null,
            'extradata' => null,
            'limit' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        if (is_null($filtered_params['interval']) && is_null($filtered_params['fromdate']) && is_null($filtered_params['todate'])) {
            throw new ApiInvalidArgumentsException("The request should contain timespan specified by interval or fromdate / todate parameters!");
        }

        return $this->makeRequest('expectedarrivals', $filtered_params);
    }

    public function masterData($imo, $params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'imo' => $imo,
            'format' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('masterdata', $filtered_params);
    }

    public function distance($from, $to, $params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'from' => $from,
            'to' => $to,
            'gateways' => null,
            'eca' => null,
            'epsg3857' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        return $this->makeRequest('distance', $filtered_params);
    }

    public function getListMManager()
    {
        return $this->makeRequest('listmanager', []);
    }

    public function listManagerAddVessels($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'imo' => null,
            'mmsi' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        if (is_null($filtered_params['imo']) && is_null($filtered_params['mmsi'])) {
            throw new ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!");
        }

        return $this->makeRequest('listmanager', $filtered_params, 'post', 'Successfully added to your ListManager!');
    }

    public function listManagerReplaceAllVessels($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'imo' => null,
            'mmsi' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        if (is_null($filtered_params['imo']) && is_null($filtered_params['mmsi'])) {
            throw new ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!");
        }

        return $this->makeRequest('listmanager', $filtered_params, 'put', 'Successfully replaced your ListManager!');
    }

    public function listManagerDeleteVessels($params = [])
    {
        if (!is_array($params)) {
            throw new ApiInvalidArgumentsException("The 'params' parameter should be array.");
        }

        $filtered_params = [
            'imo' => null,
            'mmsi' => null
        ];

        $filtered_params = array_merge($filtered_params, array_intersect_key(array_change_key_case($params, CASE_LOWER), $filtered_params));

        if (is_null($filtered_params['imo']) && is_null($filtered_params['mmsi'])) {
            throw new ApiInvalidArgumentsException("Vessels may be specified by list of IMO or MMSI numbers or both!");
        }

        return $this->makeRequest('listmanager', $filtered_params, 'delete', 'Successfully deleted from your ListManager!');
    }
}