<?php declare(strict_types = 1);

namespace WhiteDigital\Audit\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function str_starts_with;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly ParameterBagInterface $bag,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        if (!$this->validate()) {
            $filteredPaths = new Model\Paths();
            foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
                if (str_starts_with($path, '/api/wd/as/')) {
                    continue;
                }

                $filteredPaths->addPath($path, $pathItem);
            }

            return $openApi->withPaths($filteredPaths);
        }

        return $openApi;
    }

    private function validate(): bool
    {
        if ($this->bag->has($key = 'whitedigital.audit.enabled') && true === $this->bag->get($key)) {
            return $this->bag->has($resourceKey = 'whitedigital.audit.enable_audit_resource') && true === $this->bag->get($resourceKey);
        }

        return false;
    }
}
