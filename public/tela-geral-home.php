<?php
// Só entra quem fez login
include __DIR__ . '/../infra/validacoes-usuarios/verifica-login.php';

$nome_usuario = $_SESSION['usuario'];

date_default_timezone_set('America/Sao_Paulo');

$meses = [
    1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
];

$data_hoje = 'Dia ' . date('d') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .home-topo { border-left: 4px solid #daa301; padding-left: 16px; }
        .home-topo h1 { color: #1b3f53; font-weight: 700; }
        .home-topo p { color: #6c757d; margin: 0; }
        .mapa-ferrovia { width: 100%; max-width: 1400px; height: auto; display: block; margin: 0 auto; }
        .via { fill: none; stroke: #1b3f53; stroke-width: 12; stroke-linecap: round; stroke-linejoin: round; filter: drop-shadow(0 6px 6px rgba(27, 63, 83, .18)); }
        .via-dormente { fill: none; stroke: #ffffff; stroke-width: 3; stroke-dasharray: 2 12; opacity: .55; }
        .via-fundo { fill: none; stroke: #ffffff; stroke-width: 30; }
        .ponte path { fill: none; stroke: #daa301; stroke-width: 5; stroke-linejoin: round; stroke-linecap: round; }
        .parachoque { stroke: #daa301; stroke-width: 8; stroke-linecap: round; }
        .estrada path { stroke: #e9ecef; stroke-width: 56; }
        .estrada .estrada-faixa { stroke: #adb5bd; stroke-width: 3; stroke-dasharray: 14 12; }
        .rastro { fill: none; stroke: #daa301; stroke-width: 8; stroke-linecap: round; opacity: .85; }
        .marcador circle { fill: #ffffff; stroke: #daa301; stroke-width: 3; }
        .marcador text { fill: #1b3f53; font-size: 22px; font-weight: 700; letter-spacing: 2px; opacity: .75; }
    </style>
</head>

<body>

    <?php include __DIR__ . '/navbar.php'; ?>

    <main>

        <div class="container-fluid mt-4 p-0 px-4">
            <div class="home-topo mb-3">
                <h1 class="mb-1">Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
                <p><?php echo $data_hoje; ?></p>
            </div>

            <?php if (isset($_GET['acesso'])) { ?>
                <div class="alert alert-warning rounded-1" style="border-left: 4px solid #daa301;">
                    Você não tem permissão para acessar essa tela.
                </div>
            <?php } ?>
        </div>

        <div class="container-fluid px-4 pb-4">
            <svg viewBox="40 20 1820 880" class="mapa-ferrovia" role="img" aria-label="Traçado da ferrovia da turma">
                <defs>
                    <pattern id="pontos" width="36" height="36" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="2" fill="#1b3f53" opacity=".12"/>
                    </pattern>
                    <radialGradient id="fade">
                        <stop offset="55%" stop-color="#fff"/>
                        <stop offset="100%" stop-color="#000"/>
                    </radialGradient>
                    <mask id="mascara"><rect x="40" y="20" width="1820" height="880" fill="url(#fade)"/></mask>
                </defs>
                <rect x="40" y="20" width="1820" height="880" fill="url(#pontos)" mask="url(#mascara)"/>
                <g class="estrada"><path d="M1486 700 V900"/><path d="M1486 700 V900" class="estrada-faixa"/></g>
                <path d="M550 82 C510 76 428 74 378 78 C328 82 285 90 251 108 C217 126 192 151 174 183 C156 215 151 268 146 300 C141 332 138 352 146 375 C154 398 168 420 193 437 C218 454 258 465 295 477 C332 489 376 497 418 509 C460 521 511 534 550 548 C589 562 622 577 653 590 C684 603 703 618 734 628 C765 638 798 645 838 651 C878 657 930 662 973 665 C1016 668 1063 665 1094 670 C1125 675 1137 681 1160 693 C1183 705 1208 725 1233 741 C1258 757 1290 782 1310 791 C1330 800 1321 796 1355 798 C1389 800 1471 804 1516 805 C1561 806 1594 810 1627 807 C1660 804 1689 799 1713 789 C1737 779 1753 766 1769 746 C1785 726 1802 696 1810 669 C1818 642 1818 611 1815 583 C1812 555 1803 524 1790 499 C1777 474 1758 452 1735 432 C1712 412 1684 394 1652 381 C1620 368 1583 358 1545 351 C1507 344 1464 342 1423 339 C1382 336 1333 339 1300 336 C1267 333 1261 336 1223 323 C1185 310 1121 279 1071 258 C1021 237 975 212 921 195 C867 178 799 172 749 159 C699 146 653 130 620 117 C587 104 590 88 550 82 Z" class="via"/>
                <path d="M550 82 C510 76 428 74 378 78 C328 82 285 90 251 108 C217 126 192 151 174 183 C156 215 151 268 146 300 C141 332 138 352 146 375 C154 398 168 420 193 437 C218 454 258 465 295 477 C332 489 376 497 418 509 C460 521 511 534 550 548 C589 562 622 577 653 590 C684 603 703 618 734 628 C765 638 798 645 838 651 C878 657 930 662 973 665 C1016 668 1063 665 1094 670 C1125 675 1137 681 1160 693 C1183 705 1208 725 1233 741 C1258 757 1290 782 1310 791 C1330 800 1321 796 1355 798 C1389 800 1471 804 1516 805 C1561 806 1594 810 1627 807 C1660 804 1689 799 1713 789 C1737 779 1753 766 1769 746 C1785 726 1802 696 1810 669 C1818 642 1818 611 1815 583 C1812 555 1803 524 1790 499 C1777 474 1758 452 1735 432 C1712 412 1684 394 1652 381 C1620 368 1583 358 1545 351 C1507 344 1464 342 1423 339 C1382 336 1333 339 1300 336 C1267 333 1261 336 1223 323 C1185 310 1121 279 1071 258 C1021 237 975 212 921 195 C867 178 799 172 749 159 C699 146 653 130 620 117 C587 104 590 88 550 82 Z" class="via-dormente"/>
                <path d="M457 513 C484 513 561 511 620 511 C679 511 757 511 809 513 C861 515 892 515 934 523 C976 531 1029 550 1059 561 C1089 572 1106 586 1116 591" class="via"/>
                <path d="M457 513 C484 513 561 511 620 511 C679 511 757 511 809 513 C861 515 892 515 934 523 C976 531 1029 550 1059 561 C1089 572 1106 586 1116 591" class="via-dormente"/>
                <path d="M-16 0 H16" class="parachoque" transform="translate(1116 591) rotate(115)"/>
                <path id="rota-trem" d="M550 82 C510 76 428 74 378 78 C328 82 285 90 251 108 C217 126 192 151 174 183 C156 215 151 268 146 300 C141 332 138 352 146 375 C154 398 168 420 193 437 C218 454 258 465 295 477 C332 489 376 497 418 509 C460 521 511 534 550 548 C589 562 622 577 653 590 C684 603 703 618 734 628 C765 638 798 645 838 651 C878 657 930 662 973 665 C1016 668 1063 665 1094 670 C1125 675 1137 681 1160 693 C1183 705 1208 725 1233 741 C1258 757 1290 782 1310 791 C1330 800 1321 796 1355 798 C1389 800 1471 804 1516 805 C1561 806 1594 810 1627 807 C1660 804 1689 799 1713 789 C1737 779 1753 766 1769 746 C1785 726 1802 696 1810 669 C1818 642 1818 611 1815 583 C1812 555 1803 524 1790 499 C1777 474 1758 452 1735 432 C1712 412 1684 394 1652 381 C1620 368 1583 358 1545 351 C1507 344 1464 342 1423 339 C1382 336 1333 339 1300 336 C1267 333 1261 336 1223 323 C1185 310 1121 279 1071 258 C1021 237 975 212 921 195 C867 178 799 172 749 159 C699 146 653 130 620 117 C587 104 590 88 550 82 Z" fill="none"/>
                <path id="rastro" d="M550 82 C510 76 428 74 378 78 C328 82 285 90 251 108 C217 126 192 151 174 183 C156 215 151 268 146 300 C141 332 138 352 146 375 C154 398 168 420 193 437 C218 454 258 465 295 477 C332 489 376 497 418 509 C460 521 511 534 550 548 C589 562 622 577 653 590 C684 603 703 618 734 628 C765 638 798 645 838 651 C878 657 930 662 973 665 C1016 668 1063 665 1094 670 C1125 675 1137 681 1160 693 C1183 705 1208 725 1233 741 C1258 757 1290 782 1310 791 C1330 800 1321 796 1355 798 C1389 800 1471 804 1516 805 C1561 806 1594 810 1627 807 C1660 804 1689 799 1713 789 C1737 779 1753 766 1769 746 C1785 726 1802 696 1810 669 C1818 642 1818 611 1815 583 C1812 555 1803 524 1790 499 C1777 474 1758 452 1735 432 C1712 412 1684 394 1652 381 C1620 368 1583 358 1545 351 C1507 344 1464 342 1423 339 C1382 336 1333 339 1300 336 C1267 333 1261 336 1223 323 C1185 310 1121 279 1071 258 C1021 237 975 212 921 195 C867 178 799 172 749 159 C699 146 653 130 620 117 C587 104 590 88 550 82 Z" class="rastro"/>
                <g id="trem"></g>
                <path d="M1310 791 C1285 792 1225 797 1160 799 C1095 801 987 803 921 804 C855 805 832 805 764 804 C696 803 589 802 514 798 C439 794 370 794 316 782 C262 770 227 748 193 725 C159 702 130 683 112 646 C94 609 80 540 88 501 C96 462 126 439 160 414 C194 389 249 367 295 352 C341 337 392 332 438 324 C484 316 521 307 568 306 C615 305 668 314 718 320 C768 326 824 330 866 339 C908 348 935 358 973 371 C1011 384 1059 401 1094 415 C1129 429 1150 444 1181 457 C1212 470 1246 482 1281 492 C1316 502 1355 516 1389 520 C1423 524 1458 521 1486 516 C1514 511 1540 504 1559 488 C1578 472 1592 442 1601 417 C1610 392 1616 368 1614 341 C1612 314 1603 283 1587 252 C1571 221 1543 180 1516 154 C1489 128 1456 109 1423 96 C1390 83 1363 82 1319 77 C1275 72 1218 68 1160 66 C1102 64 1042 67 973 67 C904 67 820 66 749 68 C678 70 583 80 550 82" class="via"/>
                <path d="M1310 791 C1285 792 1225 797 1160 799 C1095 801 987 803 921 804 C855 805 832 805 764 804 C696 803 589 802 514 798 C439 794 370 794 316 782 C262 770 227 748 193 725 C159 702 130 683 112 646 C94 609 80 540 88 501 C96 462 126 439 160 414 C194 389 249 367 295 352 C341 337 392 332 438 324 C484 316 521 307 568 306 C615 305 668 314 718 320 C768 326 824 330 866 339 C908 348 935 358 973 371 C1011 384 1059 401 1094 415 C1129 429 1150 444 1181 457 C1212 470 1246 482 1281 492 C1316 502 1355 516 1389 520 C1423 524 1458 521 1486 516 C1514 511 1540 504 1559 488 C1578 472 1592 442 1601 417 C1610 392 1616 368 1614 341 C1612 314 1603 283 1587 252 C1571 221 1543 180 1516 154 C1489 128 1456 109 1423 96 C1390 83 1363 82 1319 77 C1275 72 1218 68 1160 66 C1102 64 1042 67 973 67 C904 67 820 66 749 68 C678 70 583 80 550 82" class="via-dormente"/>
                <path d="M514 798 C481 795 370 794 316 782 C262 770 227 748 193 725 C159 702 130 683 112 646 C94 609 80 540 88 501 C96 462 126 439 160 414 C194 389 249 367 295 352 C341 337 392 332 438 324 C484 316 521 307 568 306 C615 305 668 314 718 320 C768 326 824 330 866 339 C908 348 935 358 973 371 C1011 384 1059 401 1094 415 C1129 429 1150 444 1181 457 C1212 470 1246 482 1281 492 C1316 502 1355 516 1389 520 C1423 524 1458 521 1486 516 C1514 511 1540 504 1559 488 C1578 472 1592 442 1601 417 C1610 392 1616 368 1614 341 C1612 314 1603 283 1587 252 C1571 221 1543 180 1516 154 C1489 128 1456 109 1423 96 C1390 83 1363 82 1319 77 C1275 72 1186 68 1160 66" class="via-fundo"/>
                <path d="M514 798 C481 795 370 794 316 782 C262 770 227 748 193 725 C159 702 130 683 112 646 C94 609 80 540 88 501 C96 462 126 439 160 414 C194 389 249 367 295 352 C341 337 392 332 438 324 C484 316 521 307 568 306 C615 305 668 314 718 320 C768 326 824 330 866 339 C908 348 935 358 973 371 C1011 384 1059 401 1094 415 C1129 429 1150 444 1181 457 C1212 470 1246 482 1281 492 C1316 502 1355 516 1389 520 C1423 524 1458 521 1486 516 C1514 511 1540 504 1559 488 C1578 472 1592 442 1601 417 C1610 392 1616 368 1614 341 C1612 314 1603 283 1587 252 C1571 221 1543 180 1516 154 C1489 128 1456 109 1423 96 C1390 83 1363 82 1319 77 C1275 72 1186 68 1160 66" class="via"/>
                <path d="M514 798 C481 795 370 794 316 782 C262 770 227 748 193 725 C159 702 130 683 112 646 C94 609 80 540 88 501 C96 462 126 439 160 414 C194 389 249 367 295 352 C341 337 392 332 438 324 C484 316 521 307 568 306 C615 305 668 314 718 320 C768 326 824 330 866 339 C908 348 935 358 973 371 C1011 384 1059 401 1094 415 C1129 429 1150 444 1181 457 C1212 470 1246 482 1281 492 C1316 502 1355 516 1389 520 C1423 524 1458 521 1486 516 C1514 511 1540 504 1559 488 C1578 472 1592 442 1601 417 C1610 392 1616 368 1614 341 C1612 314 1603 283 1587 252 C1571 221 1543 180 1516 154 C1489 128 1456 109 1423 96 C1390 83 1363 82 1319 77 C1275 72 1186 68 1160 66" class="via-dormente"/>
                <g class="ponte" transform="translate(660 313) rotate(10.1)"><path d="M0 -9 H304 M0 9 H304"/><path d="M0 9 L30 -9 L61 9 L91 -9 L122 9 L152 -9 L182 9 L213 -9 L243 9 L274 -9 L304 9"/></g>
                <g class="marcador"><circle cx="800" cy="285" r="6"/><text x="816" y="291">PONTE</text></g>
                <g class="marcador"><circle cx="1150" cy="625" r="6"/><text x="1166" y="631">DESVIO MORTO</text></g>
                <g class="marcador"><circle cx="1530" cy="860" r="6"/><text x="1546" y="866">PASSAGEM DE NÍVEL</text></g>
            </svg>
        </div>

    </main>


    <script>
        const rota = document.getElementById('rota-trem');
        const rastro = document.getElementById('rastro');
        const trem = document.getElementById('trem');
        const total = rota.getTotalLength();
        const pecas = [];

        for (let i = 0; i < 3; i++) {
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            const largura = i == 0 ? 50 : 40;
            g.innerHTML = '<rect x="' + (-largura / 2) + '" y="-11" width="' + largura + '" height="22" rx="11" fill="' + (i == 0 ? '#daa301' : '#f0c94a') + '" stroke="#ffffff" stroke-width="3"/>'
                + (i == 0 ? '<circle cx="14" cy="0" r="4" fill="#ffffff"/>' : '');
            trem.appendChild(g);
            pecas.push(g);
        }

        rastro.style.strokeDasharray = '260 ' + (total - 260);

        function anda(tempo) {
            const inicio = (tempo * 0.15) % total;
            pecas.forEach(function (g, i) {
                const s = (inicio - i * 46 + total) % total;
                const a = rota.getPointAtLength(s);
                const b = rota.getPointAtLength((s + 2) % total);
                const angulo = Math.atan2(b.y - a.y, b.x - a.x) * 180 / Math.PI;
                g.setAttribute('transform', 'translate(' + a.x + ' ' + a.y + ') rotate(' + angulo + ')');
            });
            rastro.style.strokeDashoffset = -(inicio - 372);
            requestAnimationFrame(anda);
        }

        requestAnimationFrame(anda);
    </script>

</body>

</html>