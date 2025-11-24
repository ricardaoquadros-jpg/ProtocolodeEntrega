CREATE TABLE protocolos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_recebedor VARCHAR(255) NOT NULL,
    cpf_matricula VARCHAR(50),
    telefone VARCHAR(20),
    assinatura_base64 LONGTEXT, -- Salva a imagem da assinatura
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE protocolo_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocolo_id INT NOT NULL,
    patrimonio_codigo VARCHAR(100),
    tipo_equipamento VARCHAR(100),
    FOREIGN KEY (protocolo_id) REFERENCES protocolos(id)
);