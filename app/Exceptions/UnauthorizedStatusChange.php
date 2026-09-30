<?php

namespace App\Exceptions;

use LogicException;

class UnauthorizedStatusChange extends LogicException
{
    public static function for(string $model): self
    {
        return new self("The status of an existing {$model} can only change through moderation.");
    }
}
