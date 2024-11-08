<?php

namespace App\Http\Controllers;

use App\Services\CognitoService;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    private $cognitoService;

    public function __construct(CognitoService $cognitoService)
    {
        $this->cognitoService = $cognitoService;
    }

    public function register(Request $request)
    {
        $username = $request->input('firstName') . '.' . $request->input('lastName');
        $userAttribute = [
            [
                'Name' => 'given_name',
                'Value' => $request->input('firstName'),
            ],
            [
                'Name' => 'family_name',
                'Value' => $request->input('lastName'),
            ],
            [
                'Name' => 'email',
                'Value' => $request->input('email'),
            ],
            [
                'Name' => 'phone_number',
                'Value' => '+16615835466',
            ],
            [
                'Name' => 'gender',
                'Value' => 'male',
            ],
            [
                'Name' => 'birthdate',
                'Value' => '16-08-1987',
            ],
            [
                'Name' => 'updated_at',
                'Value' => (string)time(),
            ],
            [
                'Name' => 'address',
                'Value' => $request->input('address'),
            ],
        ];
        $result = $this->cognitoService->signUp($username, '123456', $userAttribute);
        return response()->json($result);
    }

    public function confirm(Request $request)
    {
        $result = $this->cognitoService->confirmSignUp($request->input('username'), $request->input('code'));
        return response()->json($result);
    }

    public function login(Request $request)
    {
        $result = $this->cognitoService->signIn($request->input('email'), $request->input('password'));
        return response()->json($result);
    }

    public function confirmLogin(Request $request)
    {
        $result = $this->cognitoService->confirmLogin($request->input('session'), $request->input('username'), $request->input('MFACode'));
        return response()->json($result);
    }

    public function verifySoftwareToken(Request $request){
        $result = $this->cognitoService->verifySoftwareToken($request->input('session'), $request->input('username'), $request->input('MFACode'));
        return response()->json($result);
    }
}
