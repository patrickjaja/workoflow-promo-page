# Bash commands
- docker exec workoflow-promo-frankenphp-1 bin/console: Symfony Console

# Code style
- Use Domain Driven Development, SOLID principles, Clean Code
- Use layered architecture
- Write modern PHP 8.4

# Workflow
- Integrate features by keeping performance and scalability in mind

# Testing
- ALWAYS manually test implementations using Puppeteer MCP
- Use the test authentication header for protected pages:
  - Header: `X-Test-Auth-Email`
  - Available test users:
    - `puppeteer.test1@example.com` (Admin user with Teams ID: TEAMS_ID_001)
    - `puppeteer.test2@example.com` (Regular user with Teams ID: TEAMS_ID_002)
- Example: When testing protected pages, navigate with the header to authenticate as a test user
- All feature implementations MUST be manually tested before completion
