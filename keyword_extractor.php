<?php
include 'db.php';
include 'keyword_utils.php';

$message = '';
$selectedAsset = null;
$keywords = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_keywords'])) {
    $selectedKeywords = $_POST['keywords'] ?? [];
    $assetId = $_POST['asset_id'] ?? null;
    $keywordsToSave = [];

    foreach ($selectedKeywords as $keyword) {
        $keywordsToSave[$keyword] = 1;
    }

    $savedCount = saveKeywordsAsTags($conn, $keywordsToSave, $assetId);
    $message = $savedCount > 0 ? "$savedCount keyword(s) saved as tags." : "No new keywords were saved. They may already exist.";
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['extract_keywords'])) {
    $assetId = $_POST['asset_id'] ?? '';
    $assetSql = "SELECT asset_id, title, group_id, file_type, original_filename, file_data FROM assets WHERE asset_id = ? LIMIT 1";

    if ($stmt = $conn->prepare($assetSql)) {
        $stmt->bind_param("s", $assetId);
        $stmt->execute();
        $result = $stmt->get_result();
        $selectedAsset = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }

    if ($selectedAsset) {
        $keywords = extractKeywordsFromAssetData($selectedAsset['title'], $selectedAsset['original_filename'] ?? '', $selectedAsset['file_type'], $selectedAsset['file_data']);
    } else {
        $message = 'Selected asset was not found.';
    }
}

$assets = [];
$assetResult = $conn->query("SELECT asset_id, title, group_id, file_type FROM assets ORDER BY asset_id DESC");
if ($assetResult) {
    while ($row = $assetResult->fetch_assoc()) {
        $assets[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Keyword Extractor | EduStream</title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }
        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; border-left: 5px solid #00d2ff; padding-left: 10px; }
        .panel { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 30px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: minmax(260px, 520px) auto; gap: 20px 24px; align-items: end; }
        label { color: #c7c7c7; margin-bottom: 8px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; display: block; }
        select { box-sizing: border-box; background: #161616; border: 1px solid #333; color: white; padding: 12px 15px; border-radius: 6px; width: 100%; height: 56px; font-size: 1rem; }
        select:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 0 30px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; height: 52px; width: fit-content; min-width: 160px; }
        .submit-btn:hover { background: #00a8cc; }
        .message { background: rgba(0, 210, 255, 0.1); border: 1px solid rgba(0, 210, 255, 0.35); color: #bff4ff; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
        .asset-summary { color: #aaa; margin: 0 0 24px; }
        .keyword-list { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .keyword-option { display: inline-flex; align-items: center; gap: 8px; background: rgba(0, 210, 255, 0.1); color: #00d2ff; border: 1px solid rgba(0, 210, 255, 0.3); border-radius: 999px; padding: 10px 14px; font-weight: bold; }
        .keyword-option input { accent-color: #00d2ff; }
        .empty-state { color: #aaa; margin: 0; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 25px; border-bottom: 1px solid #333; font-size: 1.1rem; }
        th { background: #161616; color: #00d2ff; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.04); transition: 0.2s; }
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.02); }
        @media (max-width: 700px) {
            body { padding: 24px; }
            .nav-menu { flex-direction: column; }
            .nav-btn { text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
            .submit-btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">Keyword Extractor</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Extract suggested searchable keywords from uploaded assets</p>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="groups.php" class="nav-btn">Groups Management</a>
    <a href="assets.php" class="nav-btn">Assets Management</a>
    <a href="tags.php" class="nav-btn">Tags Management</a>
    <a href="keyword_extractor.php" class="nav-btn active">Keyword Extractor</a>
    <a href="search.php" class="nav-btn">Search</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<?php if ($message !== ''): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<h2 class="section-title">Extract Keywords</h2>
<div class="panel">
    <form method="POST" action="">
        <div class="form-grid">
            <div>
                <label>Select Asset</label>
                <select name="asset_id" required>
                    <option value="" disabled selected>-- Choose Asset --</option>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo htmlspecialchars($asset['asset_id']); ?>">
                            <?php echo htmlspecialchars($asset['asset_id'] . ' - ' . $asset['title'] . ' (' . $asset['file_type'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="extract_keywords" class="submit-btn">Extract Keywords</button>
        </div>
    </form>
</div>

<?php if ($selectedAsset): ?>
    <h2 class="section-title">Suggested Keywords</h2>
    <div class="panel">
        <p class="asset-summary">
            Asset: <?php echo htmlspecialchars($selectedAsset['asset_id'] . ' - ' . $selectedAsset['title']); ?>
            | Type: <?php echo htmlspecialchars($selectedAsset['file_type']); ?>
            | Group: <?php echo htmlspecialchars($selectedAsset['group_id']); ?>
        </p>

        <?php if (!empty($keywords)): ?>
            <form method="POST" action="">
                <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars($selectedAsset['asset_id']); ?>">
                <div class="keyword-list">
                    <?php foreach ($keywords as $keyword => $count): ?>
                        <label class="keyword-option">
                            <input type="checkbox" name="keywords[]" value="<?php echo htmlspecialchars($keyword); ?>" checked>
                            #<?php echo htmlspecialchars($keyword); ?>
                            <span style="color: #aaa;">(<?php echo (int) $count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="save_keywords" class="submit-btn">Save Selected Tags</button>
            </form>
        <?php else: ?>
            <p class="empty-state">No strong keywords were found for this asset.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<h2 class="section-title">Available Assets</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Title</th>
                <th>Group</th>
                <th>File Type</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; foreach ($assets as $asset): ?>
                <tr>
                    <td style="color: #aaa; font-weight: bold;">#<?php echo $counter++; ?></td>
                    <td style="color: #00d2ff; font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($asset['asset_id']); ?></td>
                    <td><?php echo htmlspecialchars($asset['title']); ?></td>
                    <td><?php echo htmlspecialchars($asset['group_id']); ?></td>
                    <td><?php echo htmlspecialchars($asset['file_type']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #aaa;">No assets available yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
