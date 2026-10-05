<?php

$password = "password";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Password Hash</h3>";
echo "<p>Password: password</p>";
echo "<p>Hash:</p>";
echo "<textarea rows='3' cols='100'>";
echo $hash;
echo "</textarea>";