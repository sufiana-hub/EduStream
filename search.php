<?php
include 'db.php';
include 'keyword_utils.php';

$query = trim($_GET['q'] ?? '');
$group_id = trim($_GET['group_id'] ?? '');
$file_type = trim($_GET['file_type'] ?? '');
$tag_id = trim($_GET['tag_id'] ?? '');

$groups = [];
$group_result = $conn->query("SELECT groupID, groupName FROM groupdb ORDER BY groupID");
while ($group_result && $row = $group_result->fetch_assoc()) {
    $groups[] = $row;
}

$file_types = [];
$type_result = $conn->query("SELECT DISTINCT file_type FROM assets WHERE file_type IS NOT NULL AND file_type <> '' ORDER BY file_type");
while ($type_result && $row = $type_result->fetch_assoc()) {
    $file_types[] = $row['file_type'];
}

$tags = [];
$tag_result = $conn->query("SELECT tag_id, keyword FROM tags ORDER BY keyword");
while ($tag_result && $row = $tag_result->fetch_assoc()) {
    $tags[] = $row;
}

$where = [];
$types = '';
$params = [];

if ($query !== '') {
    $term = '%' . $query . '%';
    $where[] = "(a.title LIKE ? OR a.original_filename LIKE ? OR a.description LIKE ? OR a.search_text LIKE ? OR t.keyword LIKE ?)";
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if ($group_id !== '') {
    $where[] = "a.group_id = ?";
    $types .= 's';
    $params[] = $group_id;
}

if ($file_type !== '') {
    $where[] = "a.file_type = ?";
    $types .= 's';
    $params[] = $file_type;
}

if ($tag_id !== '') {
    $where[] = "atg.tag_id = ?";
    $types .= 's';
    $params[] = $tag_id;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT
            a.asset_id,
            a.title,
            a.group_id,
            a.file_type,
            a.original_filename,
            a.file_size,
            a.mime_type,
            a.upload_date,
            a.owner,
            COALESCE(CONCAT(g.groupID, ' - ', g.groupName), a.group_id) AS owner_group,
            a.description,
            a.mandatory,
            GROUP_CONCAT(DISTINCT t.keyword ORDER BY t.keyword SEPARATOR ', ') AS tags
        FROM assets a
        LEFT JOIN groupdb g ON g.groupID = a.group_id
        LEFT JOIN asset_tags atg ON atg.asset_id = a.asset_id
        LEFT JOIN tags t ON t.tag_id = atg.tag_id
        $where_sql
        GROUP BY a.asset_id, a.title, a.group_id, a.file_type, a.original_filename, a.file_size, a.mime_type, a.upload_date, a.owner, g.groupID, g.groupName, a.description, a.mandatory
        ORDER BY a.upload_date DESC, a.asset_id DESC";

$results = [];
if ($stmt = $conn->prepare($sql)) {
    if ($types !== '') {
        $refs = [$types];
        foreach ($params as $key => $value) {
            $refs[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && $row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Search | EduStream</title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }
        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; border-left: 5px solid #00d2ff; padding-left: 10px; }
        .panel { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 30px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: repeat(5, minmax(180px, 1fr)); gap: 18px; align-items: end; }
        label { color: #c7c7c7; margin-bottom: 8px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; display: block; }
        input, select { box-sizing: border-box; background: #161616; border: 1px solid #333; color: white; padding: 12px 15px; border-radius: 6px; width: 100%; height: 52px; font-size: 1rem; }
        input:focus, select:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 0 28px; border: none; border-radius: 6px; cursor: pointer; height: 52px; }
        .clear-btn { display: inline-flex; align-items: center; justify-content: center; background: #222; color: #ddd; border: 1px solid #444; text-decoration: none; border-radius: 6px; height: 52px; padding: 0 24px; font-weight: bold; }
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: auto; background: rgba(255,255,255,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 1150px; }
        th, td { padding: 16px 18px; border-bottom: 1px solid #333; font-size: 1rem; vertical-align: top; }
        th { background: #161616; color: #00d2ff; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        .tag-pill { display: inline-block; background: rgba(0, 210, 255, 0.1); color: #00d2ff; padding: 5px 10px; border-radius: 999px; border: 1px solid rgba(0, 210, 255, 0.3); font-family: monospace; margin: 2px; }
        .muted { color: #aaa; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">Search Assets</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Search by title, file details, extracted keyword/tag, group, and file type</p>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="groups.php" class="nav-btn">Groups Management</a>
    <a href="assets.php" class="nav-btn">Assets Management</a>
    <a href="tags.php" class="nav-btn">Tags Management</a>
    <a href="keyword_extractor.php" class="nav-btn">Keyword Extractor</a>
    <a href="search.php" class="nav-btn active">Search</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<h2 class="section-title">Search Filters</h2>
<div class="panel">
    <form method="GET" action="search.php">
        <div class="form-grid">
            <div>
                <label>Keyword / Text</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="e.g. assignment">
            </div>
            <div>
                <label>Group</label>
                <select name="group_id">
                    <option value="">Any group</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group['groupID']); ?>" <?php echo $group_id === $group['groupID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['groupID']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>File Type</label>
                <select name="file_type">
                    <option value="">Any type</option>
                    <?php foreach ($file_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $file_type === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tag / Keyword</label>
                <select name="tag_id">
                    <option value="">Any tag</option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo htmlspecialchars($tag['tag_id']); ?>" <?php echo $tag_id === (string) $tag['tag_id'] ? 'selected' : ''; ?>>
                            #<?php echo htmlspecialchars($tag['keyword']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="submit-btn">Search</button>
                <a href="search.php" class="clear-btn">Clear</a>
            </div>
        </div>
    </form>
</div>

<h2 class="section-title">Results: <?php echo count($results); ?></h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset</th>
                <th>File Metadata</th>
                <th>Description</th>
                <th>Tags / Keywords</th>
                <th>Mandatory</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $index => $asset): ?>
                <tr>
                    <td class="muted">#<?php echo $index + 1; ?></td>
                    <td>
                        <strong style="color:#00d2ff;"><?php echo htmlspecialchars($asset['asset_id']); ?></strong><br>
                        <?php echo htmlspecialchars($asset['title']); ?><br>
                        <span class="muted"><?php echo htmlspecialchars($asset['original_filename'] ?: '-'); ?></span>
                    </td>
                    <td>
                        Group: <?php echo htmlspecialchars($asset['group_id']); ?><br>
                        Type: <?php echo htmlspecialchars($asset['file_type']); ?><br>
                        MIME: <?php echo htmlspecialchars($asset['mime_type'] ?: '-'); ?><br>
                        Size: <?php echo htmlspecialchars($asset['file_size'] ? formatUploadBytes($asset['file_size']) : '-'); ?><br>
                        Owner: <?php echo htmlspecialchars($asset['owner_group'] ?: '-'); ?><br>
                        Uploaded: <?php echo htmlspecialchars($asset['upload_date'] ?: '-'); ?>
                    </td>
                    <td><?php echo htmlspecialchars($asset['description'] ?: '-'); ?></td>
                    <td>
                        <?php if (!empty($asset['tags'])): ?>
                            <?php foreach (explode(', ', $asset['tags']) as $tag): ?>
                                <span class="tag-pill">#<?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="muted">No tags</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($asset['mandatory']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #aaa; padding: 32px;">No matching assets found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
