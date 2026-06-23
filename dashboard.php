<?php
session_start();

// Get group name from URL or folder just like the lecturer's code
if (!isset($_GET['group'])) {
    $group = basename(dirname(__FILE__));
} else {
    $group = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['group']);
}

// In a real scenario, you would include your DB connection here to fetch real counts
// include '../../db.php'; 

// Mock data based on your PDF Progress Report
$total_groups = 5;
$total_assets = 24;
$total_specs = 24;
$total_tags = 12;

$recent_assets = [
    ['id' => 'A001', 'title' => 'Safety Video', 'group' => 'GW09', 'type' => 'MP4', 'mandatory' => 'Yes'],
    ['id' => 'A002', 'title' => 'AI Ethics Notes', 'group' => 'GW09', 'type' => 'PDF', 'mandatory' => 'No']
];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | EduStream <?php echo htmlspecialchars($group); ?></title>
    <style>
        /* Base styles from your lecturer's provided code */
        body { background: #0f0f0f; color: white; font-family: sans-serif; padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        
        /* Navigation/Tabs styling */
        .nav-menu { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn { padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; background: #222; color: #aaa; transition: 0.3s; border: 1px solid #333; }
        .nav-btn.active, .nav-btn:hover { background: #00d2ff; color: #000; border-color: #00d2ff; }

        /* Dashboard Summary Cards */
        .dashboard-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: rgba(255,255,255,0.02); border: 1px solid #444; border-left: 5px solid #00d2ff; border-radius: 8px; padding: 25px; }
        .stat-title { font-size: 1rem; color: #aaa; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 2.5rem; font-weight: bold; color: white; }

        /* Table Styling (Adapted from lecturer's code) */
        .section-title { font-size: 1.5rem; margin-bottom: 20px; color: white; }
        .table-container { border: 1px solid #444; border-radius: 12px; overflow: hidden; background: rgba(255,255,255,0.02); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 25px; border-bottom: 1px solid #333; font-size: 1.1rem; }
        th { background: #161616; color: #00d2ff; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.04); transition: 0.2s; }
        
        /* Badges for Mandatory status */
        .badge-yes { background: rgba(0, 210, 255, 0.2); color: #00d2ff; padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
        .badge-no { background: rgba(255, 255, 255, 0.1); color: #aaa; padding: 5px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1 style="margin: 0; color: #00d2ff;">EduStream Admin</h1>
        <p style="margin: 5px 0 0 0; color: #aaa;">Overview of multimedia assets, groups, technical specifications, and tags</p>
    </div>
    <div style="border: 1px solid #00d2ff; padding: 10px 25px; font-size: 1.6rem; border-radius: 5px; font-weight: bold;">
        GROUP: <?php echo htmlspecialchars($group); ?>
    </div>
</div>

<div class="nav-menu">
    <a href="dashboard.php" class="nav-btn active">Dashboard</a>
    <a href="groups.php" class="nav-btn">Groups Management</a>
    <a href="assets.php" class="nav-btn">Assets Management</a>
    <a href="tags.php" class="nav-btn">Tags Management</a>
    <a href="index.php" class="nav-btn" style="margin-left: auto;">View Members List</a>
</div>

<div class="dashboard-summary">
    <div class="stat-card">
        <div class="stat-title">Total Groups</div>
        <div class="stat-value"><?php echo $total_groups; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Total Assets</div>
        <div class="stat-value"><?php echo $total_assets; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Technical Specs</div>
        <div class="stat-value"><?php echo $total_specs; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Total Tags</div>
        <div class="stat-value"><?php echo $total_tags; ?></div>
    </div>
</div>

<h2 class="section-title">Recent Multimedia Assets</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Asset ID</th>
                <th>Title</th>
                <th>Group</th>
                <th>File Type</th>
                <th>Mandatory</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_assets as $asset): ?>
                <tr>
                    <td style="color: #00d2ff; font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($asset['id']); ?></td>
                    <td><?php echo htmlspecialchars($asset['title']); ?></td>
                    <td><?php echo htmlspecialchars($asset['group']); ?></td>
                    <td><?php echo htmlspecialchars($asset['type']); ?></td>
                    <td>
                        <span class="<?php echo $asset['mandatory'] === 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                            <?php echo htmlspecialchars($asset['mandatory']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>