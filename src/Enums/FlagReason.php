<?php

namespace Clearlyip\LaravelFlipt\Enums;

enum FlagReason: string
{
    case UNKNOWN = 'UNKNOWN';
    case DISABLED = 'DISABLED';
    case TARGETING_MATCH = 'TARGETING_MATCH';
    case DEFAULT = 'DEFAULT';
}
