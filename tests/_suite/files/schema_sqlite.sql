CREATE TABLE user (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    password TEXT NULL DEFAULT NULL,
    active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE profile (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    fullname TEXT NOT NULL,
    user_id INTEGER NOT NULL
);
CREATE TABLE friends (
    user_id INTEGER NOT NULL,
    friend_id INTEGER NOT NULL,
    level INTEGER NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, friend_id)
);
CREATE TABLE nokey (
    name TEXT NOT NULL,
    info TEXT NULL
);
CREATE TABLE ta (
    id INTEGER NOT NULL,
    vcol TEXT NOT NULL,
    PRIMARY KEY (id)
);
CREATE TABLE tb (
    id INTEGER NOT NULL,
    vcol TEXT NOT NULL,
    PRIMARY KEY (id)
);
CREATE TABLE tc (
    ta_id INTEGER NOT NULL,
    tb_id INTEGER NOT NULL,
    PRIMARY KEY (ta_id, tb_id)
);
CREATE TABLE session (
    session_id TEXT NOT NULL,
    data TEXT NOT NULL,
    ip TEXT NOT NULL,
    agent TEXT NOT NULL,
    stamp INTEGER NOT NULL
);
CREATE TABLE types_check (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(32) NULL,
    last_name VARCHAR(32) NULL,
    last_check DATE NOT NULL
);
CREATE TABLE phone (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    phonename TEXT NOT NULL,
    user_id INTEGER NOT NULL
);
CREATE TABLE ta2 (
    id INTEGER NOT NULL,
    vcol TEXT NOT NULL,
    PRIMARY KEY (id)
);
CREATE TABLE tb2 (
    id INTEGER NOT NULL,
    vcol TEXT NOT NULL,
    id2 INTEGER NOT NULL,
    PRIMARY KEY (id, id2)
);
CREATE TABLE tc2 (
    ta2_id INTEGER NOT NULL,
    tb2_id INTEGER NOT NULL,
    tb2_id2 INTEGER NOT NULL,
    PRIMARY KEY (ta2_id, tb2_id, tb2_id2)
);