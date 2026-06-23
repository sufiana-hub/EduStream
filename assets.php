<?php
session_start();
include 'db.php';

// Handle New Asset Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_asset'])) {
    $asset_id = $_POST['asset_id'];
    $title = $_POST['title'];
    $group_id = $_POST['group_id'];
    $mandatory = $_POST['mandatory'];

    // Handle the File Upload
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_upload']['tmp_name'];
        $file_name = $_FILES['file_upload']['name'];
        
        // Extract the file extension (e.g., pdf, mp4)
        $file_type = strtoupper(pathinfo($file_name, PATHINFO_EXTENSION)); 
        
        // Read the file contents as binary data (BLOB)
        $file_data = file_get_contents($file_tmp);

        // Insert into database
        $insert_sql = "INSERT INTO assets (asset_id, title, group_id, file_type, mandatory, file_data) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($insert_sql)) {
            $stmt->bind_param("ssssss", $asset_id, $title, $group_id, $file_type, $mandatory, $file_data);
            $stmt->execute();
            $stmt->close();
        }
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
$asset_result = $conn->query("SELECT asset_id, title, group_id, file_type, mandatory FROM assets");
if ($asset_result) {
    while ($row = $asset_result->fetch_assoc()) {
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
        .form-container { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 25px; margin-bottom: 40px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; }
        label { color: #aaa; margin-bottom: 8px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; }
        input[type="text"], select, input[type="file"] { background: #161616; border: 1px solid #333; color: white; padding: 12px; border-radius: 5px; width: 250px; font-size: 1rem; }
        input[type="text"]:focus, select:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background: #00a8cc; }

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
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<h2 class="section-title">Add New Asset</h2>
<div class="form-container">
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label>Asset ID</label>
                <input type="text" name="asset_id" placeholder="e.g. A001" required>
            </div>
            <div class="form-group">
                <label>Asset Title</label>
                <input type="text" name="title" placeholder="e.g. Safety Video" required>
            </div>
            <div class="form-group">
                <label>Upload File (Blob Data)</label>
                <input type="file" name="file_upload" required style="padding: 9px;">
            </div>
        </div>
        <div class="form-row">
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
                    <td>
                        <span class="<?php echo $asset['mandatory'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                            <?php echo htmlspecialchars($asset['mandatory']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($assets)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #aaa;">No assets uploaded yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>