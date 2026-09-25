Как добавить свои картинки для игр (без редактирования кода/JSON)
===================================================================

id игры — это то же самое, что "id" в storage/games.json, например:
  "id": "cyberpunk-2077"

1) Обложка карточки игры
   Положите файл сюда:
     public/assets/images/covers/<id>.jpg   (или .png / .jpeg / .webp)

   Пример:
     public/assets/images/covers/cyberpunk-2077.jpg

2) Скриншоты (модалка с галереей)
   Положите файлы сюда, пронумеровав в конце имени (1, 2, 3...):
     public/assets/images/screenshots/<id>-1.jpg
     public/assets/images/screenshots/<id>-2.jpg
     public/assets/images/screenshots/<id>-3.jpg

   Пример:
     public/assets/images/screenshots/cyberpunk-2077-1.jpg
     public/assets/images/screenshots/cyberpunk-2077-2.jpg

Больше ничего делать не нужно — сайт сам найдёт файл по id и покажет его
вместо сгенерированной цветной заглушки. Если файла нет — просто
останется заглушка, ничего не сломается.

Приоритет источников картинки (что сработает первым):
  1. Поле "cover" / "screenshots" в storage/games.json, если вы их
     прописали вручную (например, внешнюю ссылку) — самый высокий приоритет.
  2. Файлы из этой папки, подобранные по id игры.
  3. Автоматическая SVG-заглушка с названием и жанром игры.

Разрешённые форматы: jpg, jpeg, png, webp.
