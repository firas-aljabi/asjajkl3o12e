<?php

namespace App\Statuses;

class PermissionType
{
    public const UPDATE = 1;
    public const CANCLE = 2;

    public static array $statuses = [self::UPDATE, self::CANCLE];
}
