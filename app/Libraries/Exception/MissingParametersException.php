<?php

namespace App\Libraries\Exception;

/**
 * Exception thrown when a HolidayApi cannot be generated because of missing mandatory parameters.
 *
 * @author anan
 */
class MissingParametersException extends \InvalidArgumentException implements ExceptionInterface
{
}
