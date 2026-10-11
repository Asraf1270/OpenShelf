<?php

namespace Tests\Feature;

use App\Console\Commands\CalculateBoipokaWinner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoipokaLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_points_are_ranked_with_a_unique_tie_break_order(): void
    {
        $entries = [
            [
                'user' => new User(['name' => 'Zarin', 'session' => '2022-23']),
                'boipoka_points' => 120,
                'added_books' => 2,
                'lent_books' => 2,
                'on_time_returns' => 1,
                'review_count' => 3,
            ],
            [
                'user' => new User(['name' => 'Aisha', 'session' => '2021-22']),
                'boipoka_points' => 120,
                'added_books' => 2,
                'lent_books' => 2,
                'on_time_returns' => 1,
                'review_count' => 3,
            ],
            [
                'user' => new User(['name' => 'Nadia', 'session' => '2022-23']),
                'boipoka_points' => 120,
                'added_books' => 2,
                'lent_books' => 1,
                'on_time_returns' => 1,
                'review_count' => 3,
            ],
        ];

        $sorted = CalculateBoipokaWinner::sortLeaderboardEntries($entries);

        $this->assertSame(['Aisha', 'Zarin', 'Nadia'], array_map(fn ($entry) => $entry['user']->name, $sorted));
    }
}
