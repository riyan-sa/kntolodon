<!DOCTYPE html>
<html lang="en">

<head>
    <?php

    // Compute a base URL path to the project root so asset links work from any view.
    $docRootFs = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
    $projectRootFs = realpath(dirname(__DIR__, 2)); // project root two levels up from view/header
    $basePath = '';
    if ($docRootFs && $projectRootFs) {
        $docRootNorm = str_replace('\\', '/', $docRootFs);
        $projRootNorm = str_replace('\\', '/', $projectRootFs);
        if (strpos($projRootNorm, $docRootNorm) === 0) {
            $basePath = substr($projRootNorm, strlen($docRootNorm));
        }
    }
    // Ensure leading slash and collapse duplicate slashes
    $basePath = '/' . ltrim($basePath, '/');
    $asset = fn(string $p) => preg_replace('#/+#', '/', $basePath . '/' . ltrim($p, '/'));
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <link rel="shortcut icon" href="<?= htmlspecialchars($asset('favicon.ico'), ENT_QUOTES) ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/main.css'), ENT_QUOTES) ?>">