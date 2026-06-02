Run the full quality gate for this project in this order:

1. Run PHPUnit tests inside Docker:
   `docker compose exec app vendor/bin/phpunit --colors=always`

2. Run PHP CS Fixer in dry-run mode to check code style:
   `docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff`

3. Run PHPStan static analysis:
   `docker compose exec app vendor/bin/phpstan analyse src --level=8`

Report the result of each step clearly. If any step fails, stop and show the errors. Only say "All checks passed" if all 3 steps succeed.
