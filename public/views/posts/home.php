<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

ob_start();

?>

    <header class="mb-8">
        <h1 class="text-3xl font-semibold">Laatste posts</h1>
        <p class="text-slate-300 mt-2">Recente gepubliceerde posts.</p>
    </header>

<?php
if (empty($posts)): ?>
    <div class="rounded-xl border border-white/10 bg-white/5 p-6 text-slate-300">
        Nog geen gepubliceerde posts.
    </div>
<?php else: ?>
    <div class="grid md:grid-cols-2 gap-4">
        <?php
        foreach ($posts as $post): ?>
            <article class="rounded-xl border border-white/10 bg-white/5 overflow-hidden">
                <?php if (!empty($post['featured_image_path']) && !empty($post['featured_image_filename'])): ?>
                    <?php $imagePath = '/' . trim($post['featured_image_path'], '/') . '/' . $post['featured_image_filename']; ?>
                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES) ?>" 
                         alt="<?= htmlspecialchars($post['featured_image_alt'] ?? $post['title'], ENT_QUOTES) ?>"
                         class="w-full h-48 object-cover">
                <?php endif; ?>
                
                <div class="p-6">
                    <h2 class="text-xl font-semibold">
                        <a class="hover:underline" href="/posts/<?= (int)$post['id'] ?>">
                            <?php
                            $cardTitle = !empty($post['meta_title']) ? (string)$post['meta_title'] : (string)$post['title'];
                            echo htmlspecialchars($cardTitle);
                            ?>
                        </a>
                    </h2>

                    <p class="text-sm text-slate-400 mt-2">
                        <?= htmlspecialchars((string)($post['created_at'] ?? '')) ?>
                    </p>

                    <p class="text-slate-300 mt-4">
                        <?php
                        if (!empty($post['meta_description'])) {
                            echo htmlspecialchars((string)$post['meta_description']);
                        } else {
                            $cleanText = strip_tags((string)$post['content']);
                            $preview = mb_strimwidth($cleanText, 0, 140, '...');
                            echo htmlspecialchars($preview);
                        }
                        ?>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean(); // buffer -> string
$title = 'Home';
require __DIR__ . '/../layouts/public.php';
