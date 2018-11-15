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