<?php

/**
 * Bismillahir Rahmanir Raheem
 * 
 * PHPNuxBill (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Alternative WhatsApp Plugin for PHPNuxBill
 *
 * @author: Focuslinks Digital Solutions <focuslinkstech@gmail.com>
 * Website: https://focuslinkstech.com.ng/
 * GitHub: https://github.com/Focuslinkstech/
 * Telegram: https://t.me/focuslinkstech/
 *
 **/

register_menu("Alt WA Gateway", true, "wga_config", 'SETTINGS', '', '', "");
register_hook('cronjob', 'wga_cron');

function wga_config()
{
    global $ui, $config;
    _admin();
    $ui->assign('_title', 'Alternative WhatsApp Gateway Plugin');
    $ui->assign('_system_menu', '');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        $serverUrl = trim($_POST['alt_wga_server_url'] ?? '');
        $deviceId = trim($_POST['alt_wga_device_id'] ?? '');

        // Save server URL
        if (!empty($serverUrl)) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'alt_wga_server_url')->find_one();
            if ($d) {
                $d->value = $serverUrl;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'alt_wga_server_url';
                $d->value = $serverUrl;
                $d->save();
            }
        }

        // Save device ID
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'alt_wga_device_id')->find_one();
        if ($d) {
            $d->value = $deviceId;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'alt_wga_device_id';
            $d->value = $deviceId;
            $d->save();
        }

        r2(getUrl('plugin/wga_config'), 's', Lang::T('Settings saved successfully'));
        exit;
    }

    // Fetch devices list from API if server URL is configured
    $devices = [];
    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (!empty($serverUrl)) {
        try {
            $apiKey = md5($config['api_key'] ?? '');
            $wga = new WGA($serverUrl, $apiKey);
            $result = $wga->getDevices();
            if ($result['success'] && isset($result['data']['results'])) {
                $devices = $result['data']['results'];
            }
        } catch (Exception $e) {
            // Devices fetch failed - will show empty list
        }
    }

    $ui->assign('devices', $devices);
    $ui->assign('productName', 'Alternative WhatsApp Gateway Plugin');
    $ui->assign('version', '1.0.0');
    $ui->display('wga.tpl');
}


/**
 * WhatsApp Gateway API Client
 * Secure implementation for sending messages via the WhatsApp Gateway API
 */

class WGA
{
    private $apiUrl;
    private $apiKey;
    /**
     * Constructor
     * @param string $baseUrl Base URL of the WhatsApp Gateway API
     * @param string $apiKey API key for authentication
     * @param array $allowedKeys Array of allowed API keys for validation
     */
    public function __construct($baseUrl, $apiKey = null)
    {
        $this->apiUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;

        // If allowed keys are provided, validate the API key
        if (!empty($this->apiKey) && !$this->validateApiKey($this->apiKey)) {
            throw new Exception('Invalid or missing API key');
        }
    }

    /**
     * Validate API key
     * @return bool
     */
    private function validateApiKey($secret = null)
    {
        global $config;

        if (empty($secret)) {
            return false;
        }

        if ($secret !== md5($config['api_key'] ?? '')) {
            return false;
        }

        // Check if API key exists in allowed keys
        return true;
    }

