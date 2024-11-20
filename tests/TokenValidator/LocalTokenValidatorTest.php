<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests\TokenValidator;

use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDProvider;
use Dbp\Relay\CoreConnectorOidcBundle\TokenValidator\LocalTokenValidator;
use Dbp\Relay\CoreConnectorOidcBundle\TokenValidator\TokenValidationException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalTokenValidatorTest extends TestCase
{
    /* @var LocalTokenValidator */
    private $tokenValidator;

    private $oid;

    protected function setUp(): void
    {
        $this->oid = new OIDProvider();
        $this->tokenValidator = new LocalTokenValidator($this->oid, 0);
        $this->mockResponses([]);
    }

    private function getJWK(string $alg)
    {
        // Example keys taken from https://github.com/web-token/jwt-framework/tree/47e3285121849f9b5a82fa720c19efdf50f9ca3d/performance/JWS
        if (\str_starts_with($alg, 'RS') || \str_starts_with($alg, 'PS')) {
            return new JWK([
                'kty' => 'RSA',
                'kid' => 'bilbo.baggins.rsa@hobbiton.example',
                'use' => 'sig',
                'n' => 'n4EPtAOCc9AlkeQHPzHStgAbgs7bTZLwUBZdR8_KuKPEHLd4rHVTeT-O-XV2jRojdNhxJWTDvNd7nqQ0VEiZQHz_AJmSCpMaJMRBSFKrKb2wqVwGU_NsYOYL-QtiWN2lbzcEe6XC0dApr5ydQLrHqkHHig3RBordaZ6Aj-oBHqFEHYpPe7Tpe-OfVfHd1E6cS6M1FZcD1NNLYD5lFHpPI9bTwJlsde3uhGqC0ZCuEHg8lhzwOHrtIQbS0FVbb9k3-tVTU4fg_3L_vniUFAKwuCLqKnS2BYwdq_mzSnbLY7h_qixoR7jig3__kRhuaxwUkRz5iaiQkqgc5gHdrNP5zw',
                'e' => 'AQAB',
                'd' => 'bWUC9B-EFRIo8kpGfh0ZuyGPvMNKvYWNtB_ikiH9k20eT-O1q_I78eiZkpXxXQ0UTEs2LsNRS-8uJbvQ-A1irkwMSMkK1J3XTGgdrhCku9gRldY7sNA_AKZGh-Q661_42rINLRCe8W-nZ34ui_qOfkLnK9QWDDqpaIsA-bMwWWSDFu2MUBYwkHTMEzLYGqOe04noqeq1hExBTHBOBdkMXiuFhUq1BU6l-DqEiWxqg82sXt2h-LMnT3046AOYJoRioz75tSUQfGCshWTBnP5uDjd18kKhyv07lhfSJdrPdM5Plyl21hsFf4L_mHCuoFau7gdsPfHPxxjVOcOpBrQzwQ',
                'p' => '3Slxg_DwTXJcb6095RoXygQCAZ5RnAvZlno1yhHtnUex_fp7AZ_9nRaO7HX_-SFfGQeutao2TDjDAWU4Vupk8rw9JR0AzZ0N2fvuIAmr_WCsmGpeNqQnev1T7IyEsnh8UMt-n5CafhkikzhEsrmndH6LxOrvRJlsPp6Zv8bUq0k',
                'q' => 'uKE2dh-cTf6ERF4k4e_jy78GfPYUIaUyoSSJuBzp3Cubk3OCqs6grT8bR_cu0Dm1MZwWmtdqDyI95HrUeq3MP15vMMON8lHTeZu2lmKvwqW7anV5UzhM1iZ7z4yMkuUwFWoBvyY898EXvRD-hdqRxHlSqAZ192zB3pVFJ0s7pFc',
                'dp' => 'B8PVvXkvJrj2L-GYQ7v3y9r6Kw5g9SahXBwsWUzp19TVlgI-YV85q1NIb1rxQtD-IsXXR3-TanevuRPRt5OBOdiMGQp8pbt26gljYfKU_E9xn-RULHz0-ed9E9gXLKD4VGngpz-PfQ_q29pk5xWHoJp009Qf1HvChixRX59ehik',
                'dq' => 'CLDmDGduhylc9o7r84rEUVn7pzQ6PF83Y-iBZx5NT-TpnOZKF1pErAMVeKzFEl41DlHHqqBLSM0W1sOFbwTxYWZDm6sI6og5iTbwQGIC3gnJKbi_7k_vJgGHwHxgPaX2PnvP-zyEkDERuf-ry4c_Z11Cq9AqC2yeL6kdKT1cYF8',
                'qi' => '3PiqvXQN0zwMeE-sBvZgi289XP9XCQF3VWqPzMKnIgQp7_Tugo6-NZBKCQsMf3HaEGBjTVJs_jcK8-TRXvaKe-7ZMaQj8VfBdYkssbu0NKDDhjJ-GtiseaDVWt7dcH0cfwxgFUHpQh7FoCrjFJ6h6ZEpMF6xmujs4qMpPz8aaI4',
            ]);
        } elseif (\str_starts_with($alg, 'HS')) {
            return new JWK([
                'kty' => 'oct',
                'kid' => 'bilbo.baggins.hmac@hobbiton.example',
                'use' => 'sig',
                'k' => 'uRlFc5ToCUJtMLBi5eMrMT-k1rEytzm7quHuadKnU5Vvj6_97BtJprASN3s7eMWNQrAd9MRxpk_Du54SYAVutw',
            ]);
        } elseif ($alg === 'ES512') {
            return new JWK([
                'kty' => 'EC',
                'kid' => 'bilbo.baggins.ES512@hobbiton.example',
                'use' => 'sig',
                'crv' => 'P-521',
                'x' => 'AHKZLLOsCOzz5cY97ewNUajB957y-C-U88c3v13nmGZx6sYl_oJXu9A5RkTKqjqvjyekWF-7ytDyRXYgCF5cj0Kt',
                'y' => 'AdymlHvOiLxXkEhayXQnNCvDX4h9htZaCJN34kfmC6pV5OhQHiraVySsUdaQkAgDPrwQrJmbnX9cwlGfP-HqHZR1',
                'd' => 'AAhRON2r9cqXX1hg-RoI6R1tX5p2rUAYdmpHZoC1XNM56KtscrX6zbKipQrCW9CGZH3T4ubpnoTKLDYJ_fF3_rJt',
            ]);
        } elseif ($alg === 'ES384') {
            return new JWK([
                'kty' => 'EC',
                'kid' => 'bilbo.baggins.ES384@hobbiton.example',
                'use' => 'sig',
                'crv' => 'P-384',
                'x' => 'YU4rRUzdmVqmRtWOs2OpDE_T5fsNIodcG8G5FWPrTPMyxpzsSOGaQLpe2FpxBmu2',
                'y' => 'A8-yxCHxkfBz3hKZfI1jUYMjUhsEveZ9THuwFjH2sCNdtksRJU7D5-SkgaFL1ETP',
                'd' => 'iTx2pk7wW-GqJkHcEkFQb2EFyYcO7RugmaW3mRrQVAOUiPommT0IdnYK2xDlZh-j',
            ]);
        } elseif ($alg === 'ES256') {
            return new JWK([
                'kty' => 'EC',
                'kid' => 'bilbo.baggins.ES256@hobbiton.example',
                'use' => 'sig',
                'crv' => 'P-256',
                'x' => 'Ze2loSV3wrroKUN_4zhwGhCqo3Xhu1td4QjeQ5wIVR0',
                'y' => 'HlLtdXARY_f55A3fnzQbPcm6hgr34Mp8p-nuzQCE0Zw',
                'd' => 'r_kHyZ-a06rmxM3yESK84r1otSg-aQcVStkRhA-iCM8',
            ]);
        } elseif ($alg === 'EdDSA') {
            return new JWK([
                'kty' => 'OKP',
                'kid' => 'bilbo.baggins.EdDSA@hobbiton.example',
                'crv' => 'Ed25519',
                'd' => 'nWGxne_9WmC6hEr0kuwsxERJxWl7MmkZcDusAxyuf2A',
                'x' => '11qYAYKxCrfVS_7TyWQHOg7hcvPapiMlrwIaaPcHURo',
            ]);
        } elseif ($alg === 'none') {
            return new JWK(['kty' => 'none']);
        } else {
            throw new \RuntimeException('Unsupported alg: '.$alg);
        }
    }

    private function getPublicJWKs()
    {
        return ['keys' => [
            $this->getJWK('RS256')->toPublic()->jsonSerialize(),
            $this->getJWK('HS256')->toPublic()->jsonSerialize(),
            $this->getJWK('ES256')->toPublic()->jsonSerialize(),
            $this->getJWK('ES384')->toPublic()->jsonSerialize(),
            $this->getJWK('ES512')->toPublic()->jsonSerialize(),
            $this->getJWK('EdDSA')->toPublic()->jsonSerialize(),
            $this->getJWK('none')->toPublic()->jsonSerialize(),
        ]];
    }

    private function getDiscoverResponse()
    {
        return [
            'issuer' => 'https://nope/issuer',
            'jwks_uri' => 'https://nope/certs',
            'introspection_endpoint' => 'https://nope/introspect',
            'introspection_endpoint_auth_signing_alg_values_supported' => [
                'RS256', 'RS384', 'RS512',
                'PS256', 'PS384', 'PS512',
                'ES256', 'ES384', 'ES512',
                'HS256', 'HS384', 'HS512',
                'EdDSA',
            ],
        ];
    }

    private function getJWT(?int $time = null, ?string $issuer = null, string $alg = 'RS256'): string
    {
        $time ??= time();

        $payload = json_encode([
            'exp' => $time + 3600,
            'iat' => $time,
            'nbf' => $time,
            'jti' => '0123456789',
            'iss' => $issuer ?? $this->oid->getProviderConfig()->getIssuer(),
            'aud' => ['audience1', 'audience2'],
            'sub' => 'subject',
        ]);

        $jwk = $this->getJWK($alg);

        $algorithmManager = new AlgorithmManager([
            new Algorithm\RS256(),
            new Algorithm\RS384(),
            new Algorithm\RS512(),
            new Algorithm\PS256(),
            new Algorithm\PS384(),
            new Algorithm\PS512(),
            new Algorithm\ES256(),
            new Algorithm\ES384(),
            new Algorithm\ES512(),
            new Algorithm\HS256(),
            new Algorithm\HS384(),
            new Algorithm\HS512(),
            new Algorithm\EdDSA(),
            new Algorithm\None(),
        ]);
        $serializer = new CompactSerializer();
        $jwsBuilder = new JWSBuilder($algorithmManager);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => $alg])
            ->build();

        return $serializer->serialize($jws, 0);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->oid->setClientHandler($stack);
    }

    private function mockJWKResponse(array $extraAlgs = [])
    {
        $jwks = $this->getPublicJWKs();
        $discover = $this->getDiscoverResponse();
        $discover['introspection_endpoint_auth_signing_alg_values_supported'] = array_merge(
            $discover['introspection_endpoint_auth_signing_alg_values_supported'], $extraAlgs);
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($discover)),
            new Response(200, ['Content-Type' => 'application/json'], json_encode($jwks)),
        ]);
    }

    public function testCheckAudienceBad()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        $this->expectExceptionMessageMatches('/Bad audience/');
        LocalTokenValidator::checkAudience($result, 'foo');
    }

    public function testCheckAudienceGood()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        LocalTokenValidator::checkAudience($result, 'audience2');
        LocalTokenValidator::checkAudience($result, 'audience1');
        $this->assertTrue(true);
    }

    public function testLocalNoResponse()
    {
        $this->mockJWKResponse();
        $this->expectException(TokenValidationException::class);
        $this->tokenValidator->validate('foobar');
    }

    public function testLocalWrongUrl()
    {
        $this->mockResponses([
            new Response(404, ['Content-Type' => 'application/json']),
        ]);
        $this->expectException(TokenValidationException::class);
        $this->tokenValidator->validate('foobar');
    }

    public function testLocalNoneAlgo()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(alg: 'none');
        $this->expectExceptionMessageMatches('/Unable to load and verify the token/');
        $this->tokenValidator->validate($jwt);
    }

    public function testLocalNoneAlgoAdvertised()
    {
        // Even if the provider advertises 'none' (which is not allowed), we still want it to fail
        $this->mockJWKResponse(extraAlgs: ['none']);

        $jwt = $this->getJWT(alg: 'none');
        $this->expectExceptionMessageMatches('/Unable to load and verify the token/');
        $this->tokenValidator->validate($jwt);
    }

    public function testLocalExpired()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(time: 42);
        $this->expectExceptionMessageMatches('/expired/');
        $this->tokenValidator->validate($jwt);
    }

    public function testLocalFutureIssued()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(time: time() + 3600);
        $this->expectExceptionMessageMatches('/future/');
        $this->tokenValidator->validate($jwt);
    }

    public static function allAlgos(): array
    {
        return [
            ['RS256'], ['RS384'], ['RS512'],
            ['PS256'], ['PS384'], ['PS512'],
            ['ES256'], ['ES384'], ['ES512'],
            ['HS256'], ['HS384'], ['HS512'],
            ['EdDSA'],
        ];
    }

    public function testLocalWrongRealm()
    {
        $this->mockJWKResponse();

        $this->expectExceptionMessageMatches('/Unknown issuer/');
        $this->tokenValidator->validate($this->getJWT(issuer: 'foobar'));
    }

    #[DataProvider('allAlgos')]
    public function testLocalInvalidSig(string $alg)
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT();
        $parts = explode('.', $jwt);
        $parts[1] = 'REVBREJFRUY=';

        $this->expectExceptionMessageMatches('/Unable to load and verify the token/');
        $this->tokenValidator->validate(implode('.', $parts));
    }

    #[DataProvider('allAlgos')]
    public function testLocalValid(string $alg)
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(alg: $alg);
        $result = $this->tokenValidator->validate($jwt);
        $this->assertEquals('subject', $result['sub']);
    }

    public function testMissingUser()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        $this->assertEquals(null, $result['username']);
    }
}
