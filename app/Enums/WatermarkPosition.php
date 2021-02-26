<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class WatermarkPosition extends Enum
{
    const top_left      = 'top_left';
    const top_right     = 'top_right';
    const bottom_left   = 'bottom_left';
    const bottom_right  = 'bottom_right';
    const center        = 'center';
}
