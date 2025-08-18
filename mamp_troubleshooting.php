<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAMP Troubleshooting Guide</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 MAMP Troubleshooting Guide</h1>
    
    <div class="error">
        <h3>❌ Current Issue</h3>
        <p><strong>MySQL is not running!</strong> This is why phpMyAdmin and your application can't connect to the database.</p>
    </div>

    <h2>📋 Step-by-Step Solution</h2>

    <div class="step">
        <h3>Step 1: Check MAMP Application</h3>
        <ol>
            <li>Open the <strong>MAMP</strong> application (not MAMP PRO)</li>
            <li>Look at the server status indicators</li>
            <li>You should see two lights/indicators for Apache and MySQL</li>
            <li>If MySQL shows red or stopped, it needs to be started</li>
        </ol>
    </div>

    <div class="step">
        <h3>Step 2: Start MAMP Servers</h3>
        <ol>
            <li>In MAMP, click the <strong>"Start Servers"</strong> button</li>
            <li>Wait for both Apache and MySQL to show green/running status</li>
            <li>If MySQL fails to start, try the troubleshooting below</li>
        </ol>
    </div>

    <div class="step">
        <h3>Step 3: Check MAMP Ports</h3>
        <ol>
            <li>In MAMP, click <strong>"Preferences"</strong></li>
            <li>Go to the <strong>"Ports"</strong> tab</li>
            <li>Default ports should be:
                <ul>
                    <li>Apache: 8888</li>
                    <li>MySQL: 8889</li>
                </ul>
            </li>
            <li>If they're different, note them down for configuration</li>
        </ol>
    </div>

    <h2>🛠️ Common MySQL Startup Issues</h2>

    <div class="warning">
        <h4>Issue 1: Port Already in Use</h4>
        <p>Another MySQL service might be running. Kill other MySQL processes:</p>
        <pre>sudo pkill -f mysqld
brew services stop mysql</pre>
        <p>Then restart MAMP</p>
    </div>

    <div class="warning">
        <h4>Issue 2: Corrupted MySQL Data</h4>
        <p>If MySQL won't start, the data might be corrupted:</p>
        <ol>
            <li>Stop MAMP completely</li>
            <li>Go to <code>/Applications/MAMP/db/mysql56/</code> (or mysql57/mysql80 depending on version)</li>
            <li>Rename the folder to <code>mysql56_backup</code></li>
            <li>Create a new empty <code>mysql56</code> folder</li>
            <li>Restart MAMP</li>
        </ol>
        <p><strong>Warning:</strong> This will delete all existing databases!</p>
    </div>

    <div class="warning">
        <h4>Issue 3: Permission Problems</h4>
        <p>Fix MAMP folder permissions:</p>
        <pre>sudo chown -R $(whoami) /Applications/MAMP/</pre>
    </div>

    <h2>🔄 Alternative: Use Built-in PHP Server</h2>
    
    <div class="info">
        <p>If MAMP continues to have issues, you can use PHP's built-in server with SQLite instead of MySQL:</p>
        <ol>
            <li>Open Terminal</li>
            <li>Navigate to your project: <code>cd /Applications/MAMP/htdocs/project2</code></li>
            <li>Run: <code>php -S localhost:8000</code></li>
            <li>Visit: <code>http://localhost:8000</code></li>
        </ol>
        <p>I can help you convert the database to SQLite if needed.</p>
    </div>

    <h2>✅ Verification Steps</h2>

    <div class="step">
        <h3>After Starting MAMP:</h3>
        <ol>
            <li><a href="http://localhost:8888/" target="_blank">Test MAMP Start Page</a></li>
            <li><a href="http://localhost:8888/phpMyAdmin/" target="_blank">Test phpMyAdmin</a></li>
            <li><a href="test_db_connection.php" target="_blank">Test Database Connection</a></li>
            <li><a href="setup_database_mamp.php" target="_blank">Setup Database</a></li>
            <li><a href="index.php" target="_blank">Test Your Application</a></li>
        </ol>
    </div>

    <h2>📞 Need More Help?</h2>
    
    <div class="info">
        <p>If you're still having issues, please check:</p>
        <ul>
            <li>MAMP version (MAMP vs MAMP PRO)</li>
            <li>macOS version and any recent updates</li>
            <li>Any antivirus software that might block MAMP</li>
            <li>Other local development environments (XAMPP, Docker, etc.)</li>
        </ul>
        <p>You can also try <strong>XAMPP</strong> as an alternative to MAMP if problems persist.</p>
    </div>

    <div class="step">
        <h3>Quick MAMP Restart Procedure:</h3>
        <ol>
            <li>Quit MAMP completely</li>
            <li>Wait 10 seconds</li>
            <li>Open MAMP again</li>
            <li>Click "Start Servers"</li>
            <li>Wait for both lights to turn green</li>
        </ol>
    </div>

</body>
</html>