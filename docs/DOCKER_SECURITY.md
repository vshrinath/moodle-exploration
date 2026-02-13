# Docker Security Configuration

This document explains the security configuration for the Moodle Docker environment.

## Environment Variables

All sensitive configuration values (passwords, API keys) are stored in environment variables, not in the `docker-compose.yml` file.

### Setup

1. **Generate secure environment file:**
   ```bash
   ./scripts/generate-env.sh
   ```
   This creates a `.env` file with randomly generated secure passwords.

2. **Or manually create `.env` from template:**
   ```bash
   cp .env.example .env
   # Edit .env and replace placeholder values
   ```

3. **Generate secure passwords:**
   ```bash
   openssl rand -base64 32
   ```

### Environment Variables Reference

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `MARIADB_USER` | Database user | `bn_moodle` | Yes |
| `MARIADB_DATABASE` | Database name | `bitnami_moodle` | Yes |
| `MARIADB_PASSWORD` | Database user password | - | Yes |
| `MARIADB_ROOT_PASSWORD` | Database root password | - | Yes |
| `BITNAMI_DEBUG` | Enable debug mode | `false` | No |

## Security Best Practices

### Development Environment

1. **Use generated passwords:**
   - Run `./scripts/generate-env.sh` to create secure random passwords
   - Never use default or simple passwords

2. **Disable debug mode:**
   - Set `BITNAMI_DEBUG=false` in `.env`
   - Debug mode should only be enabled when actively troubleshooting

3. **Protect .env file:**
   - File permissions: `chmod 600 .env` (owner read/write only)
   - Never commit `.env` to version control
   - Verify `.env` is in `.gitignore`

### Production Environment

1. **Use secrets management:**
   - Docker Swarm: Use Docker secrets
   - Kubernetes: Use Kubernetes secrets
   - Cloud: Use cloud provider's secrets manager (AWS Secrets Manager, Azure Key Vault, etc.)

2. **Rotate passwords regularly:**
   - Change database passwords every 90 days
   - Update `.env` and restart containers

3. **Disable debug mode:**
   - Always set `BITNAMI_DEBUG=false` in production
   - Debug output can leak sensitive information

4. **Use strong passwords:**
   - Minimum 32 characters
   - Mix of uppercase, lowercase, numbers, and symbols
   - Use password manager to store securely

5. **Restrict network access:**
   - Don't expose database port (3306) to host
   - Use Docker networks for inter-container communication
   - Only expose Moodle ports (8080, 8443)

## Checking Your Configuration

### Verify .env is not in git:
```bash
git check-ignore .env
# Should output: .env
```

### Verify .env permissions:
```bash
ls -la .env
# Should show: -rw------- (600)
```

### Verify environment variables are loaded:
```bash
docker-compose config
# Should show actual values, not ${VARIABLE} placeholders
```

## Troubleshooting

### "required variable is not set" error

If you see this error when running `docker-compose up`:
```
ERROR: The MARIADB_PASSWORD variable is not set.
```

**Solution:**
1. Ensure `.env` file exists in project root
2. Verify `.env` contains all required variables
3. Check variable names match exactly (case-sensitive)

### Containers fail to start after changing passwords

**Solution:**
1. Stop and remove containers: `docker-compose down`
2. Remove volumes: `docker volume rm moodle-exploration_mariadb_data`
3. Start fresh: `docker-compose up -d`

**Note:** This will delete all data. Backup first if needed.

## Migration from Hardcoded Passwords

If you're migrating from the old configuration with hardcoded passwords:

1. **Create .env file:**
   ```bash
   ./scripts/generate-env.sh
   ```

2. **Stop containers:**
   ```bash
   docker-compose down
   ```

3. **Update database passwords:**
   ```bash
   docker-compose up -d mariadb
   docker exec -it moodle-exploration-mariadb-1 mysql -u root -p
   ```
   
   In MySQL:
   ```sql
   ALTER USER 'bn_moodle'@'%' IDENTIFIED BY 'new_password_from_env';
   FLUSH PRIVILEGES;
   ```

4. **Restart all containers:**
   ```bash
   docker-compose up -d
   ```

## Additional Resources

- [Docker Compose Environment Variables](https://docs.docker.com/compose/environment-variables/)
- [Docker Secrets](https://docs.docker.com/engine/swarm/secrets/)
- [Bitnami Moodle Documentation](https://github.com/bitnami/containers/tree/main/bitnami/moodle)
