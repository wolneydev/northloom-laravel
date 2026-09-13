<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Country
    |--------------------------------------------------------------------------
    |
    | Creation ideas are Brazil-first. Timezone still comes from config('app.timezone')
    | (America/Sao_Paulo); this value is only echoed in the API context payload.
    |
    */

    'country' => 'BR',

    /*
    |--------------------------------------------------------------------------
    | Day Periods
    |--------------------------------------------------------------------------
    |
    | Local-time windows used as a symbolic creative framework (not astrology).
    | Night wraps midnight: 18:00–04:59.
    |
    */

    'day_periods' => [
        'morning' => [
            'start' => '05:00',
            'end' => '11:59',
        ],
        'afternoon' => [
            'start' => '12:00',
            'end' => '17:59',
        ],
        'night' => [
            'start' => '18:00',
            'end' => '04:59',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Southern Hemisphere Seasons
    |--------------------------------------------------------------------------
    |
    | Month number (1–12) mapped to the Southern Hemisphere meteorological season.
    |
    */

    'southern_hemisphere_seasons' => [
        1 => 'summer',
        2 => 'summer',
        3 => 'autumn',
        4 => 'autumn',
        5 => 'autumn',
        6 => 'winter',
        7 => 'winter',
        8 => 'winter',
        9 => 'spring',
        10 => 'spring',
        11 => 'spring',
        12 => 'summer',
    ],

];
