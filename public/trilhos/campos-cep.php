<div class="col-12 border-top pt-3">
    <span class="form-label">LOCALIZAÇÃO DA ESTAÇÃO (OPCIONAL)</span>
</div>

<div class="col-md-3">
    <label for="cep" class="form-label">CEP</label>
    <input type="text" name="cep" id="cep" class="form-control" inputmode="numeric" maxlength="9" placeholder="Ex: 89201-100" value="<?php echo preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', htmlspecialchars($endereco['cep'])); ?>" onblur="buscarCep(this)">
    <div id="avisoCep" class="form-text">Digite o CEP para preencher o endereço.</div>
</div>

<?php foreach (['logradouro' => ['LOGRADOURO', 4], 'bairro' => ['BAIRRO', 2], 'cidade' => ['CIDADE', 2], 'uf' => ['UF', 1]] as $campo => [$rotulo, $tamanho]) { ?>
    <div class="col-md-<?php echo $tamanho; ?>">
        <label for="<?php echo $campo; ?>" class="form-label"><?php echo $rotulo; ?></label>
        <input type="text" name="<?php echo $campo; ?>" id="<?php echo $campo; ?>" class="form-control" maxlength="<?php echo $campo == 'uf' ? 2 : 100; ?>" value="<?php echo htmlspecialchars($endereco[$campo]); ?>">
    </div>
<?php } ?>

<script src="../../script/cep.js"></script>