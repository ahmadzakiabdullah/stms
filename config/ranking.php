<?php

return [
    'default' => 'points',

    'strategies' => [
        'points' => [
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'tiebreakers' => ['points', 'goal_difference', 'score_for'],
        ],
        'win_rate' => [
            'tiebreakers' => ['win_rate', 'wins', 'goal_difference'],
        ],
        'medal_tally' => [
            'tiebreakers' => ['gold', 'silver', 'bronze'],
        ],
    ],
];
