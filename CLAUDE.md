# Bash commands
- docker exec workoflow-promo-frankenphp-1 bin/console: Symfony Console
- google-chrome-stable \
  --headless \
  --remote-debugging-port=9222 \
  --user-data-dir=/tmp/ \
  --disable-gpu \
  --no-first-run \
  --no-sandbox \
  --disable-dev-shm-usage \
  --disable-extensions \
  --disable-background-networking \
  --suppress-message-center-logging \
  --disable-infobars \
  --disable-popup-blocking \
  --disable-translate: Start google chrome for Puppeteer MCP
# Code style
- Use Domain Driven Development, SOLID principles, Clean Code
- Use layered architecture
- Write modern PHP 8.4

# Workflow
- Integrate features by keeping performance and scalability in mind

# Testing
- ALWAYS manually test webpage features using Puppeteer MCP (NOT scripts or deployment tools)
- Only test features that are visible or interactive on the webpage
- Use the test authentication GET parameter for protected pages:
  - Parameter: `X-Test-Auth-Email`
  - Available test users:
    - `puppeteer.test1@example.com` (Admin user with Teams ID: TEAMS_ID_001)
    - `puppeteer.test2@example.com` (Regular user with Teams ID: TEAMS_ID_002)
- Example: When testing protected pages, append `?X-Test-Auth-Email=puppeteer.test1@example.com` to the URL to authenticate as a test user
- All webpage feature implementations MUST be manually tested before completion

# Git commits
- Always use current git user as the author for git commits
- NEVER add Co-Authored-By or any Claude/Claude Code references in commit messages
- Keep commit messages clean and professional without AI-generated signatures
