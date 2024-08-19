<?php
require_once 'GeoGuard.php';

$geoguard = new GeoGuard();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $new_settings = [
            'allowed_attempts' => intval($_POST['allowed_attempts']),
            'ip2location_api_key' => $_POST['ip2location_api_key'],
            'reset_time' => intval($_POST['reset_time']),
            'login_failed_delay' => intval($_POST['login_failed_delay']),
            'inform_user' => isset($_POST['inform_user']),
            'send_email' => isset($_POST['send_email']),
            '403_message' => $_POST['403_message'],
            'admin_ip' => $_POST['admin_ip'],
            'htaccess_dir' => $_POST['htaccess_dir'],
        ];
        $geoguard->updateSettings($new_settings);
        
        $blocked_countries = explode(',', $_POST['blocked_countries']);
        $blocked_countries = array_map('trim', $blocked_countries);
        $geoguard->setBlockedCountries($blocked_countries);
        
        $message = "Settings updated successfully.";
    }
    
    if (isset($_POST['block_ip'])) {
        $geoguard->blockIP($_POST['ip_to_block'], 'Manually blocked');
        $message = "IP {$_POST['ip_to_block']} has been blocked.";
    }
    
    if (isset($_POST['ip_to_unblock'])) {
        $geoguard->unblockIP($_POST['ip_to_unblock']);
        $message = "IP {$_POST['ip_to_unblock']} has been unblocked.";
    }

}

$settings = $geoguard->getSettings();
$denied_ips = $geoguard->getDeniedIPs();
$htaccess_status = $geoguard->checkHtaccessRequirements();
$attempts = $geoguard->getAttempts();
$whitelisted = $geoguard->getWhitelist();

?>

<?php if (isset($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<style>
    h2 { background-color: #f5f7fb; padding: 5px 5px 5px 10px; }
    .wrap { max-width: 600px; margin: 0 auto;   border: 1px solid #f5f7fb; border-radius: 5px; margin-bottom: 20px;}
    .body { padding: 20px; }
    label { display: block; margin-top: 10px; }
    input[type="text"], input[type="number"], input[type="password"], textarea { width: 100%; padding: 5px; }
    input[type="checkbox"] { margin-right: 5px; }
    input[type="submit"] { margin-top: 10px; padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
    input[type="submit"]:hover { background-color: #45a049; }
    .message { background-color: #f2f2f2; padding: 10px; margin-bottom: 20px; }
</style>

<div class="wrap">
    <h2>General Settings</h2>
    <div class="body">
        <form method="post">

            <label for="allowed_attempts">Allowed Attempts:</label>
            <input type="number" id="allowed_attempts" name="allowed_attempts" value="<?php echo $settings['allowed_attempts']; ?>" required>

            <label for="ip2location_api_key">IP2Location API Key (required for geolocation):</label>
            <input type="password" id="ip2location_api_key" name="ip2location_api_key" value="<?php echo $settings['ip2location_api_key']; ?>" required>

            <label for="reset_time">Reset Time (minutes):</label>
            <input type="number" id="reset_time" name="reset_time" value="<?php echo $settings['reset_time']; ?>" required>

            <label for="login_failed_delay">Login Failed Delay (seconds):</label>
            <input type="number" id="login_failed_delay" name="login_failed_delay" value="<?php echo $settings['login_failed_delay']; ?>" required>

            <label>
                <input type="checkbox" name="inform_user" <?php echo $settings['inform_user'] ? 'checked' : ''; ?>>
                Inform User
            </label>

            <label>
                <input type="checkbox" name="send_email" <?php echo $settings['send_email'] ? 'checked' : ''; ?>>
                Send Email
            </label>

            <label for="403_message">403 Message:</label>
            <textarea id="403_message" name="403_message" rows="3"><?php echo $settings['403_message']; ?></textarea>

            <label for="admin_ip">Admin IP (automatically whitelisted):</label>
            <input type="text" id="admin_ip" name="admin_ip" value="<?php echo $settings['admin_ip']; ?>">

            <label for="htaccess_dir">Htaccess Directory:</label>
            <input type="text" id="htaccess_dir" name="htaccess_dir" value="<?php echo $settings['htaccess_dir']; ?>" required>

            <label for="blocked_countries">Geolocation - Blocked Countries (comma-separated):</label>
            <input type="text" id="blocked_countries" name="blocked_countries" value="<?php echo implode(', ', $settings['blocked_countries']); ?>">

            <input type="submit" class="btn btn-primary" name="update_settings" value="Update Settings">
        </form>
    </div>
</div>
<div class="wrap">
    <h2>Whitelisted IPs</h2>
    <div class="body">
        <table border="1" class="table table-striped">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($whitelisted)): ?>
                    <?php foreach ($whitelisted as $ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="ip_to_remove" value="<?php echo htmlspecialchars($ip); ?>">
                                    <button class="btn btn-primary" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>No Whitelisted IPs.</td>
                        <td></td>
                        
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="wrap">
    <h2>Manually Whitelist IP</h2>
    <div class="body">
        <form method="post">


            <label for="ip_to_whitelist">IP to Whitelist (Never Block):</label>
            <input type="text" id="ip_to_whitelist" name="ip_to_block">
            <input type="submit" class="btn btn-primary" name="whitelist_ip" value="Whitelist IP">

        </form>
    </div>
</div>
<div class="wrap">
    <h2>Blocked IPs</h2>
    <div class="body">
        <table border="1" class="table table-striped">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($denied_ips)): ?>
                    <?php foreach ($denied_ips as $ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="ip_to_unblock" value="<?php echo htmlspecialchars($ip); ?>">
                                    <button class="btn btn-primary" type="submit">Unblock</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>No blocked IPs.</td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="wrap">
    <h2>Manually Block IP</h2>
    <div class="body">
        <form method="post">


            <label for="ip_to_block">IP to Block:</label>
            <input type="text" id="ip_to_block" name="ip_to_block">
            <input type="submit" class="btn btn-primary" name="block_ip" value="Block IP">

        </form>
    </div>
</div>
<div class="wrap">
    <h2>Login Attempts</h2>
    <div class="body">
        <table border="1" class="table table-striped">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Attempts</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($attempts)): ?>
                    <?php foreach ($attempts as $ip => $number): ?>
                        <tr>
                            <td><?php echo $ip; ?></td>
                            <td>
                                <?php echo $number; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>No logged attempts.</td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="wrap">
    <h2>Htaccess Status</h2>
    <div class="body">
        <p>Found: <?php echo $htaccess_status['found'] ? 'Yes' : 'No'; ?></p>
        <p>Readable: <?php echo $htaccess_status['readable'] ? 'Yes' : 'No'; ?></p>
        <p>Writable: <?php echo $htaccess_status['writeable'] ? 'Yes' : 'No'; ?></p>
    </div>
</div>
