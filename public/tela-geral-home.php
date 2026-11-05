<?php
include __DIR__ . '/../infra/validacoes-usuarios/verifica-login.php';

date_default_timezone_set('America/Sao_Paulo');

$meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$data_hoje = 'Dia ' . date('d') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');

$resumo = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT
    (SELECT COUNT(*) FROM trens WHERE status_trem <> 'INATIVO') AS trens,
    (SELECT COUNT(*) FROM trilhos WHERE status_trilho <> 'INATIVO') AS trilhos,
    (SELECT COUNT(*) FROM sensores WHERE status_sensor <> 'INATIVO') AS sensores,
    (SELECT COUNT(*) FROM sensores WHERE status_sensor = 'ALERTA') AS alertas"));

$atalhos = [
    ['dashboard/tela-dashboard.php', 'speedometer2', 'Dashboard', 'Acompanhe todas as leituras enviadas pelos sensores.'],
    ['sensores/tela-cadastro-sensores.php', 'broadcast', 'Sensores', 'Cadastre e acompanhe os sensores dos trens e dos trilhos.'],
    ['trens/tela-trens.php', 'train-front', 'Trens', 'Veja os trens, seus trilhos e os responsáveis.'],
    ['trilhos/tela-cadastro-trilhos.php', 'signpost-split', 'Trilhos', 'Gerencie as rotas, a distância e a localização das estações.'],
    ['relatorios/relatorios.php', 'file-earmark-bar-graph', 'Relatórios', 'Gere relatórios da situação da ferrovia.'],
];

