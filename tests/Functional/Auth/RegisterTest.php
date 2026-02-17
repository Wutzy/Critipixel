<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Model\Entity\User;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterTest extends FunctionalTestCase
{
    public function testThatRegistrationShouldSucceeded(): void
    {
        $this->get('/auth/register');

        $formData = self::getFormData();
        $this->client->submitForm('S\'inscrire', $formData);

        self::assertResponseRedirects('/auth/login');

        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneByEmail($formData['register[email]']);

        $userPasswordHasher = $this->service(UserPasswordHasherInterface::class);

        self::assertNotNull($user);
        self::assertSame($formData['register[username]'], $user->getUsername());
        self::assertSame($formData['register[email]'], $user->getEmail());
        self::assertTrue($userPasswordHasher->isPasswordValid($user, $formData['register[plainPassword]']));
    }

    /**
     * @param array<string, string> $formData
     * @dataProvider provideInvalidFormData
     */
    public function testThatRegistrationShouldFailed(array $formData): void
    {
        $this->get('/auth/register');

        $this->client->submitForm('S\'inscrire', $formData);

        self::assertResponseIsUnprocessable();
    }

    /**
     * @return iterable<string, array{0: array<string, string>}>
     */
    public static function provideInvalidFormData(): iterable
    {
        yield 'empty username' => [self::getFormData(['register[username]' => ''])];
        yield 'non unique username' => [self::getFormData(['register[username]' => 'user+1'])];
        yield 'too long username' => [self::getFormData(['register[username]' => 'Lorem ipsum dolor sit amet orci aliquam'])];
        yield 'empty email' => [self::getFormData(['register[email]' => ''])];
        yield 'non unique email' => [self::getFormData(['register[email]' => 'user+1@email.com'])];
        yield 'invalid email' => [self::getFormData(['register[email]' => 'fail'])];
    }

    /**
     * @param array<string, string> $overrideData
     * @return array<string, string>
     */
    public static function getFormData(array $overrideData = []): array
    {
        $suffix = bin2hex(random_bytes(4));

        return array_merge([
            'register[username]' => 'username_'.$suffix,
            'register[email]' => 'user_'.$suffix.'@email.com',
            'register[plainPassword]' => 'SuperPassword123!',
        ], $overrideData);
    }
}
