create database iol_sso;
use iol_sso;

create table user_scope
(
    user_id char(36)                  not null
        primary key,
    scope   bigint unsigned default 0 not null
);

create table user
(
    id        char(36)     not null
        primary key,
    username  varchar(255) not null,
    password  varchar(255) not null,
    activated datetime(6)  null,
    blocked   datetime(6)  null
);

INSERT INTO iol_sso.user (id, username, password, activated, blocked)
VALUES ('78eb93bf-079c-4dc4-868c-f3d0271950c2', 'stui', '$2y$10$MG53lQAjNLrwSXMgMipYsOD9fm3wdYZSlnbgyVqncU.t88jidGIh2',
        '2021-09-23 19:48:05.000000', null);
create table session
(
    id         char(36)    not null
        primary key,
    user_id    char(36)    not null,
    created    datetime(6) not null,
    expiration datetime(6) not null,
    app_id     char(36)    null
);

create table login_request
(
    id           char(36)      not null
        primary key,
    app_id       char(36)      not null,
    redirect_url varchar(255)  not null,
    scope        int default 0 null
);

INSERT INTO iol_sso.login_request (id, app_id, redirect_url, scope)
VALUES ('1262f164-35d3-4e7d-aa92-8e200d7f5893', 'e9fca7d0-b02d-40bd-bad8-3fb3c76b9096', 'https://sso.isleoflan.ch/test',
        7);
INSERT INTO iol_sso.login_request (id, app_id, redirect_url, scope)
VALUES ('b4c5e127-b046-49be-819a-98ea140b7703', 'e9fca7d0-b02d-40bd-bad8-3fb3c76b9096', 'https://sso.isleoflan.ch/test',
        7);
create table known_devices
(
    user_id   char(36) not null,
    device_id char(64) not null,
    primary key (user_id, device_id)
);

create table intermediate_token
(
    user_id    char(36)     not null,
    app_id     char(36)     not null,
    token      varchar(255) null,
    expiration datetime(6)  not null,
    primary key (user_id, app_id)
);

INSERT INTO iol_sso.intermediate_token (global_session_id, app_id, token, expiration)
VALUES ('78eb93bf-079c-4dc4-868c-f3d0271950c2', 'e9fca7d0-b02d-40bd-bad8-3fb3c76b9096',
        'wcF1I2BCvcJT0FopX_HWNzsKKGb370T7dixiCiwoJ-hjXA0s7m3RxwRe1_z_B9wt10P-nUAEz6ziFz5DVJggIj-FiJWSg7EOZNqlxYGjZlV3WIlEcX0AH5z3YEDlkMoKoUHporbADaRmOy8cpTSYP8gGpgDajqKs67vAR9ChmDw=*MTMyZA',
        '2021-10-31 00:35:43.000000');
create table global_session
(
    id         char(36)    not null
        primary key,
    user_id    char(36)    null,
    created    datetime(6) null,
    expiration datetime(6) null
);

INSERT INTO iol_sso.global_session (id, user_id, created, expiration)
VALUES ('309d4877-7a5d-4d70-abfd-596e11763018', '78eb93bf-079c-4dc4-868c-f3d0271950c2', '2021-10-30 22:01:47.387147',
        '2021-10-31 00:30:36.000000');
INSERT INTO iol_sso.global_session (id, user_id, created, expiration)
VALUES ('40db8e8d-c47b-46e6-8353-365df6aad3a4', '78eb93bf-079c-4dc4-868c-f3d0271950c2', '2021-10-31 00:30:56.280693',
        '2021-11-29 23:30:56.280693');
INSERT INTO iol_sso.global_session (id, user_id, created, expiration)
VALUES ('5502b293-c222-4b44-999e-5fd9900c8845', '78eb93bf-079c-4dc4-868c-f3d0271950c2', '2021-10-30 14:43:52.873886',
        '2021-10-31 00:30:36.000000');
INSERT INTO iol_sso.global_session (id, user_id, created, expiration)
VALUES ('e76db38b-3e5a-45d1-af50-72dca15cccc8', '78eb93bf-079c-4dc4-868c-f3d0271950c2', '2021-10-30 22:26:36.797831',
        '2021-10-31 00:30:36.000000');
create table app
(
    id          char(36)     null,
    title       varchar(255) not null,
    description text         null,
    base_url    varchar(255) not null
);

INSERT INTO iol_sso.app (id, title, description, base_url)
VALUES ('e9fca7d0-b02d-40bd-bad8-3fb3c76b9096', 'Test App', '123', 'http://sso.isleoflan.ch');
