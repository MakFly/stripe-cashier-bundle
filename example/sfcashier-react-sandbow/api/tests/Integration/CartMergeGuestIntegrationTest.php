<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Controller\CartController;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Security\GuestCartCookieService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CartMergeGuestIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testMergeGuestCartIntoAuthenticatedUserCart(): void
    {
        $user = (new User())
            ->setEmail('integration.user@sfcashier.local')
            ->setName('Integration User')
            ->setPassword('not_used_for_this_test');

        $product = (new Product())
            ->setName('Integration Product')
            ->setSlug('integration-product')
            ->setDescription('Product used by integration test')
            ->setPrice(1999)
            ->setStock(10);

        $this->entityManager->persist($user);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        /** @var GuestCartCookieService $guestCookieService */
        $guestCookieService = self::getContainer()->get(GuestCartCookieService::class);

        $cookieResponse = new JsonResponse();
        $guestCookieService->attach(
            $cookieResponse,
            Request::create('http://localhost/api/v1/cart/guest', 'PUT'),
            [['productId' => (int) $product->getId(), 'quantity' => 2]],
        );

        $setCookies = $cookieResponse->headers->getCookies();
        self::assertNotEmpty($setCookies, 'Le cookie invité doit être présent.');
        $guestCookie = $setCookies[0];

        $session = new Session(new MockArraySessionStorage());
        $session->set('user_id', $user->getId());

        /** @var CartController $controller */
        $controller = self::getContainer()->get(CartController::class);
        $mergeRequest = Request::create(
            uri: 'http://localhost/api/v1/cart/merge-guest',
            method: 'POST',
            cookies: ['sfcashier_guest_cart' => $guestCookie->getValue()],
        );

        $decoded = $guestCookieService->decodeFromRequest($mergeRequest);
        self::assertFalse($decoded['invalid']);
        self::assertCount(1, $decoded['items']);
        self::assertSame((int) $product->getId(), $decoded['items'][0]['productId']);
        self::assertSame(2, $decoded['items'][0]['quantity']);

        $response = $controller->mergeGuest($mergeRequest, $session);
        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('/api/v1/cart', $payload['@id']);
        self::assertCount(1, $payload['items']);
        self::assertSame(2, $payload['items'][0]['quantity']);
        self::assertSame($product->getId(), $payload['items'][0]['product']['id']);

        /** @var CartRepository $cartRepository */
        $cartRepository = self::getContainer()->get(CartRepository::class);
        $userCart = $cartRepository->findActiveCartForUser($user);

        self::assertNotNull($userCart);
        self::assertCount(1, $userCart->getItems());
        self::assertSame(2, $userCart->getItems()->first()->getQuantity());

        $clearCookie = null;
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'sfcashier_guest_cart') {
                $clearCookie = $cookie;
                break;
            }
        }

        self::assertNotNull($clearCookie, 'Le cookie invité doit être supprimé après merge.');
        self::assertSame('', $clearCookie->getValue());
    }
}
