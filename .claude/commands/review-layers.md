Review the src/ directory for layer boundary violations in this DDD project.

Check the following rules and report any violations:

1. **Domain layer** (`src/Domain/`) must NOT import from:
   - `Slim\` or any HTTP framework
   - `PDO` or any database class
   - `src/Application\`
   - `src/Infrastructure\`

2. **Application layer** (`src/Application/`) must NOT import from:
   - `Slim\` or any HTTP framework
   - `PDO` or any database class
   - `src/Infrastructure\`

3. **Infrastructure layer** (`src/Infrastructure/`) may import from anywhere — this is expected.

For each violation found, show:
- The file path
- The offending `use` statement
- Which rule it breaks

If no violations are found, confirm that all layer boundaries are clean.
