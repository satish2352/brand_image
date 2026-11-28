<?php
/**
 * TEMPORARY upload diagnostic. Delete this file once the import works.
 *
 * Reports what PHP on this server actually allows, whether it has a writable
 * temp directory, and — via the test form — the raw UPLOAD_ERR code for a real
 * upload. Reachable only with the token below so it is not a public info leak.
 */

$TOKEN = 'bi-upload-check-2026';

if (($_GET['token'] ?? '') !== $TOKEN) {
    http_response_code(404);
    exit('Not found');
}

$errorNames = [
    UPLOAD_ERR_OK         => 'OK — PHP accepted the file',
    UPLOAD_ERR_INI_SIZE   => '1 INI_SIZE — larger than upload_max_filesize',
    UPLOAD_ERR_FORM_SIZE  => '2 FORM_SIZE — larger than the form MAX_FILE_SIZE field',
    UPLOAD_ERR_PARTIAL    => '3 PARTIAL — upload was cut off part way',
    UPLOAD_ERR_NO_FILE    => '4 NO_FILE — no file was sent',
    UPLOAD_ERR_NO_TMP_DIR => '6 NO_TMP_DIR — PHP has no temp directory to write to',
    UPLOAD_ERR_CANT_WRITE => '7 CANT_WRITE — could not write to disk (full? permissions?)',
    UPLOAD_ERR_EXTENSION  => '8 EXTENSION — a PHP extension blocked the upload',
];

$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();

// Prove writability rather than trusting is_writable(), which lies under
// open_basedir and some hardened hosts.
$writeProof = 'not attempted';
$probe = @tempnam($tmpDir, 'chk');
if ($probe === false) {
    $writeProof = 'FAILED — cannot create a file in the temp directory';
} else {
    $writeProof = 'OK — created and removed ' . $probe;
    @unlink($probe);
}

$rows = [
    'PHP version'          => PHP_VERSION,
    'Server API (handler)' => PHP_SAPI,
    'post_max_size'        => ini_get('post_max_size'),
    'upload_max_filesize'  => ini_get('upload_max_filesize'),
    'max_file_uploads'     => ini_get('max_file_uploads'),
    'memory_limit'         => ini_get('memory_limit'),
    'max_input_time'       => ini_get('max_input_time'),
    'file_uploads enabled' => ini_get('file_uploads') ? 'Yes' : 'NO — uploads are switched off',
    'upload_tmp_dir (ini)' => ini_get('upload_tmp_dir') ?: '(empty — using system default)',
    'temp dir in use'      => $tmpDir,
    'temp dir exists'      => is_dir($tmpDir) ? 'Yes' : 'NO',
    'temp dir write test'  => $writeProof,
    'open_basedir'         => ini_get('open_basedir') ?: '(none)',
    'Free disk space'      => is_numeric($f = @disk_free_space(__DIR__))
                                ? round($f / 1048576) . ' MB'
                                : 'unknown',
    'Loaded .user.ini?'    => (ini_get('post_max_size') === '64M')
                                ? 'Yes — .user.ini values are active'
                                : 'NO — .user.ini is being ignored, set limits in cPanel instead',
    'ZipArchive available' => class_exists('ZipArchive') ? 'Yes' : 'NO — the images ZIP cannot be opened',
];

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upload diagnostic</title>
<style>
  body { font: 15px/1.6 system-ui, sans-serif; margin: 2rem auto; max-width: 820px; padding: 0 1rem; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
  th, td { text-align: left; padding: .5rem .7rem; border-bottom: 1px solid #ddd; vertical-align: top; }
  th { width: 40%; font-weight: 600; }
  code { background: #f4f4f5; padding: .1rem .3rem; border-radius: 3px; word-break: break-all; }
  .bad { color: #b00020; font-weight: 600; }
  .good { color: #0a7a2f; font-weight: 600; }
  fieldset { border: 1px solid #ddd; border-radius: 6px; padding: 1rem; }
  .warn { background: #fff4e5; border: 1px solid #f0c187; padding: .8rem; border-radius: 6px; }
</style>

<h1>Upload diagnostic</h1>
<p class="warn"><strong>Delete this file</strong> (<code>public/upload-check.php</code>) once the import is working.</p>

<table>
<?php foreach ($rows as $label => $value): ?>
  <tr>
    <th><?= htmlspecialchars($label) ?></th>
    <td><?php
      $isBad = preg_match('/^(NO|FAILED)\b/', (string) $value);
      echo '<span class="' . ($isBad ? 'bad' : '') . '">' . htmlspecialchars((string) $value) . '</span>';
    ?></td>
  </tr>
<?php endforeach; ?>
</table>

<h2>Test a real upload</h2>
<p>Pick the same two files you tried in the importer. This reports the exact code PHP returns.</p>

<form method="post" enctype="multipart/form-data" action="?token=<?= urlencode($TOKEN) ?>">
  <fieldset>
    <p><label>Excel / CSV file: <input type="file" name="file"></label></p>
    <p><label>Images ZIP: <input type="file" name="images_zip"></label></p>
    <p><button type="submit">Upload test</button></p>
  </fieldset>
</form>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
  <h2>Result</h2>
  <?php if (empty($_FILES)): ?>
    <p class="bad">$_FILES is completely empty — the request body exceeded
    <code>post_max_size</code> (<?= htmlspecialchars(ini_get('post_max_size')) ?>)
    and PHP discarded everything.</p>
  <?php else: ?>
    <table>
      <tr><th>Field</th><td>Details</td></tr>
      <?php foreach ($_FILES as $field => $u): ?>
        <tr>
          <th><?= htmlspecialchars($field) ?></th>
          <td>
            name: <code><?= htmlspecialchars((string) $u['name']) ?></code><br>
            size: <?= number_format(((int) $u['size']) / 1048576, 2) ?> MB<br>
            tmp_name: <code><?= htmlspecialchars((string) $u['tmp_name']) ?: '(none)' ?></code><br>
            tmp file exists: <?= ($u['tmp_name'] && is_file($u['tmp_name'])) ? 'Yes' : 'No' ?><br>
            <strong class="<?= $u['error'] === UPLOAD_ERR_OK ? 'good' : 'bad' ?>">
              <?= htmlspecialchars($errorNames[$u['error']] ?? ('unknown code ' . $u['error'])) ?>
            </strong>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>
