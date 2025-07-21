<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Service;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Dbp\Relay\CoreConnectorOidcBundle\Authenticator\BearerUserProvider;
use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDError;
use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDProvider;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @internal
 */
readonly class HealthCheck implements CheckInterface
{
    public function __construct(
        private OIDProvider $oidcProvider,
        private BearerUserProvider $userProvider)
    {
    }

    public function getName(): string
    {
        return 'auth';
    }

    private function checkMethod(string $description, callable $func): CheckResult
    {
        $result = new CheckResult($description);
        try {
            $func();
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);

            return $result;
        }
        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    /**
     * @throws OIDError
     */
    public function checkConfig(): void
    {
        $this->oidcProvider->getProviderConfig();
    }

    /**
     * @throws OIDError
     */
    public function checkPublicKey(): void
    {
        $this->oidcProvider->getJWKs();
    }

    /**
     * @throws OIDError
     */
    public function checkRemoteValidation(): void
    {
        if (!$this->userProvider->usesRemoteValidation()) {
            // Not configured, so don't test
            return;
        }

        // Create a dummy token, and introspect it
        $accessToken = $this->oidcProvider->createToken();
        $token = $this->oidcProvider->introspectToken($accessToken);
        if ($token['active'] !== true) {
            throw new \RuntimeException('invalid token');
        }
    }

    /**
     * @throws GuzzleException
     */
    public function checkTimeSync(): void
    {
        $providerTime = $this->oidcProvider->getProviderDateTime();
        $systemTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $leeway = $this->userProvider->getValidationLeewaySeconds();
        $difference = abs($providerTime->getTimestamp() - $systemTime->getTimestamp());
        if ($difference > $leeway) {
            throw new \RuntimeException("The system time and the OIDC server time is out of sync ($difference > $leeway seconds)");
        }
    }

    public function check(CheckOptions $options): array
    {
        $results = [];
        $results[] = $this->checkMethod('Check if the OIDC config can be fetched', [$this, 'checkConfig']);
        $results[] = $this->checkMethod('Check if the OIDC public key can be fetched', [$this, 'checkPublicKey']);
        $results[] = $this->checkMethod('Check if the OIDC server time is in sync', [$this, 'checkTimeSync']);
        $results[] = $this->checkMethod('Check if remote validation works (if enabled)', [$this, 'checkRemoteValidation']);

        return $results;
    }
}
