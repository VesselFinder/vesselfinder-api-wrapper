from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.vessels(imo=[9228801,9441271], mmsi=227441980))
except ApiErrorException as e:
    print(e)
