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
        $sql = "SELECT id, title, content, status, slug, featured_media_id, created_at, 
                       deleted_at, published_at, meta_title, meta_description
                FROM posts
                ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // NIEUW: Zoeken op slug column
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT id, title, content, status, featured_media_id, slug, created_at, 
                       published_at, meta_title, meta_description, deleted_at
                FROM posts
                WHERE slug = :slug
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function deleteBySlug(string $slug): void
    {
        $sql = "DELETE FROM posts WHERE slug = :slug";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
    }

    public function create(string $title, string $content, string $status, string $slug, ?int $featuredMediaId = null, ?string $publishedAt = null, ?string $metaTitle = null, ?string $metaDesc = null): int
    {
        $sql = "INSERT INTO posts (title, content, status, featured_media_id, slug, published_at, meta_title, meta_description, created_at)
                VALUES (:title, :content, :status, :featured_media_id, :slug, :published_at, :meta_title, :meta_description, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'slug' => $slug,
            'featured_media_id' => $featuredMediaId,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $title, string $content, string $status, string $slug, ?int $featuredMediaId = null, ?string $publishedAt = null, ?string $metaTitle = null, ?string $metaDesc = null): void
    {
        $sql = "UPDATE posts
                SET title = :title, content = :content, status = :status, slug = :slug,
                    featured_media_id = :featured_media_id, published_at = :published_at,
                    meta_title = :meta_title, meta_description = :meta_description,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'slug' => $slug,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc
        ]);
    }


    public function softDeleteBySlug(string $slug): void
    {
        $stmt = $this->pdo->prepare("UPDATE posts SET deleted_at = NOW() WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
    }

    public function restoreBySlug(string $slug): void
    {
        $stmt = $this->pdo->prepare("UPDATE posts SET deleted_at = NULL WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
    }

    public function getPublishedLatest(int $limit = 6): array
    {

        $sql = "SELECT p.id, p.title, p.content, p.status, p.slug, p.featured_media_id, 
                       p.created_at, p.updated_at, p.deleted_at, p.published_at, 
                       p.meta_title, p.meta_description,
                       m.path as featured_image_path, 
                       m.filename as featured_image_filename, 
                       m.alt_text as featured_image_alt
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.status = 'published' 
                AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW())
                ORDER BY p.published_at DESC LIMIT " . (int)$limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPublishedById(int $id): ?array
    {
        $sql = "SELECT p.id, p.title, p.content, p.status, p.slug, p.featured_media_id,
                       p.created_at, p.updated_at, p.deleted_at, p.published_at,
                       p.meta_title, p.meta_description,
                       m.path as featured_image_path,
                       m.filename as featured_image_filename,
                       m.alt_text as featured_image_alt
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.id = :id AND p.status = 'published' AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW()) LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getPublishedAll(): array
    {
        $sql = "SELECT p.id, p.title, p.content, p.status, p.slug, p.featured_media_id,
                       p.created_at, p.updated_at, p.deleted_at, p.published_at,
                       p.meta_title, p.meta_description,
                       m.path as featured_image_path,
                       m.filename as featured_image_filename,
                       m.alt_text as featured_image_alt
                FROM posts p
                LEFT JOIN media m ON p.featured_media_id = m.id
                WHERE p.status = 'published' 
                AND p.deleted_at IS NULL 
                AND (p.published_at IS NULL OR p.published_at <= NOW())
                ORDER BY p.published_at DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}