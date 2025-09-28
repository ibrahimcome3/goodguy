<?php
// filepath: c:\wamp64\www\goodguy\admin\file_migration_utility.php
session_start();
require_once "../includes.php";

// Ensure only an admin can run this potentially destructive script
if (empty($_SESSION['admin_id'])) {
    die("<h2>Access Denied</h2><p>You must be logged in as an admin to use this utility. Please <a href='admin_login.php'>login here</a>.</p>");
}

$logMessages = [];
$executionDone = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $executionDone = true;
    $rootPath = realpath(__DIR__ . '/../products');
    $targetDirName = 'resized_600';

    if (!$rootPath || !is_dir($rootPath)) {
        $logMessages[] = "ERROR: The root products directory was not found at '{$rootPath}'.";
    } else {
        $logMessages[] = "Starting scan in: {$rootPath}";
        $logMessages[] = "--------------------------------------------------";

        try {
            // Use a recursive iterator to scan all directories efficiently
            $directoryIterator = new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

            $foundAndProcessed = 0;

            foreach ($iterator as $file) {
                // We are only interested in directories with the specific name
                if ($file->isDir() && $file->getFilename() === $targetDirName) {
                    $sourceDir = $file->getRealPath();
                    $destinationDir = dirname($sourceDir); // The parent directory

                    $logMessages[] = "Found target directory: {$sourceDir}";
                    $logMessages[] = "Destination directory: {$destinationDir}";

                    // Iterate over the files inside the 'resizes_600' directory
                    $filesToCopy = new DirectoryIterator($sourceDir);
                    $filesCopiedCount = 0;
                    foreach ($filesToCopy as $fileInSource) {
                        if ($fileInSource->isFile()) {
                            $sourceFile = $fileInSource->getRealPath();
                            $destinationFile = $destinationDir . DIRECTORY_SEPARATOR . $fileInSource->getFilename();

                            // Copy the file
                            if (copy($sourceFile, $destinationFile)) {
                                $logMessages[] = "  -> Copied '{$fileInSource->getFilename()}' successfully.";
                                $filesCopiedCount++;
                            } else {
                                $logMessages[] = "  -> ERROR: Failed to copy '{$fileInSource->getFilename()}'.";
                            }
                        }
                    }

                    if ($filesCopiedCount > 0) {
                        $logMessages[] = "Finished processing. Copied {$filesCopiedCount} file(s).";
                        $foundAndProcessed++;
                    } else {
                        $logMessages[] = "Directory was empty. Nothing to copy.";
                    }
                    $logMessages[] = "--------------------------------------------------";
                }
            }

            if ($foundAndProcessed === 0) {
                $logMessages[] = "Scan complete. No directories named '{$targetDirName}' were found.";
            } else {
                $logMessages[] = "Scan complete. Processed {$foundAndProcessed} director(y/ies).";
            }

        } catch (Exception $e) {
            $logMessages[] = "An unexpected error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>File Migration Utility</title>
    <?php include 'admin-header.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <div class="container-fluid" data-layout="container">
            <?php include 'includes/admin_navbar.php'; ?>
            <div class="content">
                <div class="card">
                    <div class="card-header bg-body-tertiary">
                        <h3>File Migration Utility</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">Warning!</h5>
                            <p>This script will scan the <code>/products</code> directory. For every sub-directory named
                                <code>resizes_600</code>, it will <strong>copy</strong> all files from within it to its
                                parent directory.
                            </p>
                            <p class="mb-0">This action is not easily reversible. It is recommended to <strong>back up
                                    your <code>/products</code> directory</strong> before proceeding.</p>
                        </div>

                        <form method="POST" action="">
                            <button type="submit" class="btn btn-danger">Start File Migration</button>
                        </form>

                        <?php if ($executionDone): ?>
                            <hr class="my-4">
                            <h4>Execution Log:</h4>
                            <pre class="p-3 border rounded bg-body-secondary"
                                style="max-height: 500px; overflow-y: auto;"><?= htmlspecialchars(implode("\n", $logMessages)) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/admin_footer.php'; ?>
</body>

</html>