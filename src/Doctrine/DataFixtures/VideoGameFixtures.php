<?php

declare(strict_types=1);

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Tag[] $tags */
        $tags = $manager->getRepository(Tag::class)->findAll();
        /** @var User[] $allUsers */
        $allUsers = $manager->getRepository(User::class)->findAll();

        if ($tags === [] || $allUsers === []) {
            // Fixtures dépendantes manquantes => on évite une erreur (modulo 0)
            return;
        }

        /** @var list<list<User>> $usersChunks */
        $usersChunks = array_chunk($allUsers, 5);

        /** @var string $fakeText */
        $fakeText = $this->faker->paragraphs(5, true);

        /** @var VideoGame[] $videoGames */
        $videoGames = [];

        // Création des jeux
        for ($index = 0; $index < 50; $index++) {
            $videoGame = (new VideoGame())
                ->setTitle(sprintf('Jeu vidéo %d', $index))
                ->setDescription($this->faker->paragraphs(10, true))
                ->setReleaseDate(new DateTimeImmutable())
                ->setTest($fakeText)
                ->setRating(($index % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $index))
                ->setImageSize(2_098_872);

            // Ajout déterministe de 5 tags
            $tagsCount = count($tags);
            for ($tagIndex = 0; $tagIndex < 5; $tagIndex++) {
                $videoGame->getTags()->add($tags[($index + $tagIndex) % $tagsCount]);
            }

            $manager->persist($videoGame);
            $videoGames[] = $videoGame;
        }

        $manager->flush();

        // Création des reviews
        $chunksCount = count($usersChunks);

        foreach ($videoGames as $index => $videoGame) {
            $filteredUsers = $usersChunks[$index % $chunksCount];

            foreach ($filteredUsers as $user) {
                /** @var string $comment */
                $comment = $this->faker->paragraphs(1, true);

                $review = (new Review())
                    ->setUser($user)
                    ->setVideoGame($videoGame)
                    ->setRating($this->faker->numberBetween(1, 5))
                    ->setComment($comment);

                $videoGame->getReviews()->add($review);
                $manager->persist($review);

                $this->calculateAverageRating->calculateAverage($videoGame);
                $this->countRatingsPerValue->countRatingsPerValue($videoGame);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TagFixtures::class, UserFixtures::class];
    }
}