if ($admin) {
    $atalhos[] = ['usuarios/tela-cadastro-user.php', 'people', 'Usuários', 'Cadastre operadores e administradores.'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/img/style/home.css">
</head>

<body class="bg-light">

    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">

        <div class="home-topo mb-4">
            <h1 class="mb-1">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h1>
            <p><?php echo $data_hoje; ?> · <?php echo $admin ? 'Administrador' : 'Operador'; ?></p>
        </div>

        <?php if (isset($_GET['acesso'])) { ?>
            <div class="alert alert-warning rounded-1" style="border-left: 4px solid #daa301;">Você não tem permissão para acessar essa tela.</div>
        <?php } ?>

        <div class="row g-3 mb-4">

            <div class="col-lg-8">
                <p class="home-titulo mb-2">RESUMO DA OPERAÇÃO</p>
                <div class="row g-3">
                    <?php foreach ([['trens', 'train-front', 'Trens em operação'], ['trilhos', 'signpost-split', 'Trilhos em uso'], ['sensores', 'broadcast', 'Sensores ativos'], ['alertas', 'exclamation-triangle', 'Sensores em alerta']] as [$chave, $icone, $texto]) { ?>
                        <div class="col-6 col-md-3">
                            <div class="cartao-resumo <?php if ($chave == 'alertas' && $resumo['alertas'] > 0) echo 'alerta'; ?>">
                                <i class="bi bi-<?php echo $icone; ?>"></i>
                                <strong><?php echo $resumo[$chave]; ?></strong>
                                <span><?php echo $texto; ?></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-lg-4">
                <p class="home-titulo mb-2">CLIMA NA FERROVIA</p>
                <div class="cartao-clima" id="clima">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="temperatura" id="climaTemp">--</div>
                            <div id="climaTexto">Consultando o clima...</div>
                        </div>
                        <i class="bi bi-cloud icone" id="climaIcone"></i>
                    </div>
                    <div class="mt-2"><i class="bi bi-geo-alt"></i> Joinville - SC <small id="climaExtra"></small></div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small id="climaHora"></small>
                        <button class="btn btn-sm" onclick="carregarClima()" title="Atualizar"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small d-none" id="climaAviso"></div>
                </div>
            </div>

        </div>

        <p class="home-titulo mb-2">ACESSO RÁPIDO</p>
        <div class="row g-3 mb-4">
            <?php foreach ($atalhos as [$link, $icone, $titulo, $texto]) { ?>
                <div class="col-sm-6 col-lg">
                    <a href="<?php echo $link; ?>" class="atalho">
                        <i class="bi bi-<?php echo $icone; ?>"></i>
                        <div><h6><?php echo $titulo; ?></h6><p><?php echo $texto; ?></p></div>
                    </a>
                </div>
            <?php } ?>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <p class="home-titulo mb-0">CIRCUITO DA FERROVIA</p>
            <span class="badge rounded-1 text-bg-light border" id="local-trem">Trem parado</span>
        </div>
        <div class="bg-white border rounded-2 p-2">
            <svg viewBox="0 0 1661 947" class="mapa-ferrovia" role="img" aria-label="Circuito da ferrovia">
                <g class="via">
                    <path id="seg-principal" d="M430 72 L446 72 L462 72 L478 71 L494 71 L510 71 L526 71 L542 71 L558 71 L574 71 L590 71 L606 71 L622 70 L638 70 L654 70 L670 70 L686 70 L702 70 L718 70 L734 70 L750 70 L766 70 L782 70 L798 70 L814 70 L830 71 L846 71 L862 71 L878 71 L894 71 L910 71 L926 71 L942 71 L958 71 L974 71 L990 71 L1006 71 L1022 71 L1038 72 L1054 72 L1070 72 L1086 72 L1102 72 L1118 72 L1134 72 L1150 73 L1166 76 L1181 79 L1197 84 L1212 90 L1226 97 L1240 104 L1253 113 L1266 123 L1278 133 L1290 145 L1300 156 L1310 169 L1319 182 L1327 196 L1334 210 L1341 225 L1346 240 L1351 255 L1354 271 L1356 287 L1358 303 L1358 319 L1358 335 L1356 351 L1355 367 L1349 381 L1345 397 L1340 412 L1333 426 L1326 441 L1318 454 L1308 467 L1298 480 L1287 491 L1275 502 L1263 512 L1250 522 L1236 530 L1222 538 L1208 544 L1193 550 L1177 554 L1162 558 L1146 560 L1130 561 L1114 562 L1098 562 L1082 562 L1066 561 L1050 560 L1034 556 L1019 551 L1005 544 L991 536 L978 527 L965 518 L952 508 L939 499 L926 490 L913 480 L900 471 L887 462 L874 453 L860 445 L845 440 L829 436 L813 434 L797 434 L781 434 L765 434 L749 434 L733 434 L717 434 L701 434 L685 434 L669 434 L653 434 L637 434 L621 434 L605 434 L589 434 L573 434 L557 434 L541 434 L525 434 L509 434 L493 434 L477 434 L461 434 L445 434 L429 434 L413 434 L397 434 L381 434 L365 434 L349 434 L333 434 L317 434 L301 434 L285 434 L269 435 L253 437 L238 440 L223 446 L208 452 L194 460 L180 468 L168 478 L156 489 L145 500 L135 513 L125 525 L117 539 L109 553 L102 568 L97 583 L92 598 L88 613 L86 629 L84 645 L84 661 L84 677 L87 693 L90 709 L94 724 L100 739 L107 753 L115 767 L125 780 L135 792 L146 804 L158 814 L171 824 L185 832 L199 840 L213 846 L228 852 L244 856 L259 860 L275 862 L291 863 L307 863 L323 864 L339 864 L355 864 L371 864 L387 864 L403 864 L419 864 L435 864 L451 864 L467 863 L483 863 L499 863 L515 863 L531 863 L547 863 L563 863 L579 863 L595 863 L611 863 L627 863 L643 862 L659 862 L675 862 L691 862 L707 862 L723 862 L739 862 L755 862 L771 862 L787 862 L803 862 L819 862 L835 862 L851 862 L867 862 L883 862 L899 862 L915 862 L931 862 L947 862 L963 862 L979 862 L995 862 L1011 862 L1027 862 L1043 862 L1059 862 L1075 862 L1091 862 L1092 862"/>
                    <path id="seg-oeste" d="M430 72 L414 72 L398 72 L382 72 L366 72 L350 72 L334 72 L318 72 L302 72 L286 72 L270 73 L254 74 L238 77 L223 81 L208 86 L193 92 L178 99 L164 107 L151 115 L138 125 L126 135 L114 146 L104 158 L94 171 L84 184 L76 197 L68 211 L62 226 L56 241 L51 256 L47 272 L45 287 L43 303 L43 319 L43 335 L44 351 L47 367 L51 383 L55 398 L61 413 L67 428 L75 442 L83 455 L92 468 L102 481 L113 493 L124 504 L136 515 L148 525 L161 534 L175 543 L189 550 L204 556 L220 559 L236 560 L252 560 L268 560 L284 560 L300 560 L316 560 L332 560 L346 560"/>
                    <path id="seg-diagonal-norte" d="M430 72 L446 73 L461 78 L476 85 L489 94 L502 103 L515 112 L528 122 L541 131 L554 140 L567 150 L580 159 L593 168 L606 177 L619 187 L632 196 L645 205 L659 214 L673 220 L688 226 L704 229 L720 231 L736 232 L752 232 L768 232 L784 232 L800 232 L816 232 L832 232 L848 232 L864 232 L880 233 L896 236 L911 241 L925 248 L939 257 L951 267 L963 277 L976 287 L988 298 L1000 308 L1012 318 L1025 329 L1037 339 L1049 349 L1062 358 L1077 365 L1092 371 L1107 374 L1123 376 L1139 376 L1155 376 L1171 376 L1187 376 L1203 376 L1219 376 L1235 376 L1251 376 L1267 376 L1283 376 L1299 377 L1315 377 L1331 377 L1347 377 L1363 377 L1379 379 L1395 382 L1410 386 L1426 391 L1440 397 L1455 404 L1469 412 L1482 420 L1495 430 L1508 440 L1519 451 L1530 462 L1540 475 L1550 488 L1558 501 L1566 515 L1573 530 L1578 545 L1583 560 L1587 576 L1590 591 L1592 607 L1593 623 L1593 639 L1592 655 L1590 671 L1586 687 L1582 702 L1577 717 L1570 732 L1563 746 L1555 760 L1546 773 L1536 786 L1525 797 L1513 808 L1501 818 L1488 828 L1474 836 L1460 843 L1445 849 L1430 855 L1415 859 L1399 861 L1383 861 L1367 862 L1351 862 L1335 862 L1319 862 L1303 862 L1287 862 L1271 862 L1255 862 L1239 862 L1223 862 L1207 862 L1191 862 L1175 862 L1159 863 L1143 863 L1127 862 L1111 862 L1095 862 L1092 862"/>
                    <path id="seg-diagonal-sul" d="M346 560 L362 562 L377 567 L391 575 L403 585 L416 595 L428 605 L441 615 L453 625 L466 634 L478 644 L491 654 L504 664 L516 674 L529 684 L541 694 L554 704 L568 712 L582 719 L597 724 L613 727 L629 729 L645 729 L661 729 L677 729 L693 729 L709 729 L725 729 L741 729 L757 729 L773 729 L789 729 L805 729 L821 729 L837 729 L853 729 L869 730 L884 732 L900 737 L914 743 L928 752 L940 762 L952 773 L963 784 L975 795 L986 806 L998 817 L1010 828 L1022 839 L1035 848 L1050 854 L1065 859 L1081 862 L1092 862"/>
                    <path id="seg-ramal" d="M346 560 L362 560 L378 560 L394 560 L410 560 L426 560 L442 560 L458 560 L474 560 L490 560 L506 560 L522 560 L538 560 L554 560 L570 560 L586 560 L602 560 L618 560 L634 560 L650 560 L666 560 L682 560 L698 560 L714 560 L730 560 L746 560 L762 560 L778 560 L794 560 L810 560 L826 561 L842 562 L858 564 L873 569 L888 575 L903 581 L918 586 L933 592 L948 598 L963 604 L978 609 L992 615 L1007 621 L1022 626 L1037 632 L1052 638 L1067 644 L1082 649 L1097 655 L1112 661 L1127 666 L1142 672 L1152 676"/>
                </g>
                <g class="via-dormente">
                    <path data-seg="principal" d="M430 72 L446 72 L462 72 L478 71 L494 71 L510 71 L526 71 L542 71 L558 71 L574 71 L590 71 L606 71 L622 70 L638 70 L654 70 L670 70 L686 70 L702 70 L718 70 L734 70 L750 70 L766 70 L782 70 L798 70 L814 70 L830 71 L846 71 L862 71 L878 71 L894 71 L910 71 L926 71 L942 71 L958 71 L974 71 L990 71 L1006 71 L1022 71 L1038 72 L1054 72 L1070 72 L1086 72 L1102 72 L1118 72 L1134 72 L1150 73 L1166 76 L1181 79 L1197 84 L1212 90 L1226 97 L1240 104 L1253 113 L1266 123 L1278 133 L1290 145 L1300 156 L1310 169 L1319 182 L1327 196 L1334 210 L1341 225 L1346 240 L1351 255 L1354 271 L1356 287 L1358 303 L1358 319 L1358 335 L1356 351 L1355 367 L1349 381 L1345 397 L1340 412 L1333 426 L1326 441 L1318 454 L1308 467 L1298 480 L1287 491 L1275 502 L1263 512 L1250 522 L1236 530 L1222 538 L1208 544 L1193 550 L1177 554 L1162 558 L1146 560 L1130 561 L1114 562 L1098 562 L1082 562 L1066 561 L1050 560 L1034 556 L1019 551 L1005 544 L991 536 L978 527 L965 518 L952 508 L939 499 L926 490 L913 480 L900 471 L887 462 L874 453 L860 445 L845 440 L829 436 L813 434 L797 434 L781 434 L765 434 L749 434 L733 434 L717 434 L701 434 L685 434 L669 434 L653 434 L637 434 L621 434 L605 434 L589 434 L573 434 L557 434 L541 434 L525 434 L509 434 L493 434 L477 434 L461 434 L445 434 L429 434 L413 434 L397 434 L381 434 L365 434 L349 434 L333 434 L317 434 L301 434 L285 434 L269 435 L253 437 L238 440 L223 446 L208 452 L194 460 L180 468 L168 478 L156 489 L145 500 L135 513 L125 525 L117 539 L109 553 L102 568 L97 583 L92 598 L88 613 L86 629 L84 645 L84 661 L84 677 L87 693 L90 709 L94 724 L100 739 L107 753 L115 767 L125 780 L135 792 L146 804 L158 814 L171 824 L185 832 L199 840 L213 846 L228 852 L244 856 L259 860 L275 862 L291 863 L307 863 L323 864 L339 864 L355 864 L371 864 L387 864 L403 864 L419 864 L435 864 L451 864 L467 863 L483 863 L499 863 L515 863 L531 863 L547 863 L563 863 L579 863 L595 863 L611 863 L627 863 L643 862 L659 862 L675 862 L691 862 L707 862 L723 862 L739 862 L755 862 L771 862 L787 862 L803 862 L819 862 L835 862 L851 862 L867 862 L883 862 L899 862 L915 862 L931 862 L947 862 L963 862 L979 862 L995 862 L1011 862 L1027 862 L1043 862 L1059 862 L1075 862 L1091 862 L1092 862"/>
                    <path data-seg="oeste" d="M430 72 L414 72 L398 72 L382 72 L366 72 L350 72 L334 72 L318 72 L302 72 L286 72 L270 73 L254 74 L238 77 L223 81 L208 86 L193 92 L178 99 L164 107 L151 115 L138 125 L126 135 L114 146 L104 158 L94 171 L84 184 L76 197 L68 211 L62 226 L56 241 L51 256 L47 272 L45 287 L43 303 L43 319 L43 335 L44 351 L47 367 L51 383 L55 398 L61 413 L67 428 L75 442 L83 455 L92 468 L102 481 L113 493 L124 504 L136 515 L148 525 L161 534 L175 543 L189 550 L204 556 L220 559 L236 560 L252 560 L268 560 L284 560 L300 560 L316 560 L332 560 L346 560"/>
                    <path data-seg="diagonal-norte" d="M430 72 L446 73 L461 78 L476 85 L489 94 L502 103 L515 112 L528 122 L541 131 L554 140 L567 150 L580 159 L593 168 L606 177 L619 187 L632 196 L645 205 L659 214 L673 220 L688 226 L704 229 L720 231 L736 232 L752 232 L768 232 L784 232 L800 232 L816 232 L832 232 L848 232 L864 232 L880 233 L896 236 L911 241 L925 248 L939 257 L951 267 L963 277 L976 287 L988 298 L1000 308 L1012 318 L1025 329 L1037 339 L1049 349 L1062 358 L1077 365 L1092 371 L1107 374 L1123 376 L1139 376 L1155 376 L1171 376 L1187 376 L1203 376 L1219 376 L1235 376 L1251 376 L1267 376 L1283 376 L1299 377 L1315 377 L1331 377 L1347 377 L1363 377 L1379 379 L1395 382 L1410 386 L1426 391 L1440 397 L1455 404 L1469 412 L1482 420 L1495 430 L1508 440 L1519 451 L1530 462 L1540 475 L1550 488 L1558 501 L1566 515 L1573 530 L1578 545 L1583 560 L1587 576 L1590 591 L1592 607 L1593 623 L1593 639 L1592 655 L1590 671 L1586 687 L1582 702 L1577 717 L1570 732 L1563 746 L1555 760 L1546 773 L1536 786 L1525 797 L1513 808 L1501 818 L1488 828 L1474 836 L1460 843 L1445 849 L1430 855 L1415 859 L1399 861 L1383 861 L1367 862 L1351 862 L1335 862 L1319 862 L1303 862 L1287 862 L1271 862 L1255 862 L1239 862 L1223 862 L1207 862 L1191 862 L1175 862 L1159 863 L1143 863 L1127 862 L1111 862 L1095 862 L1092 862"/>
                    <path data-seg="diagonal-sul" d="M346 560 L362 562 L377 567 L391 575 L403 585 L416 595 L428 605 L441 615 L453 625 L466 634 L478 644 L491 654 L504 664 L516 674 L529 684 L541 694 L554 704 L568 712 L582 719 L597 724 L613 727 L629 729 L645 729 L661 729 L677 729 L693 729 L709 729 L725 729 L741 729 L757 729 L773 729 L789 729 L805 729 L821 729 L837 729 L853 729 L869 730 L884 732 L900 737 L914 743 L928 752 L940 762 L952 773 L963 784 L975 795 L986 806 L998 817 L1010 828 L1022 839 L1035 848 L1050 854 L1065 859 L1081 862 L1092 862"/>
                    <path data-seg="ramal" d="M346 560 L362 560 L378 560 L394 560 L410 560 L426 560 L442 560 L458 560 L474 560 L490 560 L506 560 L522 560 L538 560 L554 560 L570 560 L586 560 L602 560 L618 560 L634 560 L650 560 L666 560 L682 560 L698 560 L714 560 L730 560 L746 560 L762 560 L778 560 L794 560 L810 560 L826 561 L842 562 L858 564 L873 569 L888 575 L903 581 L918 586 L933 592 L948 598 L963 604 L978 609 L992 615 L1007 621 L1022 626 L1037 632 L1052 638 L1067 644 L1082 649 L1097 655 L1112 661 L1127 666 L1142 672 L1152 676"/>
                </g>
                <path d="M-18 0 H18" class="parachoque" transform="translate(1152 676) rotate(112)"/>
                <g id="trem"></g>
                <g class="marcador">
                    <?php foreach ([[430, 72, 'Agulha Norte'], [346, 560, 'Agulha Central'], [1092, 862, 'Agulha Sul'], [134, 514, 'Cruzamento Oeste'], [1351, 377, 'Cruzamento Leste'], [1152, 676, 'Para-choque']] as [$x, $y, $nome]) { ?>
                        <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="10"><title><?php echo $nome; ?></title></circle>
                        <text x="<?php echo $x + 16; ?>" y="<?php echo $y - 14; ?>"><?php echo mb_strtoupper($nome); ?></text>
                    <?php } ?>
                </g>
            </svg>
        </div>

    </main>

    <script src="../script/clima.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script/mapa.js"></script>

</body>

</html>