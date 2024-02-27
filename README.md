

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