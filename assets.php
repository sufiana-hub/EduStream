<?php
include 'db.php';
include 'keyword_utils.php';

$message = '';
$message_type = 'info';

function getMaxAllowedPacket($conn) {
    $result = $conn->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? (int) $row['Value'] : 0;
}

// Handle New Asset Form Submission
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['add_asset'])) {
    $title = $_POST['title'];
    $group_id = $_POST['group_id'];
    $mandatory = $_POST['mandatory'];
    $owner = $group_id;
    $description = trim($_POST['description'] ?? '');

    // Handle the File Upload
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_upload']['tmp_name'];
        $file_name = $_FILES['file_upload']['name'];
        $file_size = (int) $_FILES['file_upload']['size'];
        
        // Extract the file extension (e.g., pdf, mp4)
        $file_type = strtoupper(pathinfo($file_name, PATHINFO_EXTENSION)); 
        $mime_type = $_FILES['file_upload']['type'] ?: 'application/octet-stream';

        if (function_exists('mime_content_type')) {
            $detected_mime = mime_content_type($file_tmp);
            if ($detected_mime) {
                $mime_type = $detected_mime;
            }
        }

        $max_packet = getMaxAllowedPacket($conn);
        $safe_packet_limit = $max_packet > 0 ? (int) floor($max_packet * 0.75) : 0;

        if ($safe_packet_limit > 0 && $file_size > $safe_packet_limit) {
            $message = "Upload failed. The file is " . formatUploadBytes($file_size) . ", but this MySQL setup can safely store about " . formatUploadBytes($safe_packet_limit) . " per upload. Please upload a smaller file or increase MySQL max_allowed_packet.";
            $message_type = 'error';
        } else {
            // Read the file contents as binary data (BLOB)
            $file_data = file_get_contents($file_tmp);
            $search_text = buildSearchText($title, $file_name, $file_type, $description, $file_data);

            // Insert into database
            $insert_sql = "INSERT INTO assets
                (title, group_id, file_type, original_filename, file_size, mime_type, owner, description, search_text, mandatory, file_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($insert_sql)) {
                try {
                    $stmt->bind_param("ssssissssss", $title, $group_id, $file_type, $file_name, $file_size, $mime_type, $owner, $description, $search_text, $mandatory, $file_data);
                    $stmt->execute();
                    $asset_id = (string) $conn->insert_id;
                    $stmt->close();

                    $keywords = extractKeywordsFromAssetData($title, $file_name, $file_type, $file_data);
                    $savedCount = saveKeywordsAsTags($conn, $keywords, $asset_id);
                    $message = "Asset saved. $savedCount keyword/tag(s) extracted from the uploaded file.";
                    $message_type = 'info';
                } catch (mysqli_sql_exception $e) {
                    $stmt->close();
                    $message = "Upload failed because the file is too large for the current database packet limit. Please upload a smaller file or increase MySQL max_allowed_packet.";
                    $message_type = 'error';
                }
            }
        }
    } elseif (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = "Upload failed. Please check the file size and try again.";
        $message_type = 'error';
    }
}

// Fetch all existing groups for the dropdown menu
$groups = [];
$group_result = $conn->query("SELECT groupID, groupName FROM groupdb");
if ($group_result) {
    while ($row = $group_result->fetch_assoc()) {
        $groups[] = $row;
    }
}

