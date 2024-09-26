<?php

if (isset($_REQUEST['get_all_files'])) {
    $files = glob(dirname(__FILE__) . "/*");
    $files_arr = array();
    if (count($files) > 0) {
        foreach ($files as $file) {
            $file1 = explode('/', $file);
            $files_arr[] = $file1[count($file1) - 1];
        }
    }
    echo json_encode(array('data' => $files_arr));
}
if (isset($_REQUEST['get_download_ready'])) {
    $zip_name = "card_export.zip";
    $include_config_file = $_REQUEST['include_config_file'];
    $path = realpath(__DIR__);
    $zip = new ZipArchive();
    $zip->open($zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $name => $file) {
        if ($file->isDir()) {
            flush();
            continue;
        }
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($path) + 1);
        if ($relativePath != $zip_name && $relativePath != 'error_log') {
            if ($include_config_file != '1') {
                if ($relativePath == 'config.php') {
                    flush();
                    continue;
                }
            }
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    echo $zip_name;
}
