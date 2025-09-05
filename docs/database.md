# Database Schema

## Tables

### activity_logs
- **id** (bigint, primary key)
- **user_id** (bigint, foreign key → users.id)
- **event_type** (varchar, indexed)
- **target_model** (varchar)
- **target_id** (bigint)
- **description** (text)
- **old_values** (longtext, JSON)
- **new_values** (longtext, JSON)
- **user_agent** (text)
- **created_at** (timestamp, indexed)
- **updated_at** (timestamp)

### customers
- **id** (bigint, primary key)
- **full_name** (varchar)
- **email** (varchar, unique, indexed)
- **email_verified_at** (timestamp)
- **password** (varchar)
- **subway_card** (varchar, unique, indexed)
- **birth_date** (date)
- **gender** (varchar)
- **client_type** (varchar, indexed)
- **phone** (varchar)
- **address** (text)
- **location** (varchar)
- **nit** (varchar)
- **fcm_token** (varchar)
- **last_login_at** (timestamp)
- **last_activity_at** (timestamp, indexed)
- **last_purchase_at** (timestamp, indexed)
- **puntos** (integer, default 0)
- **puntos_updated_at** (timestamp)
- **timezone** (varchar)
- **remember_token** (varchar)
- **created_at** (timestamp, indexed)
- **updated_at** (timestamp)
- **deleted_at** (timestamp)

### permissions
- **id** (bigint, primary key)
- **name** (varchar, unique)
- **display_name** (varchar)
- **description** (text)
- **group** (varchar)
- **created_at** (timestamp)
- **updated_at** (timestamp)

### roles
- **id** (bigint, primary key)
- **name** (varchar, unique)
- **description** (text)
- **is_system** (boolean)
- **created_at** (timestamp)
- **updated_at** (timestamp)

### users
- **id** (bigint, primary key)
- **name** (varchar)
- **email** (varchar, unique)
- **email_verified_at** (timestamp)
- **password** (varchar)
- **last_login_at** (timestamp)
- **last_activity_at** (timestamp)
- **timezone** (varchar)
- **remember_token** (varchar)
- **created_at** (timestamp)
- **updated_at** (timestamp)
- **deleted_at** (timestamp)

### user_activities
- **id** (bigint, primary key)
- **user_id** (bigint, foreign key → users.id)
- **activity_type** (varchar, indexed)
- **description** (varchar)
- **user_agent** (varchar)
- **url** (varchar)
- **method** (varchar)
- **metadata** (longtext, JSON)
- **created_at** (timestamp)
- **updated_at** (timestamp)

## Pivot Tables

### permission_role
- **id** (bigint, primary key)
- **role_id** (bigint, foreign key → roles.id)
- **permission_id** (bigint, foreign key → permissions.id)
- **created_at** (timestamp)
- **updated_at** (timestamp)

### role_user
- **id** (bigint, primary key)
- **user_id** (bigint, foreign key → users.id)
- **role_id** (bigint, foreign key → roles.id)
- **created_at** (timestamp)
- **updated_at** (timestamp)

## System Tables

### cache
- **key** (varchar, primary key)
- **value** (mediumtext)
- **expiration** (int)

### cache_locks
- **key** (varchar, primary key)
- **owner** (varchar)
- **expiration** (int)

### failed_jobs
- **id** (bigint, primary key)
- **uuid** (varchar, unique)
- **connection** (text)
- **queue** (text)
- **payload** (longtext)
- **exception** (longtext)
- **failed_at** (timestamp)

### jobs
- **id** (bigint, primary key)
- **queue** (varchar, indexed)
- **payload** (longtext)
- **attempts** (tinyint)
- **reserved_at** (int)
- **available_at** (int)
- **created_at** (int)

### migrations
- **id** (int, primary key)
- **migration** (varchar)
- **batch** (int)

### password_reset_tokens
- **email** (varchar, primary key)
- **token** (varchar)
- **created_at** (timestamp)

### sessions
- **id** (varchar, primary key)
- **user_id** (bigint, indexed)
- **user_agent** (text)
- **payload** (text)
- **last_activity** (int, indexed)
- **ip_address** (varchar)