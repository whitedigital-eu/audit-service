<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\Tests\Source;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function json_decode;
use function sprintf;

class AuditApiTest extends ApiTestCase
{
    protected HttpClientInterface $client;
    protected ContainerInterface $container;

    protected string $iri = '/api/wd/as/audits';

    /**
     * @throws TransportExceptionInterface
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @depends testGetCollection
     */
    public function testGetItem(array $audits): void
    {
        $this->client->request(Request::METHOD_GET, sprintf('%s/%d', $this->iri, $id = $audits[0]['id']));

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['@id' => sprintf('%s/%d', $this->iri, $id)]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @Depends \App\Tests\PasswordApiTest::testResetRequestSetSuccess
     */
    public function testGetCollection(): array
    {
        $response = $this->client->request(Request::METHOD_GET, $this->iri);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['@id' => $this->iri]);

        return json_decode($response->getContent(), true)['hydra:member'] ?? [];
    }
}
