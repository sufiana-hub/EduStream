<?php
include 'db.php';
include 'keyword_utils.php';

$message = '';

// Handle New Tag Form Submission
if (($_SERVER["REQUEST_METHOD"] ?? '') == "POST" && isset($_POST['add_tag'])) {
    $savedCount = saveKeywordsAsTags($conn, [$_POST['keyword'] => 1]);
    $message = $savedCount > 0 ? 'Keyword/tag saved.' : 'Keyword/tag already exists or is not valid.';
}

// Fetch all existing tags
$tags = [];
$result = $conn->query("SELECT tag_id, keyword FROM tags");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Tags Management | EduStream</title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }

        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; border-left: 5px solid #00d2ff; padding-left: 10px; }
        
        /* Form Styling */
        .form-container { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 30px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: minmax(260px, 420px) auto; gap: 20px 24px; align-items: end; }
        .form-group { display: flex; flex-direction: column; min-width: 0; }
        label { color: #c7c7c7; margin-bottom: 8px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; }
        input[type="text"] { box-sizing: border-box; background: #161616; border: 1px solid #333; color: white; padding: 12px 15px; border-radius: 6px; width: 100%; height: 56px; font-size: 1rem; }
        input[type="text"]:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 0 30px; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; height: 52px; width: fit-content; min-width: 140px; }
        .submit-btn:hover { background: #00a8cc; }
        .message { background: rgba(0, 210, 255, 0.1); border: 1px solid rgba(0, 210, 255, 0.35); color: #bff4ff; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
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

        /* Tag Pill Styling */
        .tag-pill { background: rgba(0, 210, 255, 0.1); color: #00d2ff; padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(0, 210, 255, 0.3); font-weight: bold; font-family: monospace; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">Tags Management</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Create searchable keyword tags for multimedia assets</p>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="groups.php" class="nav-btn">Groups Management</a>
    <a href="assets.php" class="nav-btn">Assets Management</a>
    <a href="tags.php" class="nav-btn active">Tags Management</a>
    <a href="keyword_extractor.php" class="nav-btn">Keyword Extractor</a>
    <a href="search.php" class="nav-btn">Search</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<?php if ($message !== ''): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<h2 class="section-title">Add New Tag</h2>
<div class="form-container">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Keyword</label>
                <input type="text" name="keyword" placeholder="e.g. Safety" required>
            </div>
            <button type="submit" name="add_tag" class="submit-btn">Save Tag</button>
        </div>
    </form>
</div>

<h2 class="section-title">Tags List</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">#</th>
                <th style="width: 200px;">Tag ID</th>
                <th>Keyword</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            foreach ($tags as $tag): 
            ?>
                <tr>
                    <td style="color: #aaa; font-weight: bold;">#<?php echo $counter++; ?></td>
                    <td style="color: #aaa; font-family: monospace;"><?php echo htmlspecialchars($tag['tag_id']); ?></td>
                    <td>
                        <span class="tag-pill">#<?php echo htmlspecialchars($tag['keyword']); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($tags)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #aaa;">No tags created yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>


