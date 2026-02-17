<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Model\Entity\NumberOfRatingPerValue;
use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class NoteCalculatorTest extends TestCase
{
    /**
     * Using a data provider to validate multiple scenarios
     * without duplicating the test logic.
     * 
     * @dataProvider provideVideoGame
     */
    public function testShouldCountRatingPerValue(
        VideoGame $videoGame,
        NumberOfRatingPerValue $expectedNumberOfRatingPerValue
    ): void {
        $ratingHandler = new RatingHandler(); // Pure unit test, no kernel needed.

        // Execute business logic
        $ratingHandler->countRatingsPerValue($videoGame);

        // Compare expected distribution with actual computed state.
        self::assertEquals(
            $expectedNumberOfRatingPerValue,
            $videoGame->getNumberOfRatingsPerValue()
        );
    }

    /**
     * Provides different scenarios to ensure
     * rating distribution is correctly computed.
     * @return iterable<string, array{0: \App\Model\Entity\VideoGame}>
     */
    public static function provideVideoGame(): iterable
    {
        yield 'Aucune review' => [
            new VideoGame(), // No reviews → all counters should remain at 0
            new NumberOfRatingPerValue(),
        ];

        yield 'Une review' => [
            self::createVideoGame(5),
            self::createExpectedState(five: 1),
        ];

        yield 'Plusieurs reviews' => [
            self::createVideoGame(1, 2, 2, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 5),
            self::createExpectedState(1, 2, 3, 4, 5),
        ];
    }

    /**
     * Helper method to create a VideoGame with reviews.
     * This keeps the test focused on behavior.
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

    /**
     * Helper method to build the expected rating distribution.
     */
    private static function createExpectedState(
        int $one = 0,
        int $two = 0,
        int $three = 0,
        int $four = 0,
        int $five = 0
    ): NumberOfRatingPerValue {
        $state = new NumberOfRatingPerValue();

        for ($i = 0; $i < $one; ++$i) {
            $state->increaseOne();
        }
        for ($i = 0; $i < $two; ++$i) {
            $state->increaseTwo();
        }
        for ($i = 0; $i < $three; ++$i) {
            $state->increaseThree();
        }
        for ($i = 0; $i < $four; ++$i) {
            $state->increaseFour();
        }
        for ($i = 0; $i < $five; ++$i) {
            $state->increaseFive();
        }

        return $state;
    }
}
