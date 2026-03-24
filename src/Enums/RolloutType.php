<?php

namespace Clearlyip\LaravelFlipt\Enums;

enum RolloutType: string
{
    case UNKNOWN_ROLLOUT_TYPE = 'UNKNOWN_ROLLOUT_TYPE';
    case SEGMENT_ROLLOUT_TYPE = 'SEGMENT_ROLLOUT_TYPE';
    case THRESHOLD_ROLLOUT_TYPE = 'THRESHOLD_ROLLOUT_TYPE';
}
