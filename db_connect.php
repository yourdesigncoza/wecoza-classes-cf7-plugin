<?php
    private function connect() {
        try {
            // Get PostgreSQL database credentials from WordPress options
            // These should be set during plugin activation or via admin settings
            $pg_host = get_option('wecoza_postgres_host', 'db-wecoza-3-do-user-17263152-0.m.db.ondigitalocean.com');
            $pg_port = get_option('wecoza_postgres_port', '25060');
            $pg_name = get_option('wecoza_postgres_dbname', 'defaultdb');
            $pg_user = get_option('wecoza_postgres_user', 'doadmin');
            $pg_pass = get_option('wecoza_postgres_password', '');
            
            // Validate required credentials
            if (empty($pg_pass)) {
                throw new Exception('PostgreSQL password is not configured');
            }
            
            // Create PDO instance for PostgreSQL
            $dsn = "pgsql:host={$pg_host};port={$pg_port};dbname={$pg_name}";
            
            $this->pdo = new PDO(
                $dsn,
                $pg_user,
                $pg_pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 30,
                )
            );
            
            $this->connected = true;
            
        } catch (PDOException $e) {
            $this->connected = false;
            error_log('WeCoza CF7 Plugin: Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }