.PHONY: up down migrate seed demo backup logs

up:
	docker compose up -d --build

down:
	docker compose down

migrate:
	docker compose exec app php cli.php migrate

seed:
	docker compose exec app php cli.php seed

demo:
	docker compose exec app php cli.php seed:demo

backup:
	docker compose exec app php cli.php backup

logs:
	docker compose logs -f app
