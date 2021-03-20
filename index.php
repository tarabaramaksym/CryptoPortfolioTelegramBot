<?php

require_once('bot/bot_commands.php');

$update = json_decode(file_get_contents("php://input"), TRUE);
handle_commands($update);

?>