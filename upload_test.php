<?php
echo "<h2>Upload Diagnostic</h2>";

echo "<h3>1. Directory Check</h3>";
$uploadDir = __DIR__ . '/uploads/avatars';
echo "Path: " . $uploadDir . "<br>";
echo "Exists: " . (is_dir($uploadDir) ? "✓" : "✗") . "<br>";
echo "Writable: " . (is_writable($uploadDir) ? "✓" : "✗") . "<br>";
echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";

echo "<h3>2. POST/FILES Check</h3>";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST data: " . json_encode($_POST) . "<br>";
    echo "FILES: " . json_encode(array_keys($_FILES)) . "<br>";
    if (isset($_FILES['photo'])) {
        echo "File name: " . $_FILES['photo']['name'] . "<br>";
        echo "File size: " . $_FILES['photo']['size'] . "<br>";
        echo "File type: " . $_FILES['photo']['type'] . "<br>";
        echo "File error: " . $_FILES['photo']['error'] . "<br>";
    }
}

echo "<h3>3. Test Upload Form</h3>";
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="photo" accept="image/*">
    <button type="submit">Test Upload</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $testPath = $uploadDir . '/test_' . time() . '.jpg';
        if (move_uploaded_file($file['tmp_name'], $testPath)) {
            echo "<p style='color:green'>✓ Test upload successful! File: " . basename($testPath) . "</p>";
            unlink($testPath);
        } else {
            echo "<p style='color:red'>✗ move_uploaded_file failed</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Upload error: " . $file['error'] . "</p>";
    }
}
?>
