<?php

require_once 'htaccess.php';

class GeoGuard {
    private $ip2location_api_key;
    private $htaccess;
    private $options;
    private $settings_file;
    private $attempts_file;

    public function __construct($settings_file = 'geoguard_settings.json', $attempts_file = 'geoguard_attempts.json') {
        $this->settings_file = dirname(__FILE__) . '/' . $settings_file;
        $this->attempts_file = dirname(__FILE__) . '/' . $attempts_file;
        $this->loadSettings();
        $this->initializeHtaccess();
    }
    
    public function performIPCheck() {
        $visitor_ip = $_SERVER['REMOTE_ADDR'];
        $admin_ip = $this->options['admin_ip'];

        if ($visitor_ip === $admin_ip) {
            return;
        }

        //Where is this attempt from?
        $geo_data = $this->getIPLocation($visitor_ip);
        
        sleep($this->options['login_failed_delay']);
        
        //Should block by country?
        if ($this->shouldBlockIP($geo_data)) {
            $this->blockIP($visitor_ip, 'Blocked country: ' . $geo_data['country_name']);
            die('Access denied based on your location.');
        }

        //Multiple Attempts?
        if ($this->isSuspiciousActivity($visitor_ip)) {
            $this->blockIP($visitor_ip, 'Suspicious activity');
            die($this->options['403_message']);
        }
    }

    public function loadSettings() {
        if (file_exists($this->settings_file)) {
            $this->options = json_decode(file_get_contents($this->settings_file), true);
        } else {
            $this->setDefaultOptions();
            $this->saveSettings();
        }
        $this->ip2location_api_key = $this->options['ip2location_api_key'];
    }

    public function saveSettings() {
        file_put_contents($this->settings_file, json_encode($this->options, JSON_PRETTY_PRINT));
    }

    public function setDefaultOptions() {
        $this->options = [
            'allowed_attempts' => 20,
            'ip2location_api_key' => '',
            'reset_time' => 60,
            'login_failed_delay' => 1,
            'inform_user' => true,
            'send_email' => false,
            '403_message' => 'Access denied due to suspicious activity.',
            'admin_ip' => '',
            'htaccess_dir' => '/home/username/public_html/', 
            'blocked_countries' => [],
            'whitelist' => []
        ];
    }

    public function initializeHtaccess() {
        $this->htaccess = new Htaccess();
        $this->htaccess->setPath($this->options['htaccess_dir']);
    }

    public function getIPLocation($ip) {
        $url = "https://api.ip2location.io/?ip={$ip}&key={$this->ip2location_api_key}";
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function shouldBlockIP($geo_data) {
        return in_array($geo_data['country_code'], $this->options['blocked_countries']);
    }

    public function isSuspiciousActivity($ip) {
        $attempts = $this->getAttempts($ip);

        if ($attempts >= $this->options['allowed_attempts']) {
            return true;
        }
        
        $this->incrementAttempts($ip);
        return false;
    }

    public function blockIP($ip, $reason) {
        $this->htaccess->denyIP($ip);
        $this->clearAttempts($ip);
        // Optionally log the reason for blocking
    }

    public function unblockIP($ip) {
        $this->clearAttempts($ip);
        return $this->htaccess->undenyIP($ip);
    }

    public function addToWhitelist($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $this->htaccess->undenyIP($ip);

        if (!isset($this->options['whitelist'])) {
            $this->options['whitelist'] = [];
        }

        $this->options['whitelist'][] = $ip;
        $this->options['whitelist'] = array_unique($this->options['whitelist']);
        $this->saveSettings();

        return true;
    }

    public function removeFromWhitelist($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!isset($this->options['whitelist'])) {
            return false;
        }

        $key = array_search($ip, $this->options['whitelist']);
        if ($key === false) {
            return false;
        }

        unset($this->options['whitelist'][$key]);
        $this->saveSettings();

        return true;
    }

    public function updateSettings($new_settings) {
        $this->options = array_merge($this->options, $new_settings);
        $this->saveSettings();
    }

    public function getDeniedIPs() {
        return $this->htaccess->getDeniedIPs();
    }

    public function set403Message($message) {
        return $this->htaccess->edit403Message($message);
    }

    public function commentGeoGuardLines() {
        return $this->htaccess->commentLines();
    }

    public function uncommentGeoGuardLines() {
        return $this->htaccess->uncommentLines();
    }

    public function checkHtaccessRequirements() {
        return $this->htaccess->checkRequirements();
    }

    public function getSettings() {
        return $this->options;
    }

    public function setBlockedCountries($countries) {
        $this->options['blocked_countries'] = $countries;
        $this->saveSettings();
    }

    public function getBlockedCountries() {
        return $this->options['blocked_countries'];
    }

    public function setAdminIP($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->options['admin_ip'] = $ip;
            $this->saveSettings();
            return true;
        }
        return false;
    }

    public function setIP2LocationAPIKey($key) {
        $this->options['ip2location_api_key'] = $key;
        $this->ip2location_api_key = $key;
        $this->saveSettings();
    }

    public function getWhitelist() {
        if (!file_exists($this->attempts_file)) {
            return 0;
        }

        $attempts = json_decode(file_get_contents($this->attempts_file), true);
        return $attempts['whitelist'];
    }
    
    public function getAttempts($ip = null) {

        if (!file_exists($this->attempts_file)) {
            return 0;
        }

        $attempts = json_decode(file_get_contents($this->attempts_file), true);
        if ($ip) return $attempts[$ip] ?? 0; // checks for attempts, otherwise returns all attempts
        unset ($attempts['whitelist']); // removes whitelist from 
        return $attempts;
    }

    public function incrementAttempts($ip = NULL) {
        
        if ($ip == NULL) $ip = $_SERVER['REMOTE_ADDR'];
        
        $attempts = file_exists($this->attempts_file) ? json_decode(file_get_contents($this->attempts_file), true) : [];
        $attempts[$ip] = ($attempts[$ip] ?? 0) + 1;
        file_put_contents($this->attempts_file, json_encode($attempts, JSON_PRETTY_PRINT));
    }

    public function clearAttempts($ip = NULL) {
        
        if ($ip == NULL) $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!file_exists($this->attempts_file)) {
            return;
        }

        $attempts = json_decode(file_get_contents($this->attempts_file), true);
        if (isset($attempts[$ip])) {
            unset($attempts[$ip]);
        }

        file_put_contents($this->attempts_file, json_encode($attempts, JSON_PRETTY_PRINT));
    }
}