    /**
     * Validate phone number format
     * @param string $phone Phone number to validate
     * @return bool
     */
    private function validatePhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check if phone number is valid (minimum 10 digits)
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize input data
     * @param mixed $data Data to sanitize
     * @return mixed
     */
    private function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Make POST API request
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $extraHeaders Extra headers
     * @return array Response
     */
    private function makeRequest($endpoint, $data, $extraHeaders = [])
    {
        $url = $this->apiUrl . $endpoint;

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Build headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        if (!empty($extraHeaders)) {
            $headers = array_merge($headers, $extraHeaders);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Optional: SSL verification (set to false for local development)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return $this->processResponse($response, $httpCode, $error);
    }

    /**
     * Make GET API request
     * @param string $endpoint API endpoint
     * @param array $extraHeaders Extra headers
     * @return array Response
     */
    private function makeGetRequest($endpoint, $extraHeaders = [])
    {
        $url = $this->apiUrl . $endpoint;

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        // Build headers
        $headers = [
            'Accept: application/json'
        ];
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        if (!empty($extraHeaders)) {
            $headers = array_merge($headers, $extraHeaders);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Optional: SSL verification (set to false for local development)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return $this->processResponse($response, $httpCode, $error);
    }

    /**
     * Process cURL response
     * @param string $response Raw response
     * @param int $httpCode HTTP status code
     * @param string $error cURL error message
     * @return array Processed response
     */
    private function processResponse($response, $httpCode, $error)
    {
        // Check for cURL errors
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error,
                'http_code' => 0
            ];
        }

        // Log raw response for debugging
        _log('WGA Raw Response (HTTP ' . $httpCode . '): ' . substr($response, 0, 500));

        // Check if response is empty
        if (empty($response)) {
            return [
                'success' => false,
                'message' => 'Empty response from API',
                'http_code' => $httpCode
            ];
        }

        // Decode JSON response
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response: ' . json_last_error_msg() . ' - Raw: ' . substr($response, 0, 200),
                'http_code' => $httpCode
            ];
        }

        // Check for success - API returns 'code' field (can be numeric 200/201 or string 'SUCCESS')
        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
        if (isset($responseData['code'])) {
            $code = $responseData['code'];
            // Handle both numeric (200, 201) and string ('SUCCESS') codes
            $isSuccess = $isSuccess && ($code == 200 || $code == 201 || strtoupper($code) === 'SUCCESS');
        }

        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'data' => $responseData
        ];
    }

    /**
     * Send text message
     * @param string $sessionName Session name (e.g., 'session_1')
     * @param string $target Target phone number
     * @param string $message Message content
     * @return array Response
     */
    public function sendText($target, $message)
    {
        // Validate inputs
        if (empty($target) || empty($message)) {
            return [
                'success' => false,
                'message' => 'All fields are required: target, message'
            ];
        }

        // Validate phone number
        if (!$this->validatePhoneNumber($target)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format'
            ];
        }

        // Sanitize inputs
        $target = preg_replace('/[^0-9]/', '', $target);
        $message = $this->sanitizeInput($message);

        // Prepare request data
        $data = [
            'phone' => $target . '@s.whatsapp.net',
            'message' => $message,
            "is_forwarded" => false,
            "duration" => 3600
        ];

        // Make API request
        return $this->makeRequest('/send/message', $data);
    }

    /**
     * Send media message
     * @param string $sessionName Session name
     * @param string $target Target phone number
     * @param string $url Media URL
     * @param string $caption Optional caption
     * @return array Response
     */
    public function sendMedia($sessionName, $target, $url, $caption = '')
    {
        // Validate inputs
        if (empty($sessionName) || empty($target) || empty($url)) {
            return [
                'success' => false,
                'message' => 'sessionName, target, and url are required'
            ];
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Invalid URL format'
            ];
        }

        // Sanitize inputs
        $sessionName = $this->sanitizeInput($sessionName);
        $target = preg_replace('/[^0-9]/', '', $target);
        $caption = $this->sanitizeInput($caption);

        // Prepare request data
        $data = [
            'sessions' => $sessionName,
            'target' => $target,
            'url' => $url,
            'message' => $caption
        ];

        // Make API request
        return $this->makeRequest('/api/sendmedia', $data);
    }

    /**
     * Send location
     * @param string $sessionName Session name
     * @param string $target Target phone number
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return array Response
     */
    public function sendLocation($sessionName, $target, $latitude, $longitude)
    {
        // Validate inputs
        if (empty($sessionName) || empty($target) || !is_numeric($latitude) || !is_numeric($longitude)) {
            return [
                'success' => false,
                'message' => 'All fields are required with valid coordinates'
            ];
        }

        // Sanitize inputs
        $sessionName = $this->sanitizeInput($sessionName);
        $target = preg_replace('/[^0-9]/', '', $target);

        // Prepare request data
        $data = [
            'sessions' => $sessionName,
            'target' => $target,
            'lat' => (float)$latitude,
            'long' => (float)$longitude
        ];

        // Make API request
        return $this->makeRequest('/api/sendlocation', $data);
    }

    /**
     * Get list of devices
     * @return array Response with devices list
     */
    public function getDevices()
    {
        return $this->makeGetRequest('/app/devices');
    }

    /**
     * Get device info
     * @param string $deviceId Device ID
     * @return array Response with device info
     */
    public function getDeviceInfo($deviceId)
    {
        if (empty($deviceId)) {
            return [
                'success' => false,
                'message' => 'Device ID is required'
            ];
        }
        $headers = ['X-Device-Id: ' . $deviceId];
        return $this->makeGetRequest('/app/devices', $headers);
    }

    /**
     * Refresh/reconnect session
     * @param string $deviceId Device ID for X-Device-Id header (optional)
     * @return array Response
     */
    public function refreshSession($deviceId = null)
    {
        $headers = [];
        if ($deviceId) {
            $headers[] = 'X-Device-Id: ' . $deviceId;
        }
        return $this->makeGetRequest('/app/reconnect', $headers);
    }

    /**
     * Reconnect device
     * @param string $deviceId Device ID (required)
     * @return array Response
     */
    public function reconnectDevice($deviceId)
    {
        if (empty($deviceId)) {
            return [
                'success' => false,
                'message' => 'Device ID is required for reconnect'
            ];
        }
        $headers = ['X-Device-Id: ' . $deviceId];
        return $this->makeGetRequest('/app/reconnect', $headers);
    }

    /**
     * Add a new device - Note: Legacy API may not support this directly
     * Device is auto-created on first QR scan
     * @param string $deviceId Optional custom device ID
     * @return array Response
     */
    public function addDevice($deviceId = null)
    {
        // Legacy API: devices are created automatically when you scan QR
        // Just return success and let user scan QR to create device
        return [
            'success' => true,
            'message' => 'Please scan QR code to add device. Device will be created automatically.',
            'data' => ['device_id' => $deviceId ?: 'default']
        ];
    }

    /**
     * Get QR code for device login
     * @param string $deviceId Device ID (optional for legacy API)
     * @return array Response with QR code image
     */
    public function getQrCode($deviceId = null)
    {
        $headers = [];
        if ($deviceId) {
            $headers[] = 'X-Device-Id: ' . $deviceId;
        }
        return $this->makeGetRequest('/app/login', $headers);
    }

    /**
     * Get pairing code for device login (link with phone number)
     * @param string $deviceId Device ID (optional)
     * @param string $phone Phone number to pair with (required)
     * @return array Response with pairing code
     */
    public function getPairingCode($deviceId, $phone)
    {
        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'Phone number is required for pairing code'
            ];
        }
        // Clean phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $headers = [];
        if ($deviceId) {
            $headers[] = 'X-Device-Id: ' . $deviceId;
        }
        // Use GET with phone as query parameter
        return $this->makeGetRequest('/app/login-with-code?phone=' . urlencode($phone), $headers);
    }

    /**
     * Logout device
     * @param string $deviceId Device ID (optional)
     * @return array Response
     */
    public function logoutDevice($deviceId = null)
    {
        $headers = [];
        if ($deviceId) {
            $headers[] = 'X-Device-Id: ' . $deviceId;
        }
        // Logout uses GET method
        return $this->makeGetRequest('/app/logout', $headers);
    }

    /**
     * Delete device - Note: Legacy API uses logout
     * @param string $deviceId Device ID (optional)
     * @return array Response
     */
    public function deleteDevice($deviceId = null)
    {
        // Legacy API doesn't have delete, use logout instead
        return $this->logoutDevice($deviceId);
    }

    /**
     * Make DELETE API request
     * @param string $endpoint API endpoint
     * @param array $extraHeaders Extra headers
     * @return array Response
     */
    private function makeDeleteRequest($endpoint, $extraHeaders = [])
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $headers = ['Accept: application/json'];
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        if (!empty($extraHeaders)) {
            $headers = array_merge($headers, $extraHeaders);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return $this->processResponse($response, $httpCode, $error);
    }
}

