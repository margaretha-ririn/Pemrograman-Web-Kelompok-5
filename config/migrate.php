<?php
// config/migrate.php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>SkillNex Database Migration Script</h2>";
echo "<p>Running migrations...</p>";

try {
    // 1. Check & Alter table payments (metode)
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'metode'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN metode VARCHAR(50) NULL AFTER jumlah");
        echo "<span style='color: green;'>✔ Added column 'metode' to table 'payments'.</span><br>";
    } else {
        echo "<span style='color: orange;'>ℹ Column 'metode' already exists in table 'payments'.</span><br>";
    }

    // 2. Check & Alter table payments (invoice)
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'invoice'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN invoice VARCHAR(50) NULL AFTER status");
        echo "<span style='color: green;'>✔ Added column 'invoice' to table 'payments'.</span><br>";
    } else {
        echo "<span style='color: orange;'>ℹ Column 'invoice' already exists in table 'payments'.</span><br>";
    }

    // 3. Check & Create table global_chats
    $stmt = $pdo->query("SHOW TABLES LIKE 'global_chats'");
    if ($stmt->rowCount() === 0) {
        $sql = "CREATE TABLE `global_chats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `pesan` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_global_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);
        echo "<span style='color: green;'>✔ Table 'global_chats' successfully created.</span><br>";
    } else {
        echo "<span style='color: orange;'>ℹ Table 'global_chats' already exists.</span><br>";
    }

    echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p><a href='../pages/dashboard.php'>Back to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Migration Failed!</h3>";
    echo "<p>Error details: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
