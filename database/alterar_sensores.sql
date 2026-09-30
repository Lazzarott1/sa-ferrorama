USE sa_teste;

ALTER TABLE sensores
    ADD COLUMN id_trem INT NULL AFTER tipo_sensor,
    ADD COLUMN id_trilho INT NULL AFTER id_trem,
    ADD FOREIGN KEY (id_trem) REFERENCES trens(id_trem) ON DELETE SET NULL,
    ADD FOREIGN KEY (id_trilho) REFERENCES trilhos(id_trilho) ON DELETE SET NULL;

UPDATE sensores
    JOIN trens ON trens.nome_trem = sensores.trilho_sensor
    SET sensores.id_trem = trens.id_trem
    WHERE sensores.categoria_sensor = 'TREM';

UPDATE sensores
    JOIN trilhos ON trilhos.nome_trilho = sensores.trilho_sensor
                 OR CAST(trilhos.id_trilho AS CHAR) = sensores.trilho_sensor
    SET sensores.id_trilho = trilhos.id_trilho
    WHERE sensores.categoria_sensor = 'TRILHO';

ALTER TABLE sensores DROP COLUMN trilho_sensor;