function wga_cron()
{
    global $config;

    // For CLI execution, load config from database if not available
    $baseUrl = $config['alt_wga_server_url'] ?? '';
    $deviceId = $config['alt_wga_device_id'] ?? '';
    $apiKey = $config['api_key'] ?? '';

    // If config not loaded (CLI mode), fetch from database
    if (empty($baseUrl) || empty($apiKey)) {
        try {
            $settings = ORM::for_table('tbl_appconfig')
                ->where_in('setting', ['alt_wga_server_url', 'alt_wga_device_id', 'api_key'])
                ->find_many();

            foreach ($settings as $setting) {
                if ($setting->setting === 'alt_wga_server_url') {
                    $baseUrl = $setting->value;
                } elseif ($setting->setting === 'alt_wga_device_id') {
                    $deviceId = $setting->value;
                } elseif ($setting->setting === 'api_key') {
                    $apiKey = $setting->value;
                }
            }
        } catch (Exception $e) {
            echo 'WGA Cron: Failed to load config - ' . $e->getMessage() . PHP_EOL;
            _log('WGA Cron: Failed to load config - ' . $e->getMessage());
            return;
        }
    }

    // Skip if server URL or device ID is not configured
    if (empty($baseUrl)) {
        echo 'WGA Cron: Server URL not configured, skipping.' . PHP_EOL;
        return;
    }

    if (empty($deviceId)) {
        echo 'WGA Cron: Device ID not configured, skipping.' . PHP_EOL;
        return;
    }

    try {
        $wga = new WGA($baseUrl, md5($apiKey));
        $result = $wga->reconnectDevice($deviceId);

        if ($result['success']) {
            echo 'WGA Cron: Reconnect successful for device ' . $deviceId . PHP_EOL;
        } else {
            $errorMsg = $result['message'] ?? 'Unknown error';
            echo 'WGA Cron: Reconnect failed - ' . $errorMsg . PHP_EOL;
            _log('WGA Cron: Reconnect failed - ' . $errorMsg);
        }
    } catch (Exception $e) {
        echo 'WGA Cron Error: ' . $e->getMessage() . PHP_EOL;
        _log('WGA Cron Error: ' . $e->getMessage());
    }
}

