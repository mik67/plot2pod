<?php
// One-time script to generate admin password hash.
// Upload to server, visit in browser, copy the hash into seed.sql, then DELETE this file.
$hash = password_hash('emikA^70$P2P', PASSWORD_DEFAULT);
echo $hash;
// DELETE THIS FILE after use!
