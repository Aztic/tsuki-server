CREATE TABLE IF NOT EXISTS projects(
    id bigserial primary key,
    user_id bigint references users(id),
    project_identifier text unique not null,
    is_private bool default false,
    data text,
    created_at timestamp default now(),
    updated_at timestamp default now()
);