function wga_sendMessage()
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['phone'])) {
        global $config;

        $baseUrl = $config['alt_wga_server_url'] ?? 'http://localhost:3000';

        // Set JSON response header
        header('Content-Type: application/json');


        try {
            // Get parameters from URL
            $phone = isset($_GET['phone']) ? $_GET['phone'] : null;
            $message = isset($_GET['message']) ? $_GET['message'] : null;
            $secret = isset($_GET['secret']) ? $_GET['secret'] : null;

            // Validate required parameters
            if (empty($phone)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Parameter "phone" is required'
                ]);
                exit;
            }

            if (empty($message)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Parameter "message" is required'
                ]);
                exit;
            }

            if (empty($secret)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Parameter "secret" (API key) is required'
                ]);
                exit;
            }

            // Initialize WhatsApp Gateway with API key validation
            $whatsapp = new WGA(
                $baseUrl,
                $secret
            );

            // Send the message
            $result = $whatsapp->sendText($phone, $message);

            // Return JSON response
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'phone' => $phone,
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => isset($result['data']) ? $result['data'] : null
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit; // Stop execution after handling GET request
    }
}

/**
 * AJAX handler for adding a new device
 */
function wga_addDevice()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    try {
        $deviceId = isset($_POST['device_id']) ? trim($_POST['device_id']) : null;
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->addDevice($deviceId);
        _log('WGA Add Device Result: ' . print_r($result, true));

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Device added successfully',
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['data']['message'] ?? 'Failed to add device',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for getting QR code
 */
function wga_getQrCode()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    // Use configured device ID if available, otherwise let API create new one
    $deviceId = $config['alt_wga_device_id'] ?? '';

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->getQrCode($deviceId ?: null);

        if ($result['success']) {
            // Proxy the QR image to avoid 403 from direct browser access
            $qrBase64 = null;
            $qrLink = $result['data']['results']['qr_link'] ?? '';
            if (!empty($qrLink)) {
                // If relative URL, prepend server URL
                if (strpos($qrLink, 'http') !== 0) {
                    $qrLink = rtrim($serverUrl, '/') . '/' . ltrim($qrLink, '/');
                }

                // Fetch the image via cURL with auth headers
                $ch = curl_init($qrLink);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: image/png, image/*, */*',
                    'X-API-Key: ' . $apiKey
                ]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $imgData = curl_exec($ch);
                $imgType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $imgCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($imgCode == 200 && !empty($imgData)) {
                    $mimeType = $imgType ?: 'image/png';
                    // Clean mime type if it has charset
                    if (strpos($mimeType, ';') !== false) {
                        $mimeType = trim(explode(';', $mimeType)[0]);
                    }
                    $qrBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imgData);
                } else {
                    _log('WGA QR Image Fetch Failed: HTTP ' . $imgCode . ' - ' . $curlError . ' - URL: ' . $qrLink);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'QR code retrieved',
                'qr_image' => $qrBase64,
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['data']['message'] ?? 'Failed to get QR code',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for getting pairing code
 */
function wga_getPairingCode()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    // Use configured device ID if available
    $deviceId = $config['alt_wga_device_id'] ?? '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required']);
        exit;
    }

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->getPairingCode($deviceId ?: null, $phone);

        _log('WGA Pairing Code Response: ' . print_r($result, true));

        if ($result['success']) {
            // Extract pairing code from various possible response formats
            $pairingCode = null;
            $data = $result['data'] ?? [];

            // Try different response formats
            if (isset($data['results']['code'])) {
                $pairingCode = $data['results']['code'];
            } elseif (isset($data['results']['pair_code'])) {
                $pairingCode = $data['results']['pair_code'];
            } elseif (isset($data['code']) && !in_array($data['code'], ['SUCCESS', 200, 201])) {
                $pairingCode = $data['code'];
            } elseif (isset($data['pair_code'])) {
                $pairingCode = $data['pair_code'];
            } elseif (isset($data['pairingCode'])) {
                $pairingCode = $data['pairingCode'];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Pairing code retrieved',
                'code' => $pairingCode,
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['data']['message'] ?? $result['message'] ?? 'Failed to get pairing code',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for logout device
 */
function wga_logoutDevice()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    // Get device ID from POST
    $deviceId = isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
    if (empty($deviceId)) {
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        exit;
    }

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->logoutDevice($deviceId);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Device logged out successfully',
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['data']['message'] ?? 'Failed to logout device',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for delete device
 */
function wga_deleteDevice()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    $deviceId = isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
    if (empty($deviceId)) {
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        exit;
    }

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->deleteDevice($deviceId);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Device deleted successfully',
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['data']['message'] ?? 'Failed to delete device',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for manual reconnect
 */
function wga_reconnect()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    // Get device ID from POST
    $deviceId = isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
    if (empty($deviceId)) {
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        exit;
    }

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->reconnectDevice($deviceId);

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Reconnect request sent',
                'data' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to reconnect',
                'data' => $result['data'] ?? null
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for saving device ID to config
 */
function wga_saveDeviceId()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $deviceId = isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
    if (empty($deviceId)) {
        echo json_encode(['success' => false, 'message' => 'Device ID is required']);
        exit;
    }

    try {
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'alt_wga_device_id')->find_one();
        if ($d) {
            $d->value = $deviceId;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'alt_wga_device_id';
            $d->value = $deviceId;
            $d->save();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Device ID saved successfully',
            'device_id' => $deviceId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * AJAX handler for getting devices list
 */
function wga_getDevices()
{
    global $config;
    _admin();
    $admin = Admin::_info();

    header('Content-Type: application/json');

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $serverUrl = $config['alt_wga_server_url'] ?? '';
    if (empty($serverUrl)) {
        echo json_encode(['success' => false, 'message' => 'Server URL not configured']);
        exit;
    }

    try {
        $apiKey = md5($config['api_key'] ?? '');
        $wga = new WGA($serverUrl, $apiKey);
        $result = $wga->getDevices();

        _log('WGA Get Devices Result: ' . print_r($result, true));

        if ($result['success']) {
            // Handle different response formats
            $devices = [];
            if (isset($result['data']['results'])) {
                $devices = $result['data']['results'];
            } elseif (isset($result['data']['data'])) {
                $devices = $result['data']['data'];
            } elseif (isset($result['data']['devices'])) {
                $devices = $result['data']['devices'];
            } elseif (is_array($result['data']) && !isset($result['data']['code'])) {
                // Maybe the data itself is the devices array
                $devices = $result['data'];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Devices retrieved',
                'data' => ['results' => $devices],
                'raw' => $result['data'] // Include raw for debugging
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to get devices',
                'data' => $result['data']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
