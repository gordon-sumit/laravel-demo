<?php

namespace App\Services;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CognitoService
{
    protected $client;
    protected $clientId;
    protected $userPoolId;

    public function __construct()
    {
        $this->client = new CognitoIdentityProviderClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
//            'credentials' => [
//                'key' => env('AWS_ACCESS_KEY_ID'),
//                'secret' => env('AWS_SECRET_ACCESS_KEY'),
//            ],
        ]);
        $this->clientId = env('AWS_COGNITO_CLIENT_ID');
        $this->userPoolId = env('AWS_POOL_ID');
    }

    /**
     * @throws \Exception
     */
    public function signUp($username, $password, $userAttributes)
    {
        try {
            return $this->client->signUp([
                'ClientId' => $this->clientId,
                'Username' => $username,
                'Password' => $password,
                'UserAttributes' => $userAttributes,
            ])->toArray();
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public function confirmSignUp($username, $code)
    {
        return $this->client->confirmSignUp([
            'ClientId' => $this->clientId, // REQUIRED
            'ConfirmationCode' => $code, // REQUIRED
            'ForceAliasCreation' => true,
            'Username' => $username,
        ])->toArray();
    }

    public function signIn($username, $password)
    {
        try {
            $result = $this->client->initiateAuth([
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'ClientId' => $this->clientId,
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                ],
            ]);

            if ($result['ChallengeName'] == 'MFA_SETUP') {
                $mfaResponse = $this->associateSoftwareToken($result['Session'], $username);
                if (!empty($mfaResponse)) {
                    $result['qr'] = $mfaResponse['qr'];
                    $result['Session'] = $mfaResponse['session'];
                }
            }
            return $result->toArray();
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public function associateSoftwareToken($session, $username)
    {
        $result = $this->client->associateSoftwareToken([
            'Session' => $session
        ]);

        if ($result['SecretCode']) {
            $qr = $this->generate2FAQRCodeUrl($result['SecretCode'], $username);
            return ['qr' => $qr, 'session' => $result['Session']];
        }

    }

    public function generate2FAQRCodeUrl($secretCode, $username): string
    {
        $service = 'vercel-demo-app';
        return "otpauth://totp/{$service}:{$username}?secret={$secretCode}&issuer={$service}";
    }

    public function confirmLogin($session, $username, $code, $challenge = '')
    {
        try {
            return $this->client->respondToAuthChallenge([
                'ChallengeName' => $challenge ?: 'SOFTWARE_TOKEN_MFA',
                'ClientId' => $this->clientId,
                'Session' => $session,
                'ChallengeResponses' => [
                    'USERNAME' => $username,
                    'SOFTWARE_TOKEN_MFA_CODE' => $code
                ]
            ])->toArray();
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public function verifySoftwareToken($session, $username, $code)
    {
        try {
            $isTokenVerified = $this->client->verifySoftwareToken([
                'UserCode' => $code,
                'Session' => $session,
            ]);
            if ($isTokenVerified) {
                return $this->confirmLogin($isTokenVerified['Session'], $username, $code, 'MFA_SETUP');
            }
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
