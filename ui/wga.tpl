{include file="admin/header.tpl"}

<section class="content-header">
    <form class="form-horizontal" method="post" autocomplete="off" role="form" action="">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="panel panel-primary panel-hovered panel-stacked mb30">
                    <div class="panel-heading">
                        <h3 class="panel-title">{Lang::T('Alternative WhatsApp Gateway Settings')}</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Server URL')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" required id="alt_wga_server_url"
                                    name="alt_wga_server_url" value="{$_c['alt_wga_server_url']}"
                                    placeholder="http://localhost:3000" onmouseleave="this.type = 'password'"
                                    onmouseenter="this.type = 'text'">
                                <span class="help-block">{Lang::T('WhatsApp Gateway API Server URL')}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Device ID')}</label>
                            <div class="col-md-6">
                                {if $devices && empty($_c['alt_wga_device_id']) && count($devices) > 0}
                                <select class="form-control" id="alt_wga_device_id" name="alt_wga_device_id">
                                    <option value="">{Lang::T('-- Select Device --')}</option>
                                    {foreach $devices as $device}
                                    <option value="{$device.device}" {if
                                        $_c['alt_wga_device_id']==$device.device}selected{/if}>
                                        {$device.device} {if $device.name}({$device.name}){/if}
                                    </option>
                                    {/foreach}
                                </select>
                                <span class="help-block">{Lang::T('Select the WhatsApp device to use for sending
                                    messages')}</span>
                                {else}
                                <input type="password" class="form-control" id="alt_wga_device_id"
                                    name="alt_wga_device_id" value="{$_c['alt_wga_device_id']}"
                                    placeholder="device_id12345" onmouseleave="this.type = 'password'"
                                    onmouseenter="this.type = 'text'">
                                <span class="help-block">{Lang::T('Enter Device ID manually or save Server URL first to
                                    load devices')}</span>
                                {/if}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label"></label>
                            <div class="col-md-6 offset-md-2">
                                <button class="btn btn-success" name="save" value="save" type="submit">
                                    {Lang::T('Save Changes')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Device Management Section -->
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-info panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <h3 class="panel-title">{Lang::T('Device Management')}</h3>
                </div>
                <div class="panel-body">
                    <!-- Login Options -->
                    <div class="row">
                        <div class="col-md-12">
                            <p>{Lang::T('Add / Link WhatsApp Device')}</p><br>
                            <div class="btn-group" style="margin-bottom: 15px;">
                                <button class="btn btn-success" type="button" onclick="wgaShowQrLogin()">
                                    <i class="glyphicon glyphicon-qrcode"></i> {Lang::T('Login with QR Code')}
                                </button>
                                <button class="btn btn-primary" type="button" onclick="wgaShowCodeLogin()">
                                    <i class="glyphicon glyphicon-phone"></i> {Lang::T('Login with Code')}
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Devices List -->
                    <div id="wga-devices-container">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>{Lang::T('Connected Devices')}</h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-default btn-sm" type="button" onclick="wgaRefreshDevices()">
                                    <i class="glyphicon glyphicon-refresh"></i> {Lang::T('Refresh List')}
                                </button>
                            </div>
                        </div>
                        <div id="wga-devices-list" class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>{Lang::T('Name')}</th>
                                        <th>{Lang::T('Device ID')}</th>
                                        <th>{Lang::T('Status')}</th>
                                        <th width="180">{Lang::T('Actions')}</th>
                                    </tr>
                                </thead>
                                <tbody id="wga-devices-tbody">
                                    {if $devices && count($devices) > 0}
                                    {foreach $devices as $device}
                                    <tr id="wga-device-row-{$device.device}">
                                        <td>{$device.name|default:$device.device}</td>
                                        <td><small>{$device.device}</small></td>
                                        <td>
                                            <span class="label label-success">
                                                {$device.status|default:'connected'}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-info btn-xs"
                                                onclick="wgaReconnect('{$device.device}')"
                                                title="{Lang::T('Reconnect')}">
                                                <i class="glyphicon glyphicon-refresh"></i>
                                            </button>
                                            <button class="btn btn-warning btn-xs"
                                                onclick="wgaLogout('{$device.device}')" title="{Lang::T('Logout')}">
                                                <i class="glyphicon glyphicon-log-out"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    {/foreach}
                                    {else}
                                    <tr id="wga-no-devices-row">
                                        <td colspan="4" class="text-center text-muted">
                                            {Lang::T('No devices found. Click "Login with QR Code" or "Login with Code"
                                            to add a device.')}
                                        </td>
                                    </tr>
                                    {/if}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QR Code Login Modal -->
<div class="modal fade" id="wgaQrCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-qrcode"></i> {Lang::T('Login with QR Code')}</h4>
            </div>
            <div class="modal-body text-center">
                <div id="wga-qr-loading" class="text-center" style="padding: 40px;">
                    <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i>
                    <p>{Lang::T('Loading QR Code...')}</p>
                </div>
                <div id="wga-qr-code-container" style="display:none;">
                    <img id="wga-qr-code-image" src="" alt="QR Code"
                        style="max-width: 280px; border: 1px solid #ddd; padding: 10px;">
                    <div id="wga-qr-status" class="alert alert-info" style="margin-top: 15px;">
                        <i class="glyphicon glyphicon-refresh glyphicon-spin"></i>
                        <span id="wga-qr-status-text">{Lang::T('Waiting for scan...')}</span>
                    </div>
                    <p class="text-muted" style="margin-top: 10px;">
                        <strong>{Lang::T('How to scan:')}</strong><br>
                        1. {Lang::T('Open WhatsApp on your phone')}<br>
                        2. {Lang::T('Go to Settings > Linked Devices')}<br>
                        3. {Lang::T('Tap "Link a Device"')}<br>
                        4. {Lang::T('Scan this QR code')}
                    </p>
                </div>
                <div id="wga-qr-connected" class="alert alert-success" style="display:none;">
                    <i class="glyphicon glyphicon-ok-circle" style="font-size: 48px;"></i>
                    <h4>{Lang::T('Device Connected Successfully!')}</h4>
                    <p>{Lang::T('You can now close this window.')}</p>
                </div>
                <div id="wga-qr-error" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                <button type="button" class="btn btn-primary" id="wga-qr-refresh-btn" onclick="wgaRefreshQrCode()">
                    <i class="glyphicon glyphicon-refresh"></i> {Lang::T('Refresh QR')}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Login with Code Modal -->
<div class="modal fade" id="wgaPairingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-phone"></i> {Lang::T('Login with Code')}</h4>
            </div>
            <div class="modal-body">
                <div id="wga-pairing-form">
                    <div class="form-group">
                        <input type="text" class="form-control" id="wga-pairing-phone"
                            placeholder="{Lang::T('e.g., 2348012345678')}">
                    </div>
                    <span class="help-block">{Lang::T('Enter your WhatsApp phone number with country code (without +
                        sign)')}</span>
                </div>
                <div id="wga-pairing-loading" style="display:none; text-align:center; padding: 20px;">
                    <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 24px;"></i>
                    <p>{Lang::T('Getting pairing code...')}</p>
                </div>
                <div id="wga-pairing-result" style="display:none;">
                    <div class="alert alert-warning text-center">
                        <p><strong>{Lang::T('Your Pairing Code:')}</strong></p>
                        <h1 id="wga-pairing-code-display" style="font-size: 36px; letter-spacing: 5px; margin: 20px 0;">
                        </h1>
                        <div id="wga-pairing-status" style="margin: 15px 0;">
                            <i class="glyphicon glyphicon-refresh glyphicon-spin"></i>
                            <span id="wga-pairing-status-text">{Lang::T('Waiting for connection...')}</span>
                        </div>
                        <p class="text-muted">
                            <strong>{Lang::T('How to use:')}</strong><br>
                            1. {Lang::T('Open WhatsApp on your phone')}<br>
                            2. {Lang::T('Go to Settings > Linked Devices')}<br>
                            3. {Lang::T('Tap "Link a Device"')}<br>
                            4. {Lang::T('Tap "Link with phone number instead"')}<br>
                            5. {Lang::T('Enter this code')}
                        </p>
                    </div>
                </div>
                <div id="wga-pairing-connected" class="alert alert-success text-center" style="display:none;">
                    <i class="glyphicon glyphicon-ok-circle" style="font-size: 48px;"></i>
                    <h4>{Lang::T('Device Connected Successfully!')}</h4>
                    <p>{Lang::T('You can now close this window.')}</p>
                </div>
                <div id="wga-pairing-error" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                <button type="button" class="btn btn-primary" id="wga-pairing-btn" onclick="wgaGetPairingCode()">
                    <i class="glyphicon glyphicon-link"></i> {Lang::T('Get Pairing Code')}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bs-callout bs-callout-info" id="wga-callout-api">
    <h4>API To send directly</h4>
    <div class="input-group">
        <input id="wga-api-url" type="text" class="form-control" readonly onclick="this.select();"
            value="{$_url}plugin/wga_sendMessage&phone=[number]&message=[text]&secret={md5($_c['api_key'])}">
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="wgaCopyApiUrl()" title="Copy to clipboard">
                <i class="glyphicon glyphicon-copy"></i> Copy
            </button>
        </span>
    </div>
    <p class="text-muted"><small>Copy the API URL and paste it in SMS Server URL field in SMS
            Settings.</small></p>
    <p class="text-muted">If you update the API Token in <a href="{$_url}settings/app">Settings > General Settings > API
            Key </a> then you need to update the secret in whatsapp gateway Settings.</p>
</div>

<!-- Server Installation Instructions -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">
            <a data-toggle="collapse" href="#wga-install-instructions" style="text-decoration: none;">
                <i class="glyphicon glyphicon-info-sign"></i> {Lang::T('WhatsApp Server Installation Guide')}
                <span class="pull-right"><i class="glyphicon glyphicon-chevron-down"></i></span>
            </a>
        </h4>
    </div>
    <div id="wga-install-instructions" class="panel-collapse collapse">
        <div class="panel-body">
            <p>This plugin requires <strong>go-whatsapp-web-multidevice</strong> server. Choose one of the installation
                methods below:</p>

            <h5><i class="glyphicon glyphicon-cloud"></i> <strong>Method 1: Docker (Recommended)</strong></h5>
            <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto;">
<span style="color: #75715e;"># Pull and run the Docker image</span>
 docker run --detach --publish=3000:3000 --name=whatsapp --restart=always --volume=$(docker volume create
--name=whatsapp):/app/storages aldinokemal2104/go-whatsapp-web-multidevice --autoreply="Dont't reply this message
please"
</pre>

            <h5><i class="glyphicon glyphicon-download-alt"></i> <strong>Method 2: Docker Compose</strong></h5>
            <p>Create a <code>docker-compose.yml</code> file:</p>
            <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto;">
services:
  whatsapp:
    image: aldinokemal2104/go-whatsapp-web-multidevice
    container_name: whatsapp
    restart: always
    ports:
      - "3000:3000"
    volumes:
      - whatsapp:/app/storages
    command:
      - --basic-auth=admin:admin
      - --port=3000
      - --debug=true
      - --os=Chrome
      - --account-validation=false

volumes:
  whatsapp:
</pre>
            <p>Then run: <code>docker-compose up -d</code></p>

            <h5><i class="glyphicon glyphicon-cog"></i> <strong>Method 3: Manual Installation (Go)</strong></h5>
            <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto;">
<span style="color: #75715e;"># Install Go 1.21+ first, then:</span>
git clone https://github.com/aldinokemal/go-whatsapp-web-multidevice.git
cd go-whatsapp-web-multidevice
go build -o whatsapp-server
./whatsapp-server</pre>

            <h5><i class="glyphicon glyphicon-link"></i> <strong>After Installation</strong></h5>
            <ol>
                <li>Server will run on <code>http://your-server-ip:3000</code></li>
                <li>Enter the server URL in the <strong>Server URL</strong> field above</li>
                <li>Click <strong>Save Changes</strong></li>
                <li>Click <strong>Login with QR Code</strong> or <strong>Login with Code</strong> to connect your
                    WhatsApp</li>
                <li>The Device ID will be automatically saved after successful connection</li>
            </ol>

            <h5><i class="glyphicon glyphicon-list-alt"></i> <strong>Environment Variables (Optional)</strong></h5>
            <table class="table table-condensed table-bordered" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Default</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>APP_PORT</code></td>
                        <td>3000</td>
                        <td>Server port</td>
                    </tr>
                    <tr>
                        <td><code>APP_DEBUG</code></td>
                        <td>false</td>
                        <td>Enable debug mode</td>
                    </tr>
                    <tr>
                        <td><code>APP_WEBHOOK</code></td>
                        <td></td>
                        <td>Webhook URL for incoming messages</td>
                    </tr>
                    <tr>
                        <td><code>APP_ACCOUNT_VALIDATION</code></td>
                        <td>true</td>
                        <td>Validate phone numbers</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert alert-warning" style="margin-top: 15px;">
                <i class="glyphicon glyphicon-warning-sign"></i>
                <strong>Important:</strong> Make sure port 3000 (or your chosen port) is open in your firewall.
                For production, use a reverse proxy (nginx/apache) with SSL.
            </div>

            <p class="text-muted">
                <i class="glyphicon glyphicon-book"></i>
                Full documentation: <a href="https://github.com/aldinokemal/go-whatsapp-web-multidevice"
                    target="_blank">GitHub Repository</a>
            </p>
        </div>
    </div>
</div>

{include file="admin/footer.tpl"}

<script>
(function() {
    var wgaBaseUrl = '{$_url}';
    var wgaConnectionCheckInterval = null;
    var wgaInitialDeviceCount = {if $devices}{$devices|@count}{else}0{/if};

    // Helper functions
    function wgaGetEl(id) { return document.getElementById(id); }
    function wgaSetDisplay(id, value) { var el = wgaGetEl(id); if (el) el.style.display = value; }
    function wgaSetText(id, value) { var el = wgaGetEl(id); if (el) el.textContent = value; }
    function wgaSetValue(id, value) { var el = wgaGetEl(id); if (el) el.value = value; }
    function wgaSetHtml(id, value) { var el = wgaGetEl(id); if (el) el.innerHTML = value; }

    // Copy API URL
    window.wgaCopyApiUrl = function() {
        var copyText = wgaGetEl("wga-api-url");
        if (copyText) {
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("API URL copied to clipboard.");
        }
    };

    // Start checking for new device connection
    function wgaStartConnectionCheck(modalType) {
        wgaStopConnectionCheck();
        wgaConnectionCheckInterval = setInterval(function () {
            wgaCheckConnection(modalType);
        }, 3000);
    }

    // Stop checking for connection
    function wgaStopConnectionCheck() {
        if (wgaConnectionCheckInterval) {
            clearInterval(wgaConnectionCheckInterval);
            wgaConnectionCheckInterval = null;
        }
    }

    // Check if a new device has connected
    function wgaCheckConnection(modalType) {
        fetch(wgaBaseUrl + 'plugin/wga_getDevices')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.data && data.data.results) {
                    var devices = data.data.results;
                    var currentCount = devices.length;
                    if (currentCount > wgaInitialDeviceCount) {
                        wgaStopConnectionCheck();
                        var newDevice = devices[devices.length - 1];
                        var newDeviceId = newDevice.device || '';
                        if (newDeviceId) {
                            wgaSaveDeviceId(newDeviceId);
                        }
                        wgaInitialDeviceCount = currentCount;
                        wgaRenderDevicesTable(devices);
                        if (modalType === 'qr') {
                            wgaSetDisplay('wga-qr-code-container', 'none');
                            wgaSetDisplay('wga-qr-connected', 'block');
                            wgaSetDisplay('wga-qr-refresh-btn', 'none');
                        } else if (modalType === 'pairing') {
                            wgaSetDisplay('wga-pairing-result', 'none');
                            wgaSetDisplay('wga-pairing-connected', 'block');
                            wgaSetDisplay('wga-pairing-btn', 'none');
                        }
                    }
                }
            })
            .catch(function (error) {
                console.error('Connection check error:', error);
            });
    }

    // Save device ID to config
    function wgaSaveDeviceId(deviceId) {
        var formData = new FormData();
        formData.append('device_id', deviceId);
        fetch(wgaBaseUrl + 'plugin/wga_saveDeviceId', { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    var deviceIdEl = wgaGetEl('alt_wga_device_id');
                    if (deviceIdEl) {
                        if (deviceIdEl.tagName === 'SELECT') {
                            var optionExists = false;
                            for (var i = 0; i < deviceIdEl.options.length; i++) {
                                if (deviceIdEl.options[i].value === deviceId) {
                                    deviceIdEl.selectedIndex = i;
                                    optionExists = true;
                                    break;
                                }
                            }
                            if (!optionExists) {
                                var option = document.createElement('option');
                                option.value = deviceId;
                                option.text = deviceId;
                                option.selected = true;
                                deviceIdEl.appendChild(option);
                            }
                        } else {
                            deviceIdEl.value = deviceId;
                        }
                    }
                    console.log('Device ID saved:', deviceId);
                }
            })
            .catch(function (error) {
                console.error('Error saving device ID:', error);
            });
    }

    // Show QR Code Login Modal
    window.wgaShowQrLogin = function() {
        wgaSetDisplay('wga-qr-loading', 'block');
        wgaSetDisplay('wga-qr-code-container', 'none');
        wgaSetDisplay('wga-qr-connected', 'none');
        wgaSetDisplay('wga-qr-error', 'none');
        wgaSetDisplay('wga-qr-refresh-btn', 'inline-block');
        $('#wgaQrCodeModal').modal('show');
        wgaLoadQrCode();
    };

    // Load QR Code from server (uses proxied base64 image only)
    function wgaLoadQrCode() {
        fetch(wgaBaseUrl + 'plugin/wga_getQrCode')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                wgaSetDisplay('wga-qr-loading', 'none');
                console.log('QR Code response:', data);
                // Only use proxied base64 image (avoid 403 from direct URL)
                if (data.success && data.qr_image) {
                    var qrImg = wgaGetEl('wga-qr-code-image');
                    if (qrImg) qrImg.src = data.qr_image;
                    wgaSetDisplay('wga-qr-code-container', 'block');
                    wgaStartConnectionCheck('qr');
                } else {
                    var msg = data.message || 'Failed to load QR code';
                    if (data.data && data.data.message) msg = data.data.message;
                    if (data.success && !data.qr_image) msg = 'QR image proxy failed. Check server logs.';
                    wgaSetText('wga-qr-error', msg);
                    wgaSetDisplay('wga-qr-error', 'block');
                }
            })
            .catch(function (error) {
                wgaSetDisplay('wga-qr-loading', 'none');
                wgaSetText('wga-qr-error', 'Error: ' + error.message);
                wgaSetDisplay('wga-qr-error', 'block');
            });
    }

    // Refresh QR Code
    window.wgaRefreshQrCode = function() {
        wgaStopConnectionCheck();
        wgaSetDisplay('wga-qr-loading', 'block');
        wgaSetDisplay('wga-qr-code-container', 'none');
        wgaSetDisplay('wga-qr-connected', 'none');
        wgaSetDisplay('wga-qr-error', 'none');
        wgaLoadQrCode();
    };

    // Show Login with Code Modal
    window.wgaShowCodeLogin = function() {
        wgaSetValue('wga-pairing-phone', '');
        wgaSetDisplay('wga-pairing-form', 'block');
        wgaSetDisplay('wga-pairing-loading', 'none');
        wgaSetDisplay('wga-pairing-result', 'none');
        wgaSetDisplay('wga-pairing-connected', 'none');
        wgaSetDisplay('wga-pairing-error', 'none');
        wgaSetDisplay('wga-pairing-btn', 'inline-block');
        $('#wgaPairingModal').modal('show');
    };

    // Get Pairing Code
    window.wgaGetPairingCode = function() {
        var phoneEl = wgaGetEl('wga-pairing-phone');
        var phone = phoneEl ? phoneEl.value.trim() : '';
        if (!phone) {
            alert('Please enter your WhatsApp phone number');
            return;
        }
        var formData = new FormData();
        formData.append('phone', phone);
        wgaSetDisplay('wga-pairing-form', 'none');
        wgaSetDisplay('wga-pairing-loading', 'block');
        wgaSetDisplay('wga-pairing-result', 'none');
        wgaSetDisplay('wga-pairing-connected', 'none');
        wgaSetDisplay('wga-pairing-error', 'none');
        wgaSetDisplay('wga-pairing-btn', 'none');

        fetch(wgaBaseUrl + 'plugin/wga_getPairingCode', { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                wgaSetDisplay('wga-pairing-loading', 'none');
                console.log('Pairing code response:', data);
                var pairingCode = data.code || (data.data && data.data.results && data.data.results.code) ||
                    (data.data && data.data.results && data.data.results.pair_code) || (data.data && data.data.pair_code);
                if (data.success && pairingCode) {
                    wgaSetText('wga-pairing-code-display', pairingCode);
                    wgaSetDisplay('wga-pairing-result', 'block');
                    wgaStartConnectionCheck('pairing');
                } else {
                    var msg = data.message || 'Failed to get pairing code';
                    if (data.data && data.data.message) msg = data.data.message;
                    if (!pairingCode && data.success) msg = 'Pairing code not found in response. Check logs.';
                    wgaSetText('wga-pairing-error', msg);
                    wgaSetDisplay('wga-pairing-error', 'block');
                    wgaSetDisplay('wga-pairing-form', 'block');
                    wgaSetDisplay('wga-pairing-btn', 'inline-block');
                }
            })
            .catch(function (error) {
                wgaSetDisplay('wga-pairing-loading', 'none');
                wgaSetText('wga-pairing-error', 'Error: ' + error.message);
                wgaSetDisplay('wga-pairing-error', 'block');
                wgaSetDisplay('wga-pairing-form', 'block');
                wgaSetDisplay('wga-pairing-btn', 'inline-block');
            });
    };

    // Reconnect Device
    window.wgaReconnect = function(deviceId) {
        if (!deviceId) { alert('Device ID is required'); return; }
        if (!confirm('Reconnect device ' + deviceId + '?')) return;
        var formData = new FormData();
        formData.append('device_id', deviceId);
        fetch(wgaBaseUrl + 'plugin/wga_reconnect', { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Reconnect request sent successfully');
                    wgaRefreshDevices();
                } else {
                    alert('Error: ' + (data.message || 'Failed to reconnect'));
                }
            })
            .catch(function (error) { alert('Error: ' + error.message); });
    };

    // Logout Device
    window.wgaLogout = function(deviceId) {
        if (!deviceId) { alert('Device ID is required'); return; }
        if (!confirm('Are you sure you want to logout device ' + deviceId + '?')) return;
        var formData = new FormData();
        formData.append('device_id', deviceId);
        fetch(wgaBaseUrl + 'plugin/wga_logoutDevice', { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Logged out successfully');
                    wgaRefreshDevices();
                } else {
                    alert('Error: ' + (data.message || 'Failed to logout'));
                }
            })
            .catch(function (error) { alert('Error: ' + error.message); });
    };

    // Refresh Devices List
    window.wgaRefreshDevices = function() {
        fetch(wgaBaseUrl + 'plugin/wga_getDevices')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.data && data.data.results) {
                    wgaRenderDevicesTable(data.data.results);
                } else {
                    wgaSetHtml('wga-devices-tbody', '<tr><td colspan="4" class="text-center text-muted">No devices found.</td></tr>');
                }
            })
            .catch(function (error) { console.error('Error refreshing devices:', error); });
    };

    // Render Devices Table
    function wgaRenderDevicesTable(devices) {
        var tbody = wgaGetEl('wga-devices-tbody');
        if (!tbody) return;
        if (!devices || devices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No devices found.</td></tr>';
            return;
        }
        var html = '';
        devices.forEach(function (device) {
            var deviceId = device.device || '';
            var status = device.status || 'connected';
            var statusClass = (status === 'connected' || status === 'online') ? 'label-success' : 'label-warning';
            html += '<tr><td>' + (device.name || '-') + '</td><td><small>' + deviceId + '</small></td>' +
                '<td><span class="label ' + statusClass + '">' + status + '</span></td><td>' +
                '<button class="btn btn-info btn-xs" onclick="wgaReconnect(\'' + deviceId + '\')" title="Reconnect">' +
                '<i class="glyphicon glyphicon-refresh"></i></button> ' +
                '<button class="btn btn-warning btn-xs" onclick="wgaLogout(\'' + deviceId + '\')" title="Logout">' +
                '<i class="glyphicon glyphicon-log-out"></i></button></td></tr>';
        });
        tbody.innerHTML = html;
    }

    // Modal close event handlers (jQuery available now)
    $('#wgaQrCodeModal').on('hidden.bs.modal', function () {
        wgaStopConnectionCheck();
        wgaRefreshDevices();
    });
    $('#wgaPairingModal').on('hidden.bs.modal', function () {
        wgaStopConnectionCheck();
        wgaRefreshDevices();
    });

    // Version info
    var versionEl = document.getElementById('version');
    if (versionEl) {
        var authorLink = "https://t.me/focuslinkstech";
        var productName = "{$productName}";
        var version = "{$version}";
        versionEl.innerHTML = productName + ' | Ver: ' + version + ' | by: <a href="' + authorLink + '">Focuslinks Tech</a>';
    }
})();
</script>