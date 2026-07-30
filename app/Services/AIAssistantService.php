<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Fixture;

class AIAssistantService
{
    /**
     * Simulate an AI optimization of a match schedule.
     * In a real world, this would use a complex constraint solver or external API.
     */
    public function optimizeSchedule(Event $event): array
    {
        $matches = Fixture::where('event_id', $event->id)->get();

        $optimizedCount = 0;

        foreach ($matches as $match) {
            // Simulated AI heuristic: if match has no venue, assign randomly from available.
            // Adjust scheduled_at by random minutes to avoid clash (simulated).
            if (! $match->venue) {
                $match->venue = 'Court '.rand(1, 5);
                $optimizedCount++;
            }

            if (! $match->scheduled_at) {
                // set to a random time between 8am and 6pm on a random day in the future
                $days = rand(1, 7);
                $hours = rand(8, 17);
                $match->scheduled_at = now()->addDays($days)->setTime($hours, 0);
                $optimizedCount++;
            }

            $match->save();
        }

        return [
            'success' => true,
            'message' => "AI Schedule Optimization complete. Adjusted {$optimizedCount} match parameters.",
            'optimized_count' => $optimizedCount,
        ];
    }

    /**
     * Simulate an AI prediction of a match winner based on simplistic historical data (win rate).
     */
    public function predictWinner(Fixture $match): string
    {
        // Simple heuristic for simulation purposes.
        $comp1 = $match->competitor1;
        $comp2 = $match->competitor2;

        if (! $comp1 || ! $comp2) {
            return 'Not enough data to predict.';
        }

        // Random prediction for simulation, biased towards competitor 1 just to show output
        $confidence = rand(51, 99);
        $winnerName = rand(0, 1) ? $comp1->participant->name : $comp2->participant->name;

        return "AI Prediction: {$winnerName} will win (Confidence: {$confidence}%)";
    }
}
