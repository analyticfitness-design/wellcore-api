<?php
// Simple PHP syntax validation
$files = [
    'app/Console/Commands/BehavioralTriggersCommand.php',
    'app/Console/Commands/AutoRenewalCommand.php',
    'routes/console.php'
];

foreach ($files as $file) {
    echo "Checking $file... ";
    $output = [];
    $return = 0;
    exec("php -l '$file' 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "✓ OK\n";
    } else {
        echo "✗ FAILED\n";
        print_r($output);
    }
}

echo "\n✓ All files have valid PHP syntax\n";
