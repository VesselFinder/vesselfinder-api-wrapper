class ApiErrorException(Exception):
    pass


class ApiRequestErrorException(ApiErrorException):
    pass


class ApiInvalidArgumentsException(ApiErrorException):
    pass
