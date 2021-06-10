<?php

namespace Exceptions;

class ApiErrorException extends \Exception
{

}

class ApiRequestErrorException extends ApiErrorException
{

}

class ApiInvalidArgumentsException extends ApiErrorException
{

}