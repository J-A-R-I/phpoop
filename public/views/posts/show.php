<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| View: posts show
|--------------------------------------------------------------------------
| Verwacht:
| - $post (array)
*/

ob_start();
?>

    <a href="/posts" class="text-sm text-slate-300 hover:underline">← Terug naar overzicht</a>

    <article class="mt-6 rounded-xl border border-white/10 bg-white/5 p-8">
        <h1 class="text-3xl font-semibold">
            <?php
            $displayTitle = !empty($post['meta_title']) ? (string)$post['meta_title'] : (string)$post['title'];
            echo htmlspecialchars($displayTitle);
            ?>
        </h1>

        <p class="text-sm text-slate-400 mt-3">
            <?= htmlspecialchars((string)($post['created_at'] ?? '')) ?>
        </p>

        <?php if (!empty($post['meta_description'])): ?>
            <p class="text-lg text-slate-300 mt-4 italic border-l-4 border-blue-500 pl-4 py-2">
                <?= htmlspecialchars((string)$post['meta_description']) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($post['featured_image_path']) && !empty($post['featured_image_filename'])): ?>
            <?php
            $imagePath = '/' . trim($post['featured_image_path'], '/') . '/' . $post['featured_image_filename'];
            ?>
            <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES) ?>" 
                 alt="<?= htmlspecialchars($post['featured_image_alt'] ?? $post['title'], ENT_QUOTES) ?>"
                 class="w-full rounded-lg mt-6 max-h-96 object-cover">
        <?php endif; ?>

        <div class="mt-8 text-slate-200 leading-relaxed">
            <?php
            // nl2br behoudt nieuwe lijnen in HTML
            echo nl2br(htmlspecialchars((string)$post['content']));
            ?>
        </div>
    </article>

<?php
$content = ob_get_clean();
$title = (string)$post['title'];
$metaTitle = !empty($post['meta_title']) ? (string)$post['meta_title'] : $title;
$metaDescription = !empty($post['meta_description']) ? (string)$post['meta_description'] : '';
require __DIR__ . '/../layouts/public.php';
