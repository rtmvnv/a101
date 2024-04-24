

## Установка системы

Создать файл `storage/app/mailru_public_key`
```
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AM
...
...
...
XtBXpxv2NyO2Qdh0kjhB+qa3mmln
XwIDAQAB
-----END PUBLIC KEY-----
```


Установить Libreoffice
```
brew install --cask libreoffice
```

Прописать путь к libreoffice в .env
```
LIBREOFFICE_PATH=/usr/local/bin/
```

## Использование системы

Для отправки квитанции в командной строке
```
php artisan accrual example@example.com --fake
```

`--fake` - заполнить недостающие данные случайными.

## Troubleshooting

### LibreOffice timeout
При запуске из командной строки возвращается timeout вызова LibreOffice.

В stderr может быть вывод.
```
ERR > [Java framework] Error in function createSettingsDocument (elements.cxx).
javaldx failed!
ERR > Warning: failed to read path from javaldx
```

Проблема в том, что нет прав для записи результирующего файла. При запуске 
через вебсервер и из командной строки могут быть разные пользователи. Файл может уже существовать от другого пользователя.

Решение:
Поставить через ACL расширенные пермишены, чтобы в директорию storage могли писать вебсервер и superuser и перезаписывать файлы друг друга.

### MethodNotAllowedHttpException
При POST запросе на /api/a101/accruals возвращается ошибка
```Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException: The GET method is not supported for this route. Supported methods: POST. in file /home/superuser/projects/a101/vendor/laravel/framework/src/Illuminate/Routing/AbstractRouteCollection.php on line 117```
Происходит на сервере. Локально все работает.

Решение:
Делать на сервер запросы по HTTPS.