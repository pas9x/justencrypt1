CREATE TABLE cert(
  idCert INTEGER PRIMARY KEY,
  idSsh INTEGER,
  domain VARCHAR(255),
  documentRoot VARCHAR(255),
  addTime INTEGER UNSIGNED,
  issued DATE,
  expire DATE,
  privateKey BLOB,
  certDomain BLOB,
  certIssuer BLOB,
  csr BLOB,
  certDomainHash INTEGER
);
