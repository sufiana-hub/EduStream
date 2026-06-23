<?php
session_start();
include 'db.php';

// Check if a new group is being submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_group'])) {
    $group_id = $_POST['group_id'];
    $group_name = $_POST['group_name'];

    // Insert into database
    $insert_sql = "INSERT INTO groupdb (groupID, groupName) VALUES (?, ?)";
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("ss", $group_id, $group_name);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all existing groups from the database
$groups = [];
$result = $conn->query("SELECT groupID, groupName FROM groupdb");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Groups Management | EduStream</title>
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }

        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; border-left: 5px solid #00d2ff; padding-left: 10px; }
        
        /* Form Styling */
        .form-container { background: rgba(255,255,255,0.02); border: 1px solid #444; border-radius: 12px; padding: 25px; margin-bottom: 40px; }
        .form-group { margin-bottom: 15px; display: inline-block; margin-right: 20px; }
        label { display: block; color: #aaa; margin-bottom: 5px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; }
        input[type="text"] { background: #161616; border: 1px solid #333; color: white; padding: 12px; border-radius: 5px; width: 250px; font-size: 1rem; }
        input[type="text"]:focus { border-color: #00d2ff; outline: none; }
        .submit-btn { background: #00d2ff; color: #000; font-weight: bold; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background: #00a8cc; }

        /* Table Styling */
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 25px; border-bottom: 1px solid #333; font-size: 1.1rem; }
        th { background: #161616; color: #00d2ff; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.04); transition: 0.2s; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">Groups Management</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Manage multimedia asset groups such as EduStream Team</p>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn">Dashboard</a>
    <a href="groups.php" class="nav-btn active">Groups Management</a>
    <a href="assets.php" class="nav-btn">Assets Management</a>
    <a href="tags.php" class="nav-btn">Tags Management</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<h2 class="section-title">Add New Group</h2>
<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label>Group ID</label>
            <input type="text" name="group_id" placeholder="e.g. GW10" required>
        </div>
        <div class="form-group">
            <label>Group Name</label>
            <input type="text" name="group_name" placeholder="e.g. Media Learning Team" required>
        </div>
        <button type="submit" name="add_group" class="submit-btn">Save Group</button>
    </form>
</div>

<h2 class="section-title">Groups List</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">#</th>
                <th style="width: 200px;">Group ID</th>
                <th>Group Name</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            foreach ($groups as $grp): 
            ?>
                <tr>
                    <td style="color: #aaa; font-weight: bold;">#<?php echo $counter++; ?></td>
                    <td style="color: #00d2ff; font-weight: bold;"><?php echo htmlspecialchars($grp['groupID']); ?></td>
                    <td><?php echo htmlspecialchars($grp['groupName']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>