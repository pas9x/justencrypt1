CREATE TABLE exports(
  idExport INTEGER PRIMARY KEY,
  idCert INTEGER,
  exporterName VARCHAR(100),
  options BLOB,
  finalCommand VARCHAR(255),
  lastDate INTEGER UNSIGNED,
  lastError BLOB,
  lastCertHash INTEGER
);