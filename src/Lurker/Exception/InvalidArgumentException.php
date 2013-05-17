<?php

namespace Lurker\Exception;

use \InvalidArgumentException as BaseInvalidArgumentException;

/**
 * InvalidArgumentException
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
}
