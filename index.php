<?php
require 'core/bootstrap.php'; require_login();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf();
    $action=$_POST['action']??''; $id=(int)($_POST['id']??0);
    try {
        if ($action==='add' || $action==='edit') {
            $fields=[trim($_POST['username']??''),trim($_POST['first_name']??''),trim($_POST['last_name']??''),trim($_POST['date_of_birth']??''),trim($_POST['contact_number']??'')];
            if (!$fields[1] || !$fields[2]) throw new RuntimeException('Customer first and last names are required.');
            if ($action==='add') {
                $pdo->beginTransaction();
                $s=$pdo->prepare('INSERT INTO customers(username,first_name,last_name,date_of_birth,contact_number,added_by,updated_by) VALUES(?,?,?,?,?,?,?)');
                $s->execute([...$fields,$_SESSION['user_id'],$_SESSION['user_id']]);
                $newId=(int)$pdo->lastInsertId(); record_audit($pdo,'customers',$newId,'INSERT','Customer created: '.$fields[1].' '.$fields[2]);
                $pdo->commit(); alert('Customer added.');
            } else {
                $pdo->beginTransaction();
                $s=$pdo->prepare('UPDATE customers SET username=?,first_name=?,last_name=?,date_of_birth=?,contact_number=?,updated_by=?,last_updated=NOW() WHERE customer_id=?');
                $s->execute([...$fields,$_SESSION['user_id'],$id]);
                if ($s->rowCount()<1) { $exists=$pdo->prepare('SELECT 1 FROM customers WHERE customer_id=?'); $exists->execute([$id]); if (!$exists->fetchColumn()) throw new RuntimeException('Customer not found.'); }
                record_audit($pdo,'customers',$id,'UPDATE','Customer updated: '.$fields[1].' '.$fields[2]);
                $pdo->commit(); alert('Customer updated.');
            }
        } elseif ($action==='delete') {
            $pdo->beginTransaction();
            $s=$pdo->prepare('SELECT * FROM customers WHERE customer_id=? FOR UPDATE'); $s->execute([$id]); $old=$s->fetch();
            if (!$old) throw new RuntimeException('Customer not found.');
            // Log the devices before deleting them with their customer.
            $children=$pdo->prepare('SELECT * FROM devices WHERE customer_id=?'); $children->execute([$id]);
            foreach ($children->fetchAll() as $child) record_audit($pdo,'devices',(int)$child['device_id'],'DELETE','Device deleted with customer: '.$child['device_name']);
            record_audit($pdo,'customers',$id,'DELETE','Customer deleted: '.$old['first_name'].' '.$old['last_name']);
            // Delete the linked devices first so this also works with older databases.
            $pdo->prepare('DELETE FROM devices WHERE customer_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM customers WHERE customer_id=?')->execute([$id]);
            $pdo->commit(); alert('Customer and associated devices deleted.');
        }
        go('index.php');
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error=$e instanceof RuntimeException ? $e->getMessage() : 'Unable to save. Check your database.'; }
}
$edit=null;
if (isset($_GET['edit'])) { $s=$pdo->prepare('SELECT * FROM customers WHERE customer_id=?'); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$records=$pdo->query('SELECT c.*, a.username AS added_username, u.username AS updated_username,
 (SELECT COUNT(*) FROM devices d WHERE d.customer_id=c.customer_id) AS device_count
 FROM customers c LEFT JOIN users a ON a.user_id=c.added_by LEFT JOIN users u ON u.user_id=c.updated_by ORDER BY c.customer_id DESC')->fetchAll();
page_header('Customer Management');
if ($error) echo '<div class="error" role="alert">'.h($error).'</div>';
$deviceTotal = array_sum(array_map(static fn($row) => (int)$row['device_count'], $records));
?>
<div class="metrics" aria-label="Customer overview">
    <article class="metric"><span class="metric-icon"><?= ui_icon('users', 22) ?></span><div><div class="metric-label">Total customers</div><div class="metric-value"><?= count($records) ?></div></div></article>
    <article class="metric"><span class="metric-icon taupe"><?= ui_icon('monitor', 22) ?></span><div><div class="metric-label">Registered devices</div><div class="metric-value"><?= $deviceTotal ?></div></div></article>
    <article class="metric"><span class="metric-icon dark"><?= ui_icon('shield', 21) ?></span><div><div class="metric-label">Logged-in user</div><div class="metric-value small"><?= h($_SESSION['username'] ?? '') ?></div></div></article>
</div>
<div class="workspace-grid">
    <section class="panel" aria-labelledby="customer-form-heading">
        <div class="panel-heading"><div><div class="section-overline">Customer information</div><h2 id="customer-form-heading"><?= $edit ? 'Edit customer' : 'New customer' ?></h2><p><?= $edit ? 'Update their details below.' : 'Enter customer information here.' ?></p></div><span class="panel-counter"><?= $edit ? 'EDIT' : 'NEW' ?></span></div>
        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
            <input type="hidden" name="id" value="<?= h($edit['customer_id'] ?? '') ?>">
            <label>Customer username<input name="username" maxlength="50" placeholder="e.g. juandelacruz" value="<?= h($edit['username'] ?? '') ?>"></label>
            <label>First name<input name="first_name" maxlength="50" required placeholder="Enter first name" value="<?= h($edit['first_name'] ?? '') ?>"></label>
            <label>Last name<input name="last_name" maxlength="50" required placeholder="Enter last name" value="<?= h($edit['last_name'] ?? '') ?>"></label>
            <label>Date of birth<input type="date" name="date_of_birth" value="<?= h($edit['date_of_birth'] ?? '') ?>"></label>
            <label>Contact number<input name="contact_number" maxlength="50" placeholder="e.g. 09XX XXX XXXX" value="<?= h($edit['contact_number'] ?? '') ?>"></label>
            <div class="form-actions"><button class="primary-btn" type="submit"><?= ui_icon($edit ? 'check' : 'plus', 16) ?> <?= $edit ? 'Save changes' : 'Add customer' ?></button><?php if ($edit): ?><a href="index.php" class="button secondary">Cancel</a><?php endif; ?></div>
        </form>
        <p class="hint">The system saves who added or updated each customer.</p>
    </section>
    <section class="panel" aria-labelledby="customer-list-heading">
        <div class="panel-heading"><div><div class="section-overline">Directory</div><h2 id="customer-list-heading">Registered customers</h2><p>View customer records and manage their devices.</p></div><span class="panel-counter"><?= count($records) ?> total</span></div>
        <div class="table-tools"><label class="search-field"><span class="search-label">Search customers</span><input type="search" placeholder="Search by name, contact or username..." data-table-search="customer-table"></label></div>
        <div class="table-scroll"><table id="customer-table"><thead><tr><th>ID</th><th>Customer</th><th>Contact</th><th>Devices</th><th>Added by</th><th>Last updated by</th><th>Updated at</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($records as $c): ?>
            <tr data-search-row>
                <td class="id-cell">#<?= h($c['customer_id']) ?></td>
                <td><span class="cell-primary"><?= h($c['first_name'].' '.$c['last_name']) ?></span><span class="cell-sub">@<?= h($c['username'] ?: 'no username') ?></span></td>
                <td><?= h($c['contact_number'] ?: '—') ?></td>
                <td><span class="counter-tag"><?= h($c['device_count']) ?> devices</span></td>
                <td><?= h($c['added_username'] ?? 'Old record') ?></td>
                <td><?= h($c['updated_username'] ?? 'Old record') ?></td>
                <td><?= h($c['last_updated'] ?: '—') ?></td>
                <td><div class="actions">
                    <a class="button" href="devices.php?customer_id=<?= h($c['customer_id']) ?>"><?= ui_icon('monitor', 12) ?> Devices</a>
                    <a class="button orange" href="index.php?edit=<?= h($c['customer_id']) ?>"><?= ui_icon('edit', 12) ?> Edit</a>
                    <form method="post" onsubmit="return confirm('Delete customer AND all their devices?')">
                        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= h($c['customer_id']) ?>">
                        <button class="danger" type="submit"><?= ui_icon('trash', 12) ?> Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($records): ?><tr data-no-match hidden><td colspan="8" class="no-matches">No matching customers found.</td></tr><?php endif; ?>
        <?php if (!$records): ?><tr><td colspan="8"><div class="empty-state"><span class="empty-mark"><?= ui_icon('users', 23) ?></span><h3>No customers yet</h3><p>Use the form to create your first customer record.</p></div></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php page_footer(); ?>