// Fetch all assets to display in the table
$assets = [];
$asset_result = $conn->query("SELECT
        a.asset_id,
        a.title,
        a.group_id,
        a.file_type,
        a.original_filename,
        a.file_size,
        a.mime_type,
        a.upload_date,
        a.owner,
        a.mandatory,
        g.groupName,
        COALESCE(CONCAT(g.groupID, ' - ', g.groupName), a.group_id) AS owner_group
    FROM assets a
    LEFT JOIN groupdb g ON g.groupID = a.group_id
    ORDER BY a.upload_date DESC, a.asset_id DESC");
if ($asset_result) {
    while ($row = $asset_result->fetch_assoc()) {
        $row['tags'] = getAssetTags($conn, $row['asset_id']);
        $assets[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Assets Management | EduStream</title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }

        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; border-left: 5px solid #00d2ff; padding-left: 10px; }
        
        /* Form Styling */
        .form-container { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 30px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: repeat(3, minmax(240px, 1fr)); gap: 20px 24px; align-items: end; max-width: 1080px; }
        .form-group { display: flex; flex-direction: column; min-width: 0; }
        label { color: #c7c7c7; margin-bottom: 8px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; }
        input[type="text"], select, input[type="file"], textarea { box-sizing: border-box; background: #161616; border: 1px solid #333; color: white; padding: 12px 15px; border-radius: 6px; width: 100%; min-height: 56px; font-size: 1rem; }
        input[type="file"] { display: flex; align-items: center; padding: 13px 15px; }
        textarea { resize: vertical; min-height: 96px; }
        input[type="text"]:focus, select:focus, textarea:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 0 30px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; height: 52px; width: fit-content; min-width: 150px; margin-top: 24px; }
        .submit-btn:hover { background: #00a8cc; }
        .message { background: rgba(0, 210, 255, 0.1); border: 1px solid rgba(0, 210, 255, 0.35); color: #bff4ff; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
        .message.error { background: rgba(255, 80, 80, 0.1); border-color: rgba(255, 80, 80, 0.45); color: #ffb7b7; }
        .tag-pill { display: inline-block; background: rgba(0, 210, 255, 0.1); color: #00d2ff; padding: 5px 10px; border-radius: 999px; border: 1px solid rgba(0, 210, 255, 0.3); font-family: monospace; margin: 2px; }
        @media (max-width: 900px) {
            .form-grid { grid-template-columns: repeat(2, minmax(220px, 1fr)); }
        }
        @media (max-width: 600px) {
            body { padding: 24px; }
            .nav-menu { flex-direction: column; }
            .nav-btn { text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
            .submit-btn { width: 100%; }
        }

        /* Table Styling */
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 25px; border-bottom: 1px solid #333; font-size: 1.1rem; }
        th { background: #161616; color: #00d2ff; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.04); transition: 0.2s; }
        
        /* Badges */
        .badge-yes { background: rgba(0, 210, 255, 0.2); color: #00d2ff; padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
        .badge-no { background: rgba(255, 255, 255, 0.1); color: #aaa; padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">Assets Management</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Upload and manage multimedia such as PDF, MP4, and BlobData</p>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="groups.php" class="nav-btn">Groups Management</a>
    <a href="assets.php" class="nav-btn active">Assets Management</a>
    <a href="tags.php" class="nav-btn">Tags Management</a>
    <a href="keyword_extractor.php" class="nav-btn">Keyword Extractor</a>
    <a href="search.php" class="nav-btn">Search</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<?php if ($message !== ''): ?>
    <div class="message <?php echo $message_type === 'error' ? 'error' : ''; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<h2 class="section-title">Add New Asset</h2>
<div class="form-container">
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-grid">

            <div class="form-group">
                <label>Asset Title</label>
                <input type="text" name="title" placeholder="e.g. Safety Video" required>
            </div>
            <div class="form-group">
                <label>Upload File (Blob Data)</label>
                <input type="file" name="file_upload" required>
            </div>
            <div class="form-group">
                <label>Select Group</label>
                <select name="group_id" required>
                    <option value="" disabled selected>-- Choose Group --</option>
                    <?php foreach ($groups as $grp): ?>
                        <option value="<?php echo htmlspecialchars($grp['groupID']); ?>">
                            <?php echo htmlspecialchars($grp['groupID'] . ' - ' . $grp['groupName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Mandatory Asset</label>
                <select name="mandatory" required>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Optional description for search and keyword extraction"></textarea>
            </div>
        </div>
        <button type="submit" name="add_asset" class="submit-btn">Save Asset</button>
    </form>
</div>

<h2 class="section-title">Assets List</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Asset ID</th>
                <th>Title</th>
                <th>Group</th>
                <th>File Type</th>
                <th>Filename</th>
                <th>Size</th>
                <th>MIME</th>
                <th>Owner</th>
                <th>Uploaded</th>
                <th>Tags</th>
                <th>Mandatory</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            foreach ($assets as $asset): 
            ?>
                <tr>
                    <td style="color: #aaa; font-weight: bold;">#<?php echo $counter++; ?></td>
                    <td style="color: #00d2ff; font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($asset['asset_id']); ?></td>
                    <td><?php echo htmlspecialchars($asset['title']); ?></td>
                    <td><?php echo htmlspecialchars($asset['group_id']); ?></td>
                    <td><?php echo htmlspecialchars($asset['file_type']); ?></td>
                    <td><?php echo htmlspecialchars($asset['original_filename'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['file_size'] ? formatUploadBytes($asset['file_size']) : '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['mime_type'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['owner_group'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['upload_date'] ?: '-'); ?></td>
                    <td>
                        <?php if (!empty($asset['tags'])): ?>
                            <?php foreach ($asset['tags'] as $tag): ?>
                                <span class="tag-pill">#<?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color: #aaa;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="<?php echo $asset['mandatory'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                            <?php echo htmlspecialchars($asset['mandatory']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($assets)): ?>
                <tr>
                    <td colspan="12" style="text-align: center; color: #aaa;">No assets uploaded yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>



