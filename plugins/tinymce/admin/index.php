<?php
session_start();

// Path to admin.key
$keyFile = __DIR__ . '/admin.key';

// Check if admin.key exists
if (!file_exists($keyFile)) {
    http_response_code(404);
    die('404 - Admin panel not configured');
}

// Load password hash
$passwordHash = trim(file_get_contents($keyFile));

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], $passwordHash)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect password';
    }
}

// Check authentication
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// GitHub config file and helper functions
$githubConfigFile = __DIR__ . '/github.config';

// Load GitHub config
function loadGithubConfig($file) {
    if (!file_exists($file)) {
        return ['repo_url' => '', 'branch' => 'main'];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : ['repo_url' => '', 'branch' => 'main'];
}

// Save GitHub config
function saveGithubConfig($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// AJAX API handler
if ($isLoggedIn && isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Save GitHub settings via AJAX
    if ($_GET['ajax'] === 'save_github' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $newConfig = [
            'repo_url' => trim($_POST['repo_url'] ?? ''),
            'branch' => trim($_POST['branch'] ?? 'main')
        ];
        if (saveGithubConfig($githubConfigFile, $newConfig)) {
            echo json_encode(['success' => true, 'message' => 'GitHub settings saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save GitHub settings']);
        }
        exit;
    }

    // Save content block via AJAX
    if ($_GET['ajax'] === 'save_block' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $blockId = (int)($_POST['block_id'] ?? 0);
        $content = $_POST['content'] ?? '';
        $dataDir = dirname(__DIR__) . '/data';
        $filePath = $dataDir . '/content-' . $blockId . '.html';

        // Check if directory exists and is writable
        if (!is_dir($dataDir)) {
            echo json_encode(['success' => false, 'message' => 'Data directory does not exist: ' . $dataDir]);
            exit;
        }
        if (!is_writable($dataDir)) {
            echo json_encode(['success' => false, 'message' => 'Data directory is not writable. Check permissions on: ' . $dataDir]);
            exit;
        }

        // Try to save file
        $result = file_put_contents($filePath, $content);
        if ($result !== false) {
            echo json_encode(['success' => true, 'message' => 'Block saved successfully', 'file' => $filePath]);
        } else {
            $error = error_get_last();
            $errorMsg = $error ? $error['message'] : 'Unknown error';
            echo json_encode(['success' => false, 'message' => 'Failed to save block: ' . $errorMsg, 'file' => $filePath]);
        }
        exit;
    }

    // Update from GitHub via AJAX
    if ($_GET['ajax'] === 'update_github' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $config = loadGithubConfig($githubConfigFile);
        $repoUrl = $config['repo_url'];
        $branch = $config['branch'];

        if (empty($repoUrl)) {
            echo json_encode(['success' => false, 'message' => 'GitHub repository URL is not configured']);
            exit;
        }

        $publicRoot = dirname(__DIR__, 3);
        $tmpDir = $publicRoot . DIRECTORY_SEPARATOR . 'tmp_github_update';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'repo.zip';

        if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }

        $dl = downloadGithubZip($repoUrl, $branch, $zipPath);
        if (!$dl['ok']) {
            echo json_encode(['success' => false, 'message' => 'Download failed: ' . ($dl['msg'] ?? '')]);
            exit;
        }

        $extractDir = $tmpDir . DIRECTORY_SEPARATOR . 'extracted';
        $ex = extractZipTo($zipPath, $extractDir);
        if (!$ex['ok']) {
            echo json_encode(['success' => false, 'message' => 'Extract failed: ' . ($ex['msg'] ?? '')]);
            exit;
        }

        $items = scandir($extractDir);
        $top = null;
        if (is_array($items)) {
            foreach ($items as $it) {
                if ($it === '.' || $it === '..') continue;
                if (is_dir($extractDir . DIRECTORY_SEPARATOR . $it)) {
                    $top = $extractDir . DIRECTORY_SEPARATOR . $it;
                    break;
                }
            }
        }

        if (!$top) {
            echo json_encode(['success' => false, 'message' => 'Unexpected ZIP structure']);
            exit;
        }

        $allowed = ['themes', 'plugins', 'generator', 'scripts', 'styles'];
        copyWhitelist($top, $publicRoot, $allowed);
        @unlink($zipPath);

        $genCfg = $publicRoot . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'config.php';
        $genMain = $publicRoot . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'generate.php';

        if (is_file($genCfg) && is_file($genMain)) {
            $projects = require $genCfg;
            require $genMain;
            if (function_exists('generate')) {
                try {
                    generate($projects);
                    echo json_encode(['success' => true, 'message' => 'Successfully updated from GitHub and rebuilt sites']);
                } catch (Throwable $t) {
                    echo json_encode(['success' => false, 'message' => 'Updated files, but rebuild failed: ' . $t->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Generator entry function not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Generator files not found']);
        }
        exit;
    }
}

// Helper: download ZIP from GitHub
function downloadGithubZip($repoUrl, $branch, $destZip) {
  // Normalize URL like https://github.com/owner/repo
  $repoUrl = trim($repoUrl);
  if (!preg_match('#https?://github.com/([^/]+)/([^/]+)#i', $repoUrl, $m)) {
    return ['ok' => false, 'msg' => 'Invalid GitHub URL'];
  }
  $owner = $m[1];
  $repo = preg_replace('/\.git$/', '', $m[2]);
  $branch = trim($branch) ?: 'main';

  // Prefer codeload (direct download)
  $zipUrl = "https://codeload.github.com/{$owner}/{$repo}/zip/refs/heads/{$branch}";

  // Try cURL first
  if (function_exists('curl_init')) {
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $data = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($data !== false && $code >= 200 && $code < 300) {
      if (file_put_contents($destZip, $data) !== false) {
        return ['ok' => true];
      }
      return ['ok' => false, 'msg' => 'Failed to write ZIP file'];
    }
    return ['ok' => false, 'msg' => 'Download failed: ' . ($err ?: ('HTTP ' . $code))];
  }

  // Fallback to file_get_contents
  $data = @file_get_contents($zipUrl);
  if ($data === false) {
    return ['ok' => false, 'msg' => 'Download failed (file_get_contents)'];
  }
  if (file_put_contents($destZip, $data) !== false) {
    return ['ok' => true];
  }
  return ['ok' => false, 'msg' => 'Failed to write ZIP file'];
}

// Helper: extract ZIP
function extractZipTo($zipFile, $targetDir) {
  if (!class_exists('ZipArchive')) {
    return ['ok' => false, 'msg' => 'PHP ZipArchive extension is not available'];
  }
  $zip = new ZipArchive();
  if ($zip->open($zipFile) === true) {
    // Ensure target dir
    if (!is_dir($targetDir)) {
      @mkdir($targetDir, 0777, true);
    }
    if ($zip->extractTo($targetDir)) {
      $zip->close();
      return ['ok' => true];
    }
    $zip->close();
    return ['ok' => false, 'msg' => 'Failed to extract ZIP'];
  }
  return ['ok' => false, 'msg' => 'Failed to open ZIP'];
}

// Helper: copy directory recursively (whitelist), skipping sensitive files
function copyWhitelist($srcRoot, $dstRoot, array $allow = []) {
  $srcRoot = rtrim($srcRoot, DIRECTORY_SEPARATOR);
  $dstRoot = rtrim($dstRoot, DIRECTORY_SEPARATOR);
  foreach ($allow as $rel) {
    $src = $srcRoot . DIRECTORY_SEPARATOR . $rel;
    $dst = $dstRoot . DIRECTORY_SEPARATOR . $rel;
    if (!file_exists($src)) { continue; }
    // create destination dir
    if (is_dir($src)) {
      if (!is_dir($dst)) { @mkdir($dst, 0777, true); }
      $items = scandir($src);
      if ($items === false) { continue; }
      foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $s = $src . DIRECTORY_SEPARATOR . $it;
        $d = $dst . DIRECTORY_SEPARATOR . $it;
        // skip sensitive admin files
        if (preg_match('#admin\/(admin\.key|github\.config)$#', str_replace('\\','/',$d))) {
          continue;
        }
        if (is_dir($s)) {
          copyWhitelist($s, $d, ['.']); // copy all in this subdir
        } else {
          // ensure parent dir
          $parent = dirname($d);
          if (!is_dir($parent)) { @mkdir($parent, 0777, true); }
          @copy($s, $d);
        }
      }
    } else {
      // file copy
      $parent = dirname($dst);
      if (!is_dir($parent)) { @mkdir($parent, 0777, true); }
      @copy($src, $dst);
    }
  }
}

// Handle GitHub settings update
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_github_settings'])) {
    $newConfig = [
        'repo_url' => trim($_POST['repo_url'] ?? ''),
        'branch' => trim($_POST['branch'] ?? 'main')
    ];
    if (saveGithubConfig($githubConfigFile, $newConfig)) {
        $success = 'GitHub settings saved';
    } else {
        $error = 'Failed to save GitHub settings';
    }
    header('Location: index.php?tab=github');
    exit;
}

// Handle GitHub update (ZIP fallback, no exec required)
if ($isLoggedIn && isset($_GET['update_from_github'])) {
  $config = loadGithubConfig($githubConfigFile);
  $repoUrl = $config['repo_url'];
  $branch = $config['branch'];

  // Basic validation + ASCII-only branch suggestion
  if (empty($repoUrl)) {
    $error = 'GitHub repository URL is not configured';
  } else {
    $publicRoot = dirname(__DIR__, 3); // .../public
    $tmpDir = $publicRoot . DIRECTORY_SEPARATOR . 'tmp_github_update';
    $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'repo.zip';

    // Prepare temp dir
    if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }

    // Download ZIP
    $dl = downloadGithubZip($repoUrl, $branch, $zipPath);
    if (!$dl['ok']) {
      $error = 'Download failed: ' . ($dl['msg'] ?? '');
    } else {
      // Extract
      $extractDir = $tmpDir . DIRECTORY_SEPARATOR . 'extracted';
      $ex = extractZipTo($zipPath, $extractDir);
      if (!$ex['ok']) {
        $error = 'Extract failed: ' . ($ex['msg'] ?? '');
      } else {
        // Find top-level extracted folder (owner-repo-branch)
        $items = scandir($extractDir);
        $top = null;
        if (is_array($items)) {
          foreach ($items as $it) {
            if ($it === '.' || $it === '..') continue;
            if (is_dir($extractDir . DIRECTORY_SEPARATOR . $it)) {
              $top = $extractDir . DIRECTORY_SEPARATOR . $it;
              break;
            }
          }
        }
        if (!$top) {
          $error = 'Unexpected ZIP structure';
        } else {
          // Whitelist copy: themes, plugins, generator, scripts, styles
          $allowed = ['themes', 'plugins', 'generator', 'scripts', 'styles'];
          copyWhitelist($top, $publicRoot, $allowed);

          // Cleanup temp
          @unlink($zipPath);
          // (Leave tmp folder for troubleshooting if needed)

          // Rebuild sites by calling generator directly (no exec)
          $genCfg = $publicRoot . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'config.php';
          $genMain = $publicRoot . DIRECTORY_SEPARATOR . 'generator' . DIRECTORY_SEPARATOR . 'generate.php';
          if (is_file($genCfg) && is_file($genMain)) {
            $projects = require $genCfg;
            require $genMain;
            if (function_exists('generate')) {
              try {
                generate($projects);
                $success = 'Successfully updated from GitHub and rebuilt sites';
              } catch (Throwable $t) {
                $error = 'Updated files, but rebuild failed: ' . $t->getMessage();
              }
            } else {
              $error = 'Generator entry function not found';
            }
          } else {
            $error = 'Generator files not found';
          }
        }
      }
    }
  }
  header('Location: index.php?tab=github&msg=' . urlencode($success ?? $error ?? ''));
  exit;
}

$githubConfig = loadGithubConfig($githubConfigFile);
$currentTab = $_GET['tab'] ?? 'content';

// Blocks (shortcodes) system
$dataDir = dirname(__DIR__) . '/data';
$blocks = [];
if (is_dir($dataDir)) {
  foreach (scandir($dataDir) as $file) {
    if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
    if (preg_match('/^content-(\d+)\.html$/', $file, $m)) {
      $blocks[] = (int)$m[1];
    }
  }
  sort($blocks, SORT_NUMERIC);
}

// Create new block
if ($isLoggedIn && isset($_GET['new'])) {
  $nextId = empty($blocks) ? 1 : (max($blocks) + 1);
  $newFile = $dataDir . '/content-' . $nextId . '.html';
  if (!file_exists($newFile)) {
    file_put_contents($newFile, '<p>New content block #' . $nextId . '</p>');
  }
  header('Location: index.php?block=' . $nextId);
  exit;
}

// Save block content
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_id'], $_POST['content'])) {
  $blockId = (int)$_POST['block_id'];
  $filePath = $dataDir . '/content-' . $blockId . '.html';
  if (file_put_contents($filePath, $_POST['content']) !== false) {
    $success = 'Block saved successfully';
  } else {
    $error = 'Failed to save block';
  }
  header('Location: index.php?block=' . $blockId);
  exit;
}

// Load selected block
$currentBlock = isset($_GET['block']) ? (int)$_GET['block'] : (empty($blocks) ? null : $blocks[0]);
$currentContent = '';
if ($currentBlock !== null && $isLoggedIn) {
  $filePath = $dataDir . '/content-' . $currentBlock . '.html';
  if (file_exists($filePath)) {
    $currentContent = file_get_contents($filePath) ?: '';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TinyMCE Admin Panel</title>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f5f7;
    color: #222;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
  }

  .card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
  }

  h1, h2 {
    margin-top: 0;
  }

  .btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 10px 5px 10px 0;
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    border-radius: 8px;
    border: none;
    transition: all 0.25s ease;
  }

  .action-save {
    background: #4caf50;
    color: #fff;
    border: 1px solid #3a8f3e;
  }

  .action-save:hover {
    background: #44a047;
    transform: translateY(-1px);
  }

  .action-delete {
    background: #f44336;
    color: #fff;
    border: 1px solid #d32f2f;
  }

  .action-delete:hover {
    background: #e53935;
  }

  .b1 {
    background: #fff;
    border: 1px solid #d0d0d0;
    color: #222;
  }

  .b1:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  }

  .input {
    padding: 10px 14px;
    border-radius: 6px;
    margin: 10px 0;
    width: 100%;
    max-width: 300px;
    border: 1px solid #ccc;
    background: #fff;
  }

  .input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(106, 160, 255, 0.4);
  }

  .menu {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  .menu a {
    padding: 8px 14px;
    text-decoration: none;
    border-radius: 6px;
    color: #222;
    background: #ffffff;
    border: 1px solid #ccc;
    transition: all 0.25s ease;
  }

  .menu a:hover, .menu a.active {
    transform: translateY(-1px);
    background: #e8f8ff;
    border-color: #9dd8ff;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
  }

  .error {
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
  }

  .success {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
  }

  .login-form {
    max-width: 400px;
    margin: 100px auto;
  }

  .tabs {
    display: flex;
    gap: 5px;
    border-bottom: 2px solid #ddd;
    margin-bottom: 20px;
  }

  .tab {
    padding: 12px 24px;
    background: #f5f5f7;
    border: none;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    transition: all 0.2s;
  }

  .tab:hover {
    background: #e8e8ea;
    color: #222;
  }

  .tab.active {
    background: #fff;
    color: #007aff;
    border-bottom: 2px solid #007aff;
    margin-bottom: -2px;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
  }

  .form-group input,
  .form-group select {
    width: 100%;
    max-width: 500px;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
  }

  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: #007aff;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
  }

  .btn-update {
    background: #007aff;
    color: #fff;
    border: 1px solid #0051d5;
  }

  .btn-update:hover {
    background: #0051d5;
  }

  .info-box {
    background: #f0f9ff;
    border-left: 4px solid #007aff;
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
  }

  /* Toast notifications */
  .toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 350px;
  }

  .toast {
    background: #fff;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
    border-left: 4px solid #007aff;
  }

  .toast.success {
    border-left-color: #4caf50;
  }

  .toast.error {
    border-left-color: #f44336;
  }

  .toast-icon {
    font-size: 20px;
    flex-shrink: 0;
  }

  .toast-message {
    flex: 1;
    font-size: 14px;
    color: #222;
  }

  .toast-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    line-height: 1;
  }

  .toast-close:hover {
    color: #222;
  }

  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }

  .toast.hiding {
    animation: slideOut 0.3s ease-out forwards;
  }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<div class="login-form">
  <div class="card">
    <h1>Admin Login</h1>
    <?php if (isset($error)): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="password" class="input" placeholder="Password" required autofocus>
      <button type="submit" class="btn action-save">Login</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="container">
  <div class="header">
    <h1>TinyMCE Admin Panel</h1>
    <div>
      <?php if ($currentTab === 'github' && !empty($githubConfig['repo_url'])): ?>
        <a href="?update_from_github=1" class="btn btn-update" onclick="return confirm('Update from GitHub and rebuild all sites?')">Update from GitHub</a>
      <?php endif; ?>
      <a href="?logout" class="btn action-delete">Logout</a>
    </div>
  </div>

  <div class="tabs">
    <a href="?tab=content" class="tab <?php echo $currentTab === 'content' ? 'active' : ''; ?>">Content Blocks</a>
    <a href="?tab=github" class="tab <?php echo $currentTab === 'github' ? 'active' : ''; ?>">GitHub Settings</a>
  </div>

  <?php if (isset($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if (isset($success)): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <?php if (isset($_GET['msg'])): ?>
    <div class="<?php echo strpos($_GET['msg'], 'Success') !== false ? 'success' : 'error'; ?>">
      <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
  <?php endif; ?>

  <?php if ($currentTab === 'github'): ?>
  <!-- GitHub Settings Tab -->
  <div class="card">
    <h2>GitHub Repository Settings</h2>
    <div class="info-box">
      Configure your GitHub repository to enable automatic updates. The system will pull latest changes and rebuild all sites.
    </div>

    <form method="POST" id="githubSettingsForm">
      <input type="hidden" name="save_github_settings" value="1">

      <div class="form-group">
        <label for="repo_url">GitHub Repository URL</label>
        <input
          type="text"
          id="repo_url"
          name="repo_url"
          placeholder="https://github.com/username/repository"
          value="<?php echo htmlspecialchars($githubConfig['repo_url']); ?>"
          required
        >
        <small style="color:#666;display:block;margin-top:5px;">Example: https://github.com/semtio/Phorge</small>
      </div>

      <div class="form-group">
        <label for="branch">Branch</label>
        <input
          type="text"
          id="branch"
          name="branch"
          placeholder="main"
          value="<?php echo htmlspecialchars($githubConfig['branch']); ?>"
          required
        >
        <small style="color:#666;display:block;margin-top:5px;">Default: main</small>
      </div>

      <button type="submit" class="btn action-save">Save Settings</button>
    </form>

    <?php if (!empty($githubConfig['repo_url'])): ?>
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <h3>Current Configuration</h3>
    <p><strong>Repository:</strong> <?php echo htmlspecialchars($githubConfig['repo_url']); ?></p>
    <p><strong>Branch:</strong> <?php echo htmlspecialchars($githubConfig['branch']); ?></p>
    <p style="margin-top:20px;">
      <a href="?update_from_github=1" class="btn btn-update" onclick="return confirm('This will:\n1. Pull latest changes from GitHub\n2. Rebuild all sites\n\nContinue?')">
        🔄 Update Now
      </a>
    </p>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- Content Blocks Tab -->

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <h2 style="margin:0;">Shortcodes</h2>
      <a href="?new=1" class="btn action-add" title="Add new block">＋</a>
    </div>
    <?php if (empty($blocks)): ?>
      <p>No content blocks yet. Click ＋ to create first.</p>
    <?php else: ?>
    <div class="menu">
      <?php foreach ($blocks as $bid): ?>
        <a href="?block=<?php echo $bid; ?>"
           class="<?php echo ($bid === $currentBlock) ? 'active' : ''; ?>"
           data-shortcode="[content id=&quot;<?php echo $bid; ?>&quot;]">
           [content id="<?php echo $bid; ?>"]
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p style="font-size:12px;color:#555;">Click shortcode to copy it to clipboard and edit its content.</p>
  </div>

  <?php if ($currentBlock !== null): ?>
  <div class="card">
    <h2>Edit: [content id="<?php echo htmlspecialchars($currentBlock); ?>"]</h2>
    <form method="POST" id="contentForm">
      <input type="hidden" name="block_id" value="<?php echo htmlspecialchars($currentBlock); ?>">
      <textarea name="content" id="tinymce-editor"><?php echo htmlspecialchars($currentContent); ?></textarea>
      <button type="submit" class="btn action-save">Save Block</button>
    </form>
  </div>
  <?php endif; ?>

  <?php endif; // End content tab ?>
</div>

<!-- Toast notification container -->
<div class="toast-container" id="toastContainer"></div>

<script src="/plugins/tinymce/tinymce/tinymce.min.js"></script>
<script>
  // Toast notification system
  function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;

    const icon = type === 'success' ? '✓' : '✕';

    toast.innerHTML = `
      <span class="toast-icon">${icon}</span>
      <span class="toast-message">${message}</span>
      <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

    container.appendChild(toast);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      toast.classList.add('hiding');
      setTimeout(() => toast.remove(), 300);
    }, 5000);
  }

  // AJAX helper
  async function ajaxPost(url, data) {
    const formData = new FormData();
    for (const key in data) {
      formData.append(key, data[key]);
    }

    const response = await fetch(url, {
      method: 'POST',
      body: formData
    });

    return await response.json();
  }
</script>
<script>
  tinymce.init({
    selector: '#tinymce-editor',
    height: 500,
    menubar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
      'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic backcolor | ' +
      'alignleft aligncenter alignright alignjustify | ' +
      'bullist numlist outdent indent | removeformat | help',
    content_style: 'body { font-family:Arial,sans-serif; font-size:14px }'
  });

  // Copy shortcode to clipboard on click
  document.querySelectorAll('.menu a[data-shortcode]').forEach(function(a){
    a.addEventListener('click', function(e){
      const sc = a.getAttribute('data-shortcode');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(sc).catch(()=>{});
      } else {
        const ta = document.createElement('textarea');
        ta.value = sc; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch(err) {}
        document.body.removeChild(ta);
      }
    });
  });

  // GitHub Settings form AJAX
  const githubForm = document.getElementById('githubSettingsForm');
  if (githubForm) {
    githubForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = {
        repo_url: document.getElementById('repo_url').value,
        branch: document.getElementById('branch').value
      };
      try {
        const result = await ajaxPost('?ajax=save_github', data);
        showToast(result.message, result.success ? 'success' : 'error');
      } catch (error) {
        showToast('Ошибка сохранения настроек', 'error');
      }
    });
  }

  // Content Block form AJAX
  const contentForm = document.getElementById('contentForm');
  if (contentForm) {
    contentForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const content = tinymce.get('tinymce-editor').getContent();
      const blockId = document.querySelector('[name="block_id"]').value;
      const data = {
        block_id: blockId,
        content: content
      };
      try {
        const result = await ajaxPost('?ajax=save_block', data);
        showToast(result.message, result.success ? 'success' : 'error');
      } catch (error) {
        showToast('Ошибка сохранения контента', 'error');
      }
    });
  }

  // Update from GitHub button AJAX
  document.querySelectorAll('a[href*="update_from_github"]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!confirm('Update from GitHub and rebuild all sites?\n\nThis will:\n1. Download latest files from GitHub\n2. Extract theme, plugin, and generator updates\n3. Regenerate all sites from build/squidgamebler.org/ and build/squidgamebler.net/\n\nContinue?')) return;

      showToast('Обновление с GitHub...', 'success');
      try {
        const result = await ajaxPost('?ajax=update_github', {});
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
          setTimeout(() => location.reload(), 2000);
        }
      } catch (error) {
        showToast('Ошибка обновления с GitHub', 'error');
      }
    });
  });
</script>
<?php endif; ?>

</body>
</html>
