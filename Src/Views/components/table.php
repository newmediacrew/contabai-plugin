<?php

$table_headers = $table_headers ?? [];
$table_rows    = $table_rows ?? [];
?>
<div class="flex flex-col">
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div class="overflow-hidden rounded-xl border border-neutral-200">
                <table class="min-w-full divide-y divide-neutral-200">
                    <?php if (! empty($table_headers)): ?>
                        <thead class="bg-neutral-50">
                            <tr class="text-neutral-500">
                                <?php foreach ($table_headers as $header): ?>
                                    <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium uppercase"><?php echo esc_html($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                    <?php endif; ?>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        <?php foreach ($table_rows as $row): ?>
                            <tr class="text-neutral-700 transition-colors hover:bg-neutral-50">
                                <?php foreach ((array) $row as $cell): ?>
                                    <td class="px-5 py-4 text-sm align-top"><?php echo esc_html($cell); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $table_headers = $table_rows = null; ?>
