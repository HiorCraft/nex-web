<?php
$serverId = "1411323559225069620";
$url = "https://discord.com/api/guilds/$serverId/widget.json";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

header("Content-Type: application/json");
echo $response;
