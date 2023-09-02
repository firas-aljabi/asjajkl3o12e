<?php

namespace App\Statuses;

class ReservationEvent

{
    public const BRIDE = 1;
    public const EVENT_MAKEUP = 2;
    public const HAIR_STYLE = 3;
    public const INSTALL_EYELASHES = 4;
    public const CURL_EYELASHES = 5;
    public const RAISE_EYEBROWS = 6;
    public const NAIL_INSTALL = 7;
    public const MASSAGE = 8;
    public const SKIN_CLEANING = 9;
    public const HAIR_EXTENSIONS = 10;

    public static array $statuses = [
        self::BRIDE, self::EVENT_MAKEUP, self::HAIR_STYLE,
        self::INSTALL_EYELASHES, self::CURL_EYELASHES, self::RAISE_EYEBROWS,
        self::NAIL_INSTALL, self::MASSAGE, self::SKIN_CLEANING, self::HAIR_EXTENSIONS
    ];
}
