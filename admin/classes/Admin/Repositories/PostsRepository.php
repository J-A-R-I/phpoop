<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use PDO;

final class PostsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }

    public function getAll(): array
    {
        $sql = "SELECT id, title, content, status, slug, featured_media_id, created_at
                FROM posts
                ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // NIEUW: Zoeken op slug column
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT id, title, content, status, featured_media_id, slug, created_at
                FROM posts
                WHERE slug = :slug
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    // NIEUW: Verwijderen op slug column
    public function deleteBySlug(string $slug): void
    {
        $sql = "DELETE FROM posts WHERE slug = :slug";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
    }

    public function create(string $title, string $content, string $status, string $slug, ?int $featuredMediaId = null): int
    {
        $sql = "INSERT INTO posts (title, content, status, featured_media_id, slug, created_at)
                VALUES (:title, :content, :status, :featured_media_id, :slug, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'slug' => $slug,
            'featured_media_id' => $featuredMediaId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $title, string $content, string $status, string $slug, ?int $featuredMediaId = null): void
    {
        $sql = "UPDATE posts
                SET title = :title,
                    content = :content,
                    status = :status,
                    slug = :slug,
                    featured_media_id = :featured_media_id
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'slug' => $slug,
        ]);
    }

    // Frontend methodes (ongewijzigd laten)
    public function getPublishedLatest(int $limit = 6): array
    {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT id, title, content, status, featured_media_id, slug, created_at
                FROM posts
                WHERE status = 'published'
                ORDER BY created_at DESC
                LIMIT " . (int)$limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPublishedById(int $id): ?array
    {
        $sql = "SELECT id, title, content, status, featured_media_id, slug, created_at
                FROM posts
                WHERE id = :id AND status = 'published'
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}