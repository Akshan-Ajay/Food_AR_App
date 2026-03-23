========================================
     Cafe PHP + SQL Server Project
========================================

PROJECT OVERVIEW
----------------
This project is a Cafe Management System developed using PHP and Microsoft SQL Server.
It allows administrators to manage menu items, reservations, and notifications, while
customers can view food items (including 3D AR models), place orders, and give feedback.

----------------------------------------

REQUIREMENTS
------------
1. Web Server & PHP
   - XAMPP (Recommended): https://www.apachefriends.org/index.html
     OR WampServer
   - Apache and PHP must be running

2. Microsoft SQL Server
   - SQL Server Express or Full Version
   - SQL Server Management Studio (SSMS)

3. ODBC Driver for SQL Server
   - Install Microsoft ODBC Driver 17 or 18 for SQL Server
   - Download:
     https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server

4. SQLSRV PHP Drivers
   - Required for PHP to connect with SQL Server
   - Download from:
     https://github.com/microsoft/msphpsql

----------------------------------------

STEP 1: DOWNLOAD & SETUP PROJECT
--------------------------------
1. Download the file: food_ar_app.zip
2. Extract all files
3. Copy the folder: food_ar_app
4. Paste it into:

   C:\xampp\htdocs

----------------------------------------

STEP 2: INSTALL SQL SERVER & DRIVER
-----------------------------------
1. Install Microsoft SQL Server and SSMS
2. Install ODBC Driver 17 or 18
3. Restart your PC if required

----------------------------------------

STEP 3: SETUP DATABASE
----------------------

OPTION A: Import SQL Script
---------------------------
1. Open SQL Server Management Studio (SSMS)
2. Connect to your SQL Server instance
3. Create a new database:

   CafeManagementAR

4. Open the SQL file:

   C:\xampp\htdocs\food_ar_app\database\db_structure\cafeAr.sql

5. Execute the script to create tables and insert sample data

---------------------------

OPTION B: Restore Database (Backup File)
----------------------------------------
1. Open SQL Server Management Studio (SSMS)
2. Right-click on "Databases"
3. Click "Restore Database"

4. Select:
   Device → Click "..." → Add

5. Choose the backup file (.bak) from:

   C:\xampp\htdocs\food_ar_app\database\cafeAr.bak

6. Select the backup file and click OK

7. Set destination database name:

   CafeManagementAR

8. Click "OK" to restore

✔ Database will be restored with all tables and data

----------------------------------------

STEP 4: CONFIGURE PHP DATABASE CONNECTION
-----------------------------------------
Open db.php in the project folder and update:

-----------------------------------------
<?php
$serverName = "AKSHAN\\SQLEXPRESS"; // Change to your server name

$connectionOptions = [
    "Database" => "CafeManagementAR",
    "Uid" => "sa",
    "PWD" => "YourStrongPassword",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}
?>
-----------------------------------------

NOTE:
- Replace "AKSHAN" with your PC name or SQL Server instance
- Replace password with your SQL Server password

----------------------------------------

STEP 5: ENABLE SQLSRV DRIVER IN PHP
-----------------------------------
1. Check your PHP version:
   http://localhost/info.php

2. Identify:
   - PHP Version (e.g., 8.2)
   - Thread Safety (TS or NTS)
   - Architecture (x64 or x86)

3. Download matching drivers from GitHub:
   https://github.com/microsoft/msphpsql

Example (PHP 8.2, NTS, x86):
   php_sqlsrv_82_nts_x86.dll
   php_pdo_sqlsrv_82_nts_x86.dll

4. Copy both files into:

   C:\xampp\php\ext

5. Open php.ini and add:

   extension=php_sqlsrv_82_nts_x86.dll
   extension=php_pdo_sqlsrv_82_nts_x86.dll

6. Save file and restart Apache

----------------------------------------

STEP 6: RUN THE PROJECT
-----------------------
1. Open browser
2. Go to:

   http://localhost/food_ar_app/

3. The system should load successfully

----------------------------------------

LOGIN CREDENTIALS
-----------------

Admin Login:
Email: admin1@example.com
Password: admin123

Customer Login:
Email: john@example.com
Password: hashedpassword

----------------------------------------

STEP 7: COMMON ISSUES & SOLUTIONS
--------------------------------

Problem: Could not connect to server
Solution:
- Check SQL Server is running
- Verify server name, username, and password

Problem: sqlsrv_connect() not found
Solution:
- SQLSRV driver not enabled
- Check php.ini and restart Apache

Problem: Driver not loading
Solution:
- Check TS/NTS mismatch
- Check x64/x86 mismatch

Problem: Database restore failed
Solution:
- Check .bak file path
- Ensure SQL Server service is running
- Verify permissions

----------------------------------------

FINAL CHECKLIST
---------------
✔ Project copied to htdocs
✔ SQL Server installed and running
✔ Database imported OR restored
✔ ODBC Driver installed
✔ SQLSRV drivers installed correctly
✔ php.ini updated
✔ Apache restarted
✔ Project runs successfully

----------------------------------------

        END OF README GUIDE
========================================

