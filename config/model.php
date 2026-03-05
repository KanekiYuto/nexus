<?php

use App\AIModels\bytedance\seedream\v4_5\text_to_image\WaveSpeed;
use App\Constants\ProviderConst;

return [
    'bytedance/seedream/v4.5/text-to-image' => [
        ProviderConst::FAL => 'bytedance/seedream-v4.5/text-to-image',
        ProviderConst::WAVE_SPEED => WaveSpeed::class,
    ],
    'bytedance/seedream/v4.5/edit' => [
        ProviderConst::FAL => 'bytedance/seedream/v4.5/edit',
        ProviderConst::WAVE_SPEED => 'bytedance/seedream-v4.5/edit',
    ],
];
