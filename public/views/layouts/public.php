<?php
declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Layout: public
|--------------------------------------------------------------------------
| Verwacht van elke view:
| - $title   (string)
| - $content (string) -> HTML via output buffering
*/
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
    $pageTitle = $metaTitle ?? $title ?? 'MiniCMS';
    ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <?php if (isset($metaDescription) && $metaDescription !== ''): ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">

<?php require __DIR__ . '/../partials/nav.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-10">
    <?= $content ?>
</main>

<?php  require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>

