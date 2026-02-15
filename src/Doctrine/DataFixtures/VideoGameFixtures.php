<?php

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

use function array_fill_callback;

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
        // on récupère tous les tags
        $tags = $manager->getRepository(Tag::class)->findAll();
        
        $users = array_chunk(
            $manager->getRepository(User::class)->findAll(),
            5
        );
        
        /** @var string $fakeText */
        $fakeText = $this->faker->paragraphs(5, true);

        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($fakeText)
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );



        array_walk($videoGames, static function (VideoGame $videoGame, int $index) use ($tags) {
            // on choisit un nombre aléatoire entre 2 et 5 pour le nombre de tags d'un jeu
            $numberOfTags = random_int(2, 5);

            // on mélange pour ne pas avoir les mêmes
            shuffle($tags);

            for ($i = 0; $i < $numberOfTags; $i++) {
                $videoGame->getTags()->add($tags[$i]);
            }
        });

        // on ajoute les tags aux jeux
        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        array_walk($videoGames, function (VideoGame $videoGame, int $index) use ($users, $manager) {
            $filteredUsers = $users[$index % count($users)];

            foreach ($filteredUsers as $i => $user) {
                /** @var string $comment */
                $comment = $this->faker->paragraphs(1, true);

                $review = (new Review())
                    ->setUser($user)
                    ->setVideoGame($videoGame)
                    ->setRating($this->faker->numberBetween(1, 5))
                    ->setComment($comment)
                ;

                $videoGame->getReviews()->add($review);

                $manager->persist($review);

                $this->calculateAverageRating->calculateAverage($videoGame);
                $this->countRatingsPerValue->countRatingsPerValue($videoGame);
            }
        });

    }

    public function getDependencies(): array
    {
        return [TagFixtures::class, UserFixtures::class];
    }
}
