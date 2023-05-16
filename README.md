
# Wilda
Репроксирование с модификацией страниц.

## Зависимости
`nginx` `redis` `php81-fpm` `php81-redis` `php81-cli` `php81-ctype` `php81-dom` `php81-bcmath`

## Конфигурация
Вся конфигурация для сайтов описывается в файле `.config.json`
Файл `global.json` не изменяется.

### Файлы конфигурации
`global.json` - основная конфигурация
`.config.json` - пользовательская конфигурация.

> Глобальные настройки наследуются с пользовательскими.
> Если в пользовательских настройках не будет задан какой-либо параметр, он будет наследован из глобальной конфигурации.

```json
	"enabled": false
```
Параметр используется для вкл/выкл различного функционала.

## hosts (global)
```json
	"hosts": []
```
Описание конфигурации для каждого сайта отдельно
> Если некоторые параметры не заданы в настройках сайта, они наследуются из глобальной конфигурации

## forceSSL (global)
```json
	"forceSSL": true
```
Принудительная установка HTTPS урлов.

## styles (global, hosts)
```json
	"styles": "relative"
```

`relative` - относительный путь css

`absolute` - абсолютный путь css

## scripts (global, hosts)
```json
	"scripts": "relative"
```

`relative` - относительный путь js

`absolute` - абсолютный путь js

## images (global, hosts)
```json
	"images": "relative"
```

`relative` - относительный путь img

`absolute` - абсолютный путь img

## fonts (global, hosts)
```json
	"fonts": "relative"
```

`relative` - относительный путь font

`absolute` - абсолютный путь font

## icons (global, hosts)
```json
	"icons": "relative"
```

`relative` - относительный путь icons

`absolute` - абсолютный путь icons

## salt (global)
```json
	"salt": "wzvvHBWVYQLnX5jFqDmGWPf6om1Hsx8g"
```
Используется для `relative` шифрует ссылки

## lang (global)
```json
	"lang": "ru"
```
Локализация для внутренних и внешних сообщений приложения

## type (hosts)
```json
	"type": "Plain"
```
Тип конструктора

**Plain** - пустой модуль

## editor (global)
```json
 "editor": {
  "enabled": true
 }
```

## site (hosts)
```json
	"site": "https://site.tld",
	"site": [
		"https://site.tld",
		"https://anothersite.tld"
	]
```
Имя сайта с протоколом (внешнее)

## project (hosts)
```json
	"project": "https://project.tld"
```

Имя сайта с протоколом (внутреннее)

## cache (global, hosts)
```json
	"cache": {
		"enabled": false,
		"expire": 1,
		"stats": true
	}
```
Все элементы сайта можно закешировать, чтобы ускорить процесс рендера страницы. 
Кеширование элементов: html, css, js, font, ico.

`expire` - Время жизни в минутах.

`stats` - Отображать в разметке комментарий с временем загрузки страницы.

### Методы очистки кеша
`https://sitename.tld/?cleaner` - получение браузерного идентификатора для очистки кеша. 
После получения идентификатора браузером, происходит перенаправление на главную страницу сайта.
Сверху слева появится иконка перезагрузки &#10227; при нажатии, произойдёт очистка
кеша и перезагрузка страницы.

## compress (global, hosts)
```json
 "compress": {
 "enabled": true
 }
 ```
 Включение HTML компрессии

## privoxy (global, hosts)
```json
	"privoxy": {
		"enabled": false,
		"host": "127.0.0.1",
		"port": 8118

	}
```
Запросы к внутреннему сайту через прокси

`host` - имя хоста

`port` - порт хоста

## metrics (global, hosts)
```json
	"metrics": {
		"enabled": true,
		"ga": "GTM-123123",
		"ya": "93467514"
	}
```
Счётчики Google Analytics & Yandex Metrika

`ga` - идентификатор Google Analytics

`ya` - идентификатор Yandex Metrika

## mail (global, hosts)
```json
	"mail": {
		"enabled": true,
		"subject": "New submission",
		"from":"info@mail.tld",
		"success": "Сообщение успешно отправлено!",
		"error": "Ошибка!",
		"to": [
			"test@mail.tld",
			"another@mail.tld"
		]
	}
```
Отправка почтовых сообщений

`subject` - Заголовок сообщения

`from` - Ящик отправителя

`to` - Ящики для приёма

`success` - Сообщение об успешной отправке

`error` - Сообщение об ошибке


## favicon (global, hosts)
```json
	"favicon": {
		"enabled": true
	}
```

Замена favicon на сайте
Необходимо добавить файл favicon в директорию `app/favicon`. Имя файла должно совпадать с именем основного домена `sitename.tld.ico`

## inject (hosts)
```json
	"inject": {
		"enabled": true,
		"header": true,
		"footer": true
	}
```
Необходимо добавить файлы в директорию `app/inject`.

`header` - Инъекция блока html с произвольным кодом в шапку разметки `</head>`
> Имя файла должно совпадать с именем основного домена sitename.tld-header.html

`footer` - Инъекция блока html с произвольным кодом в шапку разметки вниз `</body>`
> Имя файла должно совпадать с именем основного домена sitename.tld-footer.html


## Пример описания конфигурации для определённого сайта
```json
"hosts": [
		{
			"compress": {
				"enabled": true
			},
			"type": "Plain",
			"site": [
				"https://sitename.tld",
				"https://anothersitename.tld"
			],
			"project": "https://project123-constructor.tld",
			"privoxy": {
				"enabled": false
			},
			"mail": {
				"enabled": true,
				"subject": "Заявка с сайта",
				"from":"info@mail.tld",
				"to": [
					"test@mail.tld",
					"another@mail.tld"
				]
			},
			"favicon": {
				"enabled": true
			},
			"metrics": {
				"enabled": true,
				"ga": "GTM-123123",
				"ya": "123456789"
			},
			"inject": {
				"enabled": true,
				"header": true
			}
		}
]
```