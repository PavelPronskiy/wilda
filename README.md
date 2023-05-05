
# Wilda
Репроксирование с модификацией страниц для конструкторов tilda, wix

## Зависимости
`nginx` `redis` `php8-fpm` `php8-cli` `php8-ctype` `php8-dom` `php8-mcrypt`

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

## hosts
```json
	"hosts": []
```
Описание конфигурации для каждого сайта отдельно
> Если некоторые параметры не заданы в настройках сайта, они наследуются из глобальной конфигурации

## styles
```json
	"styles": "relative"
```

`relative` - относительный путь css
`absolute` - абсолютный путь css

## scripts
```json
	"scripts": "relative"
```

`relative` - относительный путь js
`absolute` - абсолютный путь js

## images
```json
	"images": "relative"
```

`relative` - относительный путь img
`absolute` - абсолютный путь img

## fonts
```json
	"fonts": "relative"
```

`relative` - относительный путь font
`absolute` - абсолютный путь font

## icons
```json
	"icons": "relative"
```

`relative` - относительный путь icons
`absolute` - абсолютный путь icons

## salt
```json
	"salt": "wzvvHBWVYQLnX5jFqDmGWPf6om1Hsx8g"
```
Используется для `relative` шифрует ссылки

## lang
```json
	"lang": "ru"
```
Локализация для внутренних и внешних сообщений приложения

## type
```json
	"type": "tilda"
```
Тип конструктора

**tilda** - проект из тилды
**wix** - проект из викс

## site
```json
	"site": "https://site.tld"
```
Имя сайта с протоколом (внешнее)

## project
```json
	"project": "https://project.tld"
```

Имя сайта с протоколом (внутреннее)

## cache
```json
	"cache": {
		"enabled": false,
		"expire": 1
	}
```

Все элементы сайта можно закешировать, чтобы ускорить процесс рендера страницы. 
Кеширование элементов: html, css, js, font, ico.

`expire` - Время жизни в минутах.

### Методы очистки кеша

`https://sitename.tld/?cleaner` - получение браузерного идентификатора для очистки кеша. 
После получения идентификатора браузером, происходит перенаправление на главную страницу сайта.
Сверху слева появится иконка перезагрузки &#10227; при нажатии, произойдёт очистка
кеша и перезагрузка страницы.

## privoxy
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

## metrics
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

## mail
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


## favicon
```json
	"favicon": {
		"enabled": true
	}
```

Замена favicon на сайте
Необходимо добавить файл favicon в директорию `app/favicon`. Имя файла должно совпадать с именем основного домена `sitename.tld.ico`

## inject
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
			"compress": true,
			"type": "tilda",
			"site": "https://sitename.tld",
			"project": "https://project123-constructor.tld"
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
];
```