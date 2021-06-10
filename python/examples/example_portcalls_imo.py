from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.port_calls(imo=9175717, interval=10080))
except ApiErrorException as e:
    print(e)
