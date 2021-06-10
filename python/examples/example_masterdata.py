from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.master_data(imo=[9466233, 9723708, 9328649]))
except ApiErrorException as e:
    print(e)
