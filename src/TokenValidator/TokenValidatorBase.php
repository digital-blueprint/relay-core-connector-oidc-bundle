<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\TokenValidator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @internal
 */
abstract class TokenValidatorBase implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Validates the token and returns the parsed token.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    abstract public function validate(string $accessToken): array;

    /**
     * Verifies that the token was created for the given audience.
     * If not, it throws a TokenValidationException.
     *
     * @param array  $jwt      The access token
     * @param string $audience The audience string
     *
     * @throws TokenValidationException
     */
    public static function checkAudience(array $jwt, string $audience): void
    {
        $value = $jwt['aud'] ?? [];

        if (\is_string($value)) {
            if ($value !== $audience) {
                throw new TokenValidationException('Bad audience');
            }
        } elseif (\is_array($value)) {
            if (!\in_array($audience, $value, true)) {
                throw new TokenValidationException('Bad audience');
            }
        } else {
            throw new TokenValidationException('Bad audience');
        }
    }
}
