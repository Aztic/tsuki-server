CREATE TABLE IF NOT EXISTS users(
    id bigserial primary key,
    username text unique not null,
    email text unique not null,
    password text not null,
    created_at timestamp default now()
);
