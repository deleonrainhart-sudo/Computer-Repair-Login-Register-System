<?php
require 'core/bootstrap.php'; require_login();
$logs=$pdo->query('SELECT log_id,username,entity_type,record_id,action_type,description,action_at FROM audit_logs ORDER BY log_id DESC LIMIT 200')->fetchAll();
page_header('Activity Log — Who Did It');
$insertCount = 0; $updateCount = 0; $deleteCount = 0;
foreach ($logs as $entry) {
    if ($entry['action_type'] === 'INSERT') ++$insertCount;
    elseif ($entry['action_type'] === 'UPDATE') ++$updateCount;
    elseif ($entry['action_type'] === 'DELETE') ++$deleteCount;
}
?>
<div class="metrics" aria-label="Recent activity summary">
    <article class="metric"><span class="metric-icon"><?= ui_icon('plus', 23) ?></span><div><div class="metric-label">Recent insertions</div><div class="metric-value"><?= $insertCount ?></div></div></article>
    <article class="metric"><span class="metric-icon taupe"><?= ui_icon('edit', 21) ?></span><div><div class="metric-label">Recent updates</div><div class="metric-value"><?= $updateCount ?></div></div></article>
    <article class="metric"><span class="metric-icon dark"><?= ui_icon('trash', 21) ?></span><div><div class="metric-label">Recent deletions</div><div class="metric-value"><?= $deleteCount ?></div></div></article>
</div>
<section class="panel">
    <div class="panel-heading"><div><div class="section-overline">Record history</div><h2>User activity log</h2><p>Shows the last 200 changes made by users, including deletions.</p></div><span class="panel-counter"><?= count($logs) ?> entries</span></div>
    <div class="table-tools"><label class="search-field"><span class="search-label">Search activity</span><input type="search" placeholder="Search user, record or action..." data-table-search="audit-table"></label></div>
    <div class="table-scroll"><table id="audit-table"><thead><tr><th>Log #</th><th>Username</th><th>Entity</th><th>Record</th><th>Action</th><th>Details</th><th>Timestamp</th></tr></thead><tbody>
    <?php foreach ($logs as $log): ?>
        <tr data-search-row>
            <td class="id-cell">#<?= h($log['log_id']) ?></td>
            <td><span class="cell-primary"><?= h($log['username']) ?></span></td>
            <td><?= h(ucfirst($log['entity_type'])) ?></td>
            <td>#<?= h($log['record_id']) ?></td>
            <td><span class="action-tag <?= h(strtolower($log['action_type'])) ?>"><?= h($log['action_type']) ?></span></td>
            <td><?= h($log['description']) ?></td>
            <td><?= h($log['action_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($logs): ?><tr data-no-match hidden><td colspan="7" class="no-matches">No matching activity found.</td></tr><?php endif; ?>
    <?php if (!$logs): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-mark"><?= ui_icon('activity', 23) ?></span><h3>No activity recorded yet</h3><p>Your saved changes will appear here.</p></div></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php page_footer(); ?>
