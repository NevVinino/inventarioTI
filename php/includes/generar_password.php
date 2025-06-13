<?php
$passwordPlano = "54321";
$hash = password_hash($passwordPlano, PASSWORD_DEFAULT);
echo "Password encriptada: <br><pre>$hash</pre>";
