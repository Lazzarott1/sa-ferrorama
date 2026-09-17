        <div id="header" class="hstack gap-3 px-2">
            <div class="d-flex" id="logo">
                <img src="../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo">
                <div class="nome-sistema">
                    <h2 class="mb-0 text-white">FerroMonitor</h2>
                    <p>SISTEMA FERROVIÁRIO</p>
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1b3f53;">
                <div class="container-fluid">
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <div class="d-flex">
                            <ul class="navbar-nav">
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" aria-current="page" href="tela-geral-home.php">Home</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" aria-current="page" href="#">Dashboard</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-cadastro-sensores.php">Sensores</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-trens.php">Trens</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="trilhos/tela-cadastro-trilhos.php">Trilhos</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-monitoramento.php">Monitoramento</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-relatorios.php">Relatórios</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-cadastro-user.php">Usuários</a>
                                    </li>
                                </div>

                            </ul>
                        </div>

                    </div>
                </div>
            </nav>

            <div>
                <button class="btn-sair">Sair</button>
            </div>
        </div>
    </header>

<main class="d-flex flex-column align-items-center gap-5 w-100"
    style="padding-top: 60px; min-height: 100vh; background-color: #f8f9fa;">

    <div class="card shadow-sm border-1 p-0" style="width: 600px; border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (+) CADASTRO DE USUÁRIOS
        </div>

        <div class="p-4">

            <form id="form-cadastro">

                <div class="mb-3">
                    <label for="email" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        EMAIL
                    </label>

                    <input type="email" id="email_usuario" name="email_usuario" class="form-control"
                        placeholder="exemplo@123.com" required>
                </div>

                <div class="mb-3">
                    <label for="user" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOME DE USUÁRIO
                    </label>

                    <input type="text" id="nome_usuario" name="nome_usuario" class="form-control"
                        placeholder="Usuário" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        SENHA
                    </label>

                    <input type="password" id="senha" name="senha" class="form-control"
                        placeholder="Senha" required>
                </div>

                <button type="submit"
                    class="btn btn-primary w-100 fw-semibold"
                    style="background-color: #1b3f53; border: none; border-radius: 4px; font-size: 0.85rem;">
                    CADASTRAR
                </button>

            </form>

            <div id="mensagem" class="mt-3"></div>

        </div>
    </div>

    <div class="card shadow-sm border-1 p-0" style="width: 900px; border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (∞) USUÁRIOS CADASTRADOS
        </div>

        <table class="table table-bordered table-hover mb-0 align-middle">

            <thead class="table-light">
                <tr class="text-secondary" style="font-size: 0.75rem;">
                    <th class="fw-semibold">LOGIN</th>
                    <th class="fw-semibold">E-MAIL</th>
                    <th class="fw-semibold">STATUS</th>
                    <th class="fw-semibold text-center">AÇÕES</th>
                </tr>
            </thead>

            <tbody style="font-size: 0.85rem;">

                <tr>
                    <td class="text-primary-emphasis fw-bold">
                        j.silva
                    </td>

                    <td class="text-secondary">
                        j.silva@ferromonitor.com
                    </td>

                    <td>
                        <span
                            class="badge border border-success-subtle bg-success-subtle text-success-emphasis rounded-1">
                            ATIVO
                        </span>
                    </td>

                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary">
                            ✎
                        </button>

                        <button class="btn btn-sm btn-outline-danger">
                            X
                        </button>
                    </td>
                </tr>

            </tbody>

        </table>
    </div>

</main>

    <script src="../script/validacao_cadastro_user.js"></script>

</body>


</html>