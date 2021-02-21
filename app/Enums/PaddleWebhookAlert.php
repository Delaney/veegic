<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaddleWebhookAlert extends Enum
{
    const created           = 'subscription_created';
    const payment_success   = 'subscription_payment_succeeded';
    const cancelled         = 'subscription_cancelled';
}
