<?php
require 'core/bootstrap.php'; require_login();
$customerId=(int)($_GET['customer_id']??$_POST['customer_id']??0);
$s=$pdo->prepare('SELECT * FROM customers WHERE customer_id=?'); $s->execute([$customerId]); $customer=$s->fetch();
if (!$customer) { http_response_code(404); page_header('Customer not found'); echo '<p>Return to <a href="index.php">Customers</a>.</p>'; page_footer(); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf(); $action=$_POST['action']??''; $id=(int)($_POST['id']??0);
    try {
        if ($action==='add' || $action==='edit') {
            $name=trim($_POST['device_name']??''); $issue=trim($_POST['issue']??'');
            if (!$name || !$issue) throw new RuntimeException('Enter both device name and issue.');
            $pdo->beginTransaction();
            if ($action==='add') {
                $s=$pdo->prepare('INSERT INTO devices(device_name,issue,customer_id,added_by,updated_by) VALUES(?,?,?,?,?)');
                $s->execute([$name,$issue,$customerId,$_SESSION['user_id'],$_SESSION['user_id']]);
                $id=(int)$pdo->lastInsertId(); record_audit($pdo,'devices',$id,'INSERT','Device created: '.$name);
                alert('Device added.');
            } else {
                $s=$pdo->prepare('SELECT 1 FROM devices WHERE device_id=? AND customer_id=?'); $s->execute([$id,$customerId]);
                if (!$s->fetchColumn()) throw new RuntimeException('Device not found for this customer.');
                $pdo->prepare('UPDATE devices SET device_name=?,issue=?,updated_by=?,last_updated=NOW() WHERE device_id=? AND customer_id=?')
                    ->execute([$name,$issue,$_SESSION['user_id'],$id,$customerId]);
                record_audit($pdo,'devices',$id,'UPDATE','Device updated: '.$name);
                alert('Device updated.');
            }
            $pdo->commit();
        } elseif ($action==='delete') {
            $pdo->beginTransaction();
            $s=$pdo->prepare('SELECT * FROM devices WHERE device_id=? AND customer_id=? FOR UPDATE'); $s->execute([$id,$customerId]); $old=$s->fetch();
            if (!$old) throw new RuntimeException('Device not found for this customer.');
            record_audit($pdo,'devices',$id,'DELETE','Device deleted: '.$old['device_name'].' (Customer #'.$customerId.')');
            $pdo->prepare('DELETE FROM devices WHERE device_id=? AND customer_id=?')->execute([$id,$customerId]);
            $pdo->commit(); alert('Device deleted.');
        }
        go('devices.php?customer_id='.$customerId);
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error=$e instanceof RuntimeException ? $e->getMessage() : 'Unable to save. Check database.'; }
}
$edit=null;
if (isset($_GET['edit'])) { $s=$pdo->prepare('SELECT * FROM devices WHERE device_id=? AND customer_id=?'); $s->execute([(int)$_GET['edit'],$customerId]); $edit=$s->fetch(); }
$s=$pdo->prepare('SELECT d.*,a.username AS added_username,u.username AS updated_username FROM devices d LEFT JOIN users a ON d.added_by=a.user_id LEFT JOIN users u ON d.updated_by=u.user_id WHERE d.customer_id=? ORDER BY d.device_id DESC');
$s->execute([$customerId]); $devices=$s->fetchAll();
page_header('Devices of '.$customer['first_name'].' '.$customer['last_name']);
if ($error) echo '<div class="error" role="alert">'.h($error).'</div>';
?>
<a class="back-link" href="index.php"><?= ui_icon('arrow-left', 14) ?> Back to customers</a>
<div class="metrics" aria-label="Device overview">
    <article class="metric"><span class="metric-icon"><?= ui_icon('users', 22) ?></span><div><div class="metric-label">Customer</div><div class="metric-value small"><?= h($customer['first_name'].' '.$customer['last_name']) ?></div></div></article>
    <article class="metric"><span class="metric-icon taupe"><?= ui_icon('monitor', 22) ?></span><div><div class="metric-label">Registered devices</div><div class="metric-value"><?= count($devices) ?></div></div></article>
    <article class="metric"><span class="metric-icon dark"><?= ui_icon('shield', 21) ?></span><div><div class="metric-label">Logged-in user</div><div class="metric-value small"><?= h($_SESSION['username'] ?? '') ?></div></div></article>
