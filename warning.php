<?php 
    date_default_timezone_set("Asia/Tashkent"); 
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once 'bot/db.php'; 


    $stmt = $conn->prepare("SELECT language FROM clients WHERE chat_id = ?");
    $stmt->execute([$_GET['user_id']]); 
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="css/index.css">
	<title>Warning</title>
</head>
<body>
	
</body>
</html>