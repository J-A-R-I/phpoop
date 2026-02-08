<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use PDO;
use Exception;

class UsersRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * getAll()
     * Doel: admin-overzicht van alle users + rolnaam.
     */
    public function getAll(): array
    {
        $sql = "SELECT u.id, u.email, u.name, u.is_active, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                ORDER BY u.id ASC";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * findByEmail()
     * Doel: login alleen voor actieve users.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.id, u.email, u.password_hash, u.name, u.is_active, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.email = :email
                AND u.is_active = 1
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * create()
     * Doel: nieuwe user aanmaken met hash en default actief.
     */
    public function create(string $email, string $name, string $plainPassword, int $roleId): void
    {
        $sql = "INSERT INTO users (email, name, password_hash, role_id, is_active)
                VALUES (:email, :name, :hash, :role_id, 1)";

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'name' => $name,
            'hash' => $hash,
            'role_id' => $roleId,
        ]);
    }

    /**
     * findById()
     * Doel: user ophalen voor edit-form, inclusief role_id.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT u.id, u.email, u.name, u.role_id, u.is_active, r.name AS role_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * update()
     * Doel: naam + rol wijzigen.
     */
    public function update(int $id, string $name, int $roleId): void
    {
        $sql = "UPDATE users
                SET name = :name,
                    role_id = :role_id
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'role_id' => $roleId,
        ]);
    }

    /**
     * updatePassword()
     * Doel: wachtwoord resetten (hash vervangen).
     */
    public function updatePassword(int $id, string $plainPassword): void
    {
        $sql = "UPDATE users
                SET password_hash = :hash
                WHERE id = :id
                LIMIT 1";

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'hash' => $hash,
        ]);
    }

    /**
     * disable()
     * Doel: user blokkeren.
     */
    public function disable(int $id): void
    {
        $sql = "UPDATE users
                SET is_active = 0
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    /**
     * enable()
     * Doel: user deblokkeren.
     */
    public function enable(int $id): void
    {
        $sql = "UPDATE users
                SET is_active = 1
                WHERE id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    // --- NIEUWE METHODES VOOR OAUTH (Github/Google) ---

    /**
     * findOrCreateByProvider()
     * Doel: Zoek gebruiker via provider-link. Bestaat die niet? Maak of link dan.
     */
    public function findOrCreateByProvider(string $provider, string $providerId, string $email, string $name): array
    {
        // 1. Check of deze connectie al bestaat in auth_connections
        // We joinen meteen de user en role info erbij voor de sessie
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN auth_connections a ON u.id = a.user_id
            JOIN roles r ON u.role_id = r.id
            WHERE a.provider = :provider 
            AND a.provider_id = :provider_id
            LIMIT 1
        ");

        $stmt->execute([
            ':provider'    => $provider,
            ':provider_id' => $providerId
        ]);

        $user = $stmt->fetch();

        if ($user) {
            return $user; // Bestaande gebruiker gevonden via social login
        }

        // 2. Geen connectie? Check of e-mail al bestaat (account linking) of maak nieuw
        return $this->linkOrCreateUser($provider, $providerId, $email, $name);
    }

    /**
     * linkOrCreateUser()
     * Doel: Koppelt een OAuth account aan een bestaande of nieuwe user.
     * Gebruikt transacties om dataconsistentie te garanderen.
     */
    private function linkOrCreateUser(string $provider, string $providerId, string $email, string $name): array
    {
        try {
            $this->pdo->beginTransaction();

            // Stap A: Zoek user op email
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // User bestaat al -> we gaan koppelen
                $userId = (int)$existingUser['id'];
            } else {
                // User bestaat niet -> aanmaken
                // We gebruiken role_id = 2 (standaard user). Pas dit aan indien nodig.
                // Password hash is NULL omdat ze via social login komen.
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (email, name, role_id, is_active, created_at) 
                    VALUES (:email, :name, 2, 1, NOW())
                ");
                $stmt->execute([
                    ':email' => $email,
                    ':name'  => $name
                ]);
                $userId = (int)$this->pdo->lastInsertId();
            }

            // Stap B: Link toevoegen in auth_connections tabel
            $stmt = $this->pdo->prepare("
                INSERT INTO auth_connections (user_id, provider, provider_id, created_at)
                VALUES (:user_id, :provider, :provider_id, NOW())
            ");

            $stmt->execute([
                ':user_id'     => $userId,
                ':provider'    => $provider,
                ':provider_id' => $providerId
            ]);

            $this->pdo->commit();

            // Stap C: Haal de volledige user op om direct in te loggen
            return $this->findById($userId);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }
}