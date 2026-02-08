<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\View;
use Admin\Core\Auth;
use Admin\Repositories\UsersRepository;

class AuthController
{
    private UsersRepository $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    public function showLogin(): void
    {
        View::render('login.php', [
            'title' => 'Login',
            'errors' => [],
            'old' => [
                'email' => '',
            ],
        ]);
    }

    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email is verplicht.';
        }

        if ($password === '') {
            $errors[] = 'Wachtwoord is verplicht.';
        }

        if (!empty($errors)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => $errors,
                'old' => ['email' => $email],
            ]);
            return;
        }

        $user = $this->usersRepository->findByEmail($email);

        if ($user === null) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        $hash = (string)$user['password_hash'];

        if (empty($hash) || !password_verify($password, $hash)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)$user['role_name'];

        header('Location: /admin');
        exit;
    }

    private function getProviderConfig(string $provider): array
    {
        $baseUrl = 'http://minicms.com/admin';

        if ($provider === 'github') {
            return [
                'client_id'     => 'Ov23ctyFwdXlTem5e9Xx',
                'client_secret' => '1fe197c54d5849b90e22c6ea334f646913dd24e7',
                'redirect_uri'  => $baseUrl . '/login/github/callback',
                'auth_url'      => 'https://github.com/login/oauth/authorize',
                'token_url'     => 'https://github.com/login/oauth/access_token',
                'user_url'      => 'https://api.github.com/user',
                'scope'         => 'user:email',
            ];
        }

        if ($provider === 'discord') {
            return [
                'client_id'     => '1469299868471398440',
                'client_secret' => '2QtiyjGX4UETyXf2Q-iVAUInsv6dq_yV',
                'redirect_uri'  => $baseUrl . '/login/discord/callback',
                'auth_url'      => 'https://discord.com/api/oauth2/authorize',
                'token_url'     => 'https://discord.com/api/oauth2/token',
                'user_url'      => 'https://discord.com/api/users/@me',
                'scope'         => 'identify email',
            ];
        }

        return [];
    }

    public function loginViaProvider(string $provider): void
    {
        if (!in_array($provider, ['github', 'discord'])) {
            header('Location: /admin/login');
            exit;
        }

        $config = $this->getProviderConfig($provider);

        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));

        $params = [
            'client_id'    => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope'        => $config['scope'],
            'state'        => $_SESSION['oauth_state'],
            'response_type'=> 'code',
        ];

        $url = $config['auth_url'] . '?' . http_build_query($params);

        header('Location: ' . $url);
        exit;
    }

    public function callbackProvider(string $provider): void
    {
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['oauth_state'] ?? 'xxx';

        unset($_SESSION['oauth_state']);

        if ($state === '' || $state !== $savedState) {
            die('Security Error: Invalid State (CSRF mismatch). Probeer opnieuw.');
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            header('Location: /admin/login');
            exit;
        }

        $config = $this->getProviderConfig($provider);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['token_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $postParams = [
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code'          => $code,
            'redirect_uri'  => $config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            die('Error bij ophalen token.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['user_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $authHeader = ($provider === 'github') ? "Authorization: token $accessToken" : "Authorization: Bearer $accessToken";

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $authHeader,
            "User-Agent: MiniCMS"
        ]);

        $userJson = curl_exec($ch);
        curl_close($ch);

        $providerUser = json_decode($userJson, true);

        $email = '';
        $providerId = '';
        $name = '';

        if ($provider === 'github') {
            $providerId = (string)($providerUser['id'] ?? '');
            $name = $providerUser['name'] ?? $providerUser['login'] ?? 'GitHub User';
            $email = $providerUser['email'] ?? (($providerUser['login'] ?? 'user') . '@github.placeholder');
        }
        elseif ($provider === 'discord') {
            $providerId = (string)($providerUser['id'] ?? '');
            $name = $providerUser['global_name'] ?? $providerUser['username'] ?? 'Discord User';
            $email = $providerUser['email'] ?? (($providerUser['username'] ?? 'user') . '@discord.placeholder');
        }

        if (empty($providerId)) {
            die("Kon geen user ID ophalen van $provider.");
        }

        $user = $this->usersRepository->findOrCreateByProvider($provider, $providerId, $email, $name);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)($user['role_name'] ?? 'user');

        header('Location: /admin');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /admin/login');
        exit;
    }
}