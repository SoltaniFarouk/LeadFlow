<?php

function extractDepNumber($filename) {
    // Remove the file extension
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    // Split into parts using '-'
    $parts = explode('-', $nameWithoutExt);
    
    // Combine the relevant parts (DEP + numbers)
    $depNumber = str_replace('-', '', $parts[1] . $parts[2] . $parts[3]);
    
    return $depNumber;
}

function extractFirstPart($filename) {
    $parts = explode('-', $filename); // Split by '-'
    return $parts[0]; // First segment (250203)
}

function extractDepPart($filename) {
    $parts = explode('-', $filename); // Split by '-'
    return $parts[1]; // Second segment (DEP97)
}

function extractLastNumber($filename) {
    $parts = explode('-', $filename); // Split by '-'
    $lastPart = end($parts); // Gets "14.txt"
    return (int)str_replace('.txt', '', $lastPart); // Removes ".txt" and converts to integer
}

function extractMiddleNumberRegex($filename) {
    if (preg_match('/-(\d{3})-/', $filename, $matches)) {
        return $matches[1]; // Returns "335"
    }
    return null; // If pattern not found
}

$directory = '/var/www/html/sans404_01-2025/';
if (is_dir($directory)) {
    if ($dh = opendir($directory)) {
        while (($file = readdir($dh)) !== false) {
            if ($file !== '.' && $file !== '..' && is_dir($directory . $file)) {
                $subdirectory = $directory . $file . '/';
                $name_db = 'dbx_' . strtolower($file);

                echo "name_db: $name_db \n"; 
                echo "Subdirectory: $subdirectory \n";
                
                chdir($subdirectory);       
                
                if (!is_dir($subdirectory)) {  
                    die("Directory not found.\n");  
                }      
                
                $txtFiles = glob($subdirectory . "*.txt");
        
                if (empty($txtFiles)) {
                    echo "No TXT files found.\n";
                } else {
                    foreach ($txtFiles as $file_txt) {
                        $current_file = basename($file_txt);
                        echo $current_file . " \n ";
                        
                        // Extract values
                        $batch_name = extractDepNumber($current_file);
                        echo " - batch_name: $batch_name \n";
                        echo " - file_txt: $file_txt \n";
                        $batch_split_date = extractFirstPart($current_file);
                        echo " - batch_split_date: $batch_split_date \n";
                        $department = extractDepPart($current_file);
                        echo " - department: $department \n";
                        $database_name = $name_db;
                        echo " - database_name: $database_name \n";
                        $table_name = 'tb_batch_' . extractLastNumber($current_file);
                        echo " - table_name: $table_name \n";
                        $phone_prefix = extractMiddleNumberRegex($current_file);
                        echo " - phone_prefix: $phone_prefix \n";
                        
                        // Database operations would go here
                    }
                } 
            }
        }
        closedir($dh);
    } else {
        echo "Could not open directory.";
    }
} else {
    echo "Invalid directory path.";
}
?>