</div>
<div class="workspace-grid">
    <section class="panel" aria-labelledby="device-form-heading">
        <div class="panel-heading"><div><div class="section-overline">Device information</div><h2 id="device-form-heading"><?= $edit ? 'Edit device' : 'Register device' ?></h2><p><?= $edit ? 'Edit the device details and issue.' : 'Add a new repair device for this customer.' ?></p></div><span class="panel-counter"><?= $edit ? 'EDIT' : 'NEW' ?></span></div>
        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
            <input type="hidden" name="id" value="<?= h($edit['device_id'] ?? '') ?>">
            <input type="hidden" name="customer_id" value="<?= h($customerId) ?>">
            <label>Device name<input name="device_name" maxlength="50" required placeholder="e.g. Lenovo laptop" value="<?= h($edit['device_name'] ?? '') ?>"></label>
            <label>Reported issue<textarea name="issue" maxlength="100" required placeholder="Describe the issue with the device..."><?= h($edit['issue'] ?? '') ?></textarea></label>
            <div class="form-actions"><button class="primary-btn" type="submit"><?= ui_icon($edit ? 'check' : 'plus', 16) ?> <?= $edit ? 'Save device' : 'Add device' ?></button><?php if ($edit): ?><a class="button secondary" href="devices.php?customer_id=<?= h($customerId) ?>">Cancel</a><?php endif; ?></div>
        </form>
        <p class="hint">The system saves who added or updated each device.</p>
    </section>
    <section class="panel" aria-labelledby="device-list-heading">
        <div class="panel-heading"><div><div class="section-overline">Customer devices</div><h2 id="device-list-heading">Registered devices</h2><p>View devices registered under this customer.</p></div><span class="panel-counter"><?= count($devices) ?> total</span></div>
        <div class="table-tools"><label class="search-field"><span class="search-label">Search devices</span><input type="search" placeholder="Search device, issue or user..." data-table-search="device-table"></label></div>
        <div class="table-scroll"><table id="device-table"><thead><tr><th>ID</th><th>Device</th><th>Issue</th><th>Added by</th><th>Last updated by</th><th>Updated at</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($devices as $d): ?>
            <tr data-search-row>
                <td class="id-cell">#<?= h($d['device_id']) ?></td>
                <td><span class="cell-primary"><?= h($d['device_name']) ?></span></td>
                <td><?= h($d['issue']) ?></td>
                <td><?= h($d['added_username'] ?? 'Old record') ?></td>
                <td><?= h($d['updated_username'] ?? 'Old record') ?></td>
                <td><?= h($d['last_updated'] ?: '—') ?></td>
                <td><div class="actions">
                    <a class="button orange" href="devices.php?customer_id=<?= h($customerId) ?>&amp;edit=<?= h($d['device_id']) ?>"><?= ui_icon('edit', 12) ?> Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this device?')">
                        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
                        <input type="hidden" name="customer_id" value="<?= h($customerId) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= h($d['device_id']) ?>">
                        <button class="danger" type="submit"><?= ui_icon('trash', 12) ?> Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($devices): ?><tr data-no-match hidden><td colspan="7" class="no-matches">No matching devices found.</td></tr><?php endif; ?>
        <?php if (!$devices): ?><tr><td colspan="7"><div class="empty-state"><span class="empty-mark"><?= ui_icon('monitor', 23) ?></span><h3>No registered devices</h3><p>Add a device using the form to begin tracking repairs.</p></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php page_footer(); ?>
