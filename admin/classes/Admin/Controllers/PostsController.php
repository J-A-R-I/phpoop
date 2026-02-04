<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\Flash;
use Admin\Core\View;
use Admin\Repositories\MediaRepository;
use Admin\Repositories\PostsRepository;

final class PostsController
{
    private PostsRepository $posts;

    public function __construct(PostsRepository $posts)
    {
        $this->posts = $posts;
    }

    public function index(): void
    {
        View::render('posts.php', [
            'title' => 'Posts',
            'posts' => $this->posts->getAll(),
        ]);
    }

    public function create(): void
    {
        $old = Flash::get('old');
        if (!is_array($old)) {
            $old = ['title' => '', 'content' => '', 'status' => 'draft', 'slug' => '', 'featured_media_id' => ''];
        }

        View::render('post-create.php', [
            'title' => 'Nieuwe post',
            'old' => $old,
            'media' => MediaRepository::make()->getAllImages(),
        ]);
    }

    public function store(): void
    {
        $title   = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status  = (string)($_POST['status'] ?? 'draft');

        // Slug logica
        $rawSlug = trim((string)($_POST['slug'] ?? ''));
        if ($rawSlug === '') {
            $rawSlug = $title;
        }
        $slug = $this->generateSlug($rawSlug);

        $featuredRaw = trim((string)($_POST['featured_media_id'] ?? ''));
        $featuredId = $this->normalizeFeaturedId($featuredRaw);

        // Validatie (Nu met speciale tekens check)
        $errors = $this->validate($title, $content, $status, $featuredId);

        // Check op dubbele slug (Extra veiligheid)
        if (empty($errors) && $this->posts->findBySlug($slug)) {
            $errors[] = "De gegenereerde URL '$slug' bestaat al. Kies een andere titel.";
        }

        if (!empty($errors)) {
            Flash::set('warning', $errors);
            Flash::set('old', compact('title', 'content', 'status', 'slug') + ['featured_media_id' => $featuredRaw]);
            header('Location: ' . ADMIN_BASE_PATH . '/posts/create');
            exit;
        }

        $this->posts->create($title, $content, $status, $slug, $featuredId);

        Flash::set('success', 'Post succesvol aangemaakt.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function edit(string $slug): void
    {
        $slug = urldecode($slug);
        $post = $this->posts->findBySlug($slug);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $old = Flash::get('old');
        if (!is_array($old)) {
            $old = [
                'title' => (string)$post['title'],
                'content' => (string)$post['content'],
                'status' => (string)$post['status'],
                'slug' => (string)($post['slug'] ?? ''),
                'featured_media_id' => (string)($post['featured_media_id'] ?? ''),
            ];
        }

        View::render('post-edit.php', [
            'title' => 'Post bewerken',
            'postSlug' => $slug,
            'postId' => (int)$post['id'],
            'post' => $post,
            'old' => $old,
            'media' => MediaRepository::make()->getAllImages(),
        ]);
    }

    public function update(string $currentSlug): void
    {
        $currentSlug = urldecode($currentSlug);
        $post = $this->posts->findBySlug($currentSlug);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $id = (int)$post['id'];

        $title   = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status  = (string)($_POST['status'] ?? 'draft');

        $rawSlug = trim((string)($_POST['slug'] ?? ''));
        if ($rawSlug === '') {
            $rawSlug = $title;
        }
        $newSlug = $this->generateSlug($rawSlug);

        $featuredRaw = trim((string)($_POST['featured_media_id'] ?? ''));
        $featuredId = $this->normalizeFeaturedId($featuredRaw);

        $errors = $this->validate($title, $content, $status, $featuredId);

        // Check op dubbele slug bij update
        if (empty($errors)) {
            $existing = $this->posts->findBySlug($newSlug);
            if ($existing && (int)$existing['id'] !== $id) {
                $errors[] = "De URL '$newSlug' is al in gebruik door een andere post.";
            }
        }

        if (!empty($errors)) {
            Flash::set('warning', $errors);
            Flash::set('old', compact('title', 'content', 'status') + ['slug' => $newSlug, 'featured_media_id' => $featuredRaw]);
            // Terug naar de huidige (oude) slug URL
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . urlencode($currentSlug) . '/edit');
            exit;
        }

        $this->posts->update($id, $title, $content, $status, $newSlug, $featuredId);

        Flash::set('success', 'Post succesvol aangepast.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function deleteConfirm(string $slug): void
    {
        $slug = urldecode($slug);
        $post = $this->posts->findBySlug($slug);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        View::render('post-delete.php', [
            'title' => 'Post verwijderen',
            'post' => $post,
        ]);
    }

    public function delete(string $slug): void
    {
        $slug = urldecode($slug);
        $this->posts->deleteBySlug($slug);

        Flash::set('success', 'Post verwijderd.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function show(string $slug): void
    {
        $slug = urldecode($slug);
        $post = $this->posts->findBySlug($slug);

        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        View::render('post-show.php', [
            'title' => 'Post bekijken',
            'post' => $post,
        ]);
    }

    /**
     * Helper: maakt van "Post 15" -> "post15"
     */
    private function generateSlug(string $text): string
    {
        $text = strtolower($text);
        // Alles weg behalve a-z en 0-9
        $text = preg_replace('/[^a-z0-9]/', '', $text);
        return $text;
    }

    private function normalizeFeaturedId(string $raw): ?int
    {
        if ($raw === '' || !ctype_digit($raw)) { return null; }
        $id = (int)$raw;
        return $id > 0 ? $id : null;
    }

    private function validate(string $title, string $content, string $status, ?int $featuredId): array
    {
        $errors = [];

        if ($title === '') {
            $errors[] = 'Titel is verplicht.';
        } elseif (mb_strlen($title) < 3) {
            $errors[] = 'Titel moet minstens 3 tekens bevatten.';
        } elseif (preg_match('/[^a-zA-Z0-9 ]/', $title)) {
            // NIEUW: Check op verboden tekens in de titel
            $errors[] = 'Titel mag geen speciale tekens bevatten (alleen letters, cijfers en spaties).';
        }

        if ($content === '') {
            $errors[] = 'Inhoud is verplicht.';
        } elseif (mb_strlen($content) < 10) {
            $errors[] = 'Inhoud moet minstens 10 tekens bevatten.';
        }

        if (!in_array($status, ['draft', 'published'], true)) {
            $errors[] = 'Status moet draft of published zijn.';
        }

        if ($featuredId !== null && MediaRepository::make()->findImageById($featuredId) === null) {
            $errors[] = 'Featured image is ongeldig.';
        }

        return $errors;
    }
}