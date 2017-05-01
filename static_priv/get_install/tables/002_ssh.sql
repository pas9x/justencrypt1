CREATE TABLE ssh(
  idSsh INTEGER PRIMARY KEY,
  shared BOOL,
  sharedName VARCHAR(255),
  host VARCHAR(255),
  port SMALLINT UNSIGNED,
  login VARCHAR(255),
  authType TINYINT UNSIGNED,
  authValue TEXT
);