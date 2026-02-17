<?php

declare(strict_types=1);

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // On crée 25 tags de manière déterministe (pas de callbacks difficiles à typer)
        for ($i = 1; $i <= 25; $i++) {
            $tag = (new Tag())
                ->setName(sprintf('Tag %d', $i));

            $manager->persist($tag);
        }

        $manager->flush();
    }
}
