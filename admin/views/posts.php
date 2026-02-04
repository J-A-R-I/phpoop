<?php
declare(strict_types=1);

use Admin\Core\Auth;

?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Posts overzicht</h2>

            <a class="underline" href="/admin/posts/create">
                + Nieuwe post
            </a>
        </div>

        <table class="w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Titel</th>
                <th>Slug</th>
                <th>Status</th>
                <th class="text-right">Acties</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($posts as $post): ?>
                <?php
                // Haal slug op
                $rawSlug = (string)($post['slug'] ?? '');
                // rawurlencode zorgt voor veilige links.
                // Na opslaan via de nieuwe controller is dit netjes "post-5"
                $slugLink = $rawSlug !== '' ? rawurlencode($rawSlug) : (int)$post['id'];
                ?>
                <tr class="border-b">
                    <td class="py-2">
                        <a class="underline" href="/admin/posts/<?= $slugLink; ?>">
                            <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?>
                        </a>
                    </td>
                    <td class="text-gray-500 italic">
                        <?= htmlspecialchars($rawSlug, ENT_QUOTES); ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)$post['status'], ENT_QUOTES); ?></td>
                    <td class="text-right space-x-3">
                        <a class="underline" href="/admin/posts/<?= $slugLink; ?>/edit">
                            Bewerken
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                            <a class="underline text-red-600" href="/admin/posts/<?= $slugLink; ?>/delete">
                                Verwijderen
                            </a>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>