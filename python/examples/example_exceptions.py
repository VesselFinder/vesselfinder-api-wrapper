from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException, ApiInvalidArgumentsException, ApiRequestErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.vessels(imo=[9228801,9441271], mmsi=227441980))
except ApiInvalidArgumentsException as e:
    print("This block is for Invalid Arguments: {}".format(e))
except ApiRequestErrorException as e:
    print("This block is for Request Error: {}".format(e))
except ApiErrorException as e:
    print("General case: {}".format(e))
