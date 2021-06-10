from vesselfinder_api import VesselFinderApi
from vesselfinder_api.exceptions import ApiErrorException

v = VesselFinderApi(userkey='-- Input your userkey here --', errormode=False, save_last_info=True)

try:
    print(v.distance(_from="1.24703,51.94967", _to="28.68018,40.96205"))
except ApiErrorException as e:
    print(e)
