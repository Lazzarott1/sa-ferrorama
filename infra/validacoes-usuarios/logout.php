<?php

// sair do sistema: apaga a sessão e volta pra landing page
session_start();
session_destroy();

// não deixa o navegador guardar a página
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Location: ../../index.html");
exit();
