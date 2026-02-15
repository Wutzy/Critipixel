<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class AverageRatingCalculatorTest extends TestCase
{
    /**
     * Validate that the average rating is correctly computed
     * across multiple scenarios.
     *
     * @dataProvider provideVideoGame
     */
    public function testShouldCalculateAverageRating(
        VideoGame $videoGame,
        ?int $expectedAverageRating
    ): void {
        $ratingHandler = new RatingHandler(); // Pure unit test

        $ratingHandler->calculateAverage($videoGame); // Execute calculation

        // Strict comparison ensures correct handling of null vs int
        self::assertSame(
            $expectedAverageRating,
            $videoGame->getAverageRating()
        );
    }

    /**
     * Provides test cases to validate average computation
     * 
     */
    public static function provideVideoGame(): iterable
    {
        yield 'Aucune review' => [
            new VideoGame(), // No reviews → average should be null
            null,
        ];

        yield 'Une review' => [
            self::createVideoGame(5),
            5,
        ];

        yield 'Plusieurs reviews' => [
            self::createVideoGame(1, 2, 2, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 5),
            4, // Expected rounded average
        ];
    }

    /**
     * Helper method to create a VideoGame with reviews
     */
    private static function createVideoGame(int ...$ratings): VideoGame
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add(
                (new Review())->setRating($rating)
            );
        }

        return $videoGame;
    }
}
