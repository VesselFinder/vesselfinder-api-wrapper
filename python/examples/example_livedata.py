from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.live_data(format='xml'))
    print(v.get_last_info())
except ApiErrorException as e:
    print(e)
