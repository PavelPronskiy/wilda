var buttonSaveTrigger = false;
const revision = {};
const container = document.getElementById("jsoneditor");

const hostSchemaRefs = {

};

const options = {
    name: 'Сайты',
    mode: 'tree',
    // modes: ['code', 'tree'],
    // ace: ace,
    history: true,
    enableSort: false,
    enableTransform: false,
    limitDragging: true,
    /* ajv: Ajv({
        allErrors: true,
        verbose: true,
        jsonPointers: true,
        $data: true
    }), */
    schema: {
        "type": "array",
        "items": {
            "type": "object",
            "properties": {
                "type": {
                    "title": "Тип сайта",
                    "enum": ["Tilda", "Wix", "Plain"],
                    "type": "string",
                    "default": "Tilda"
                },
                "site": {
                    "type": "array",
                    "items": {
                        "type": "string"
                    }
                },
                "project": {
                    "type": "string"
                },
                "privoxy": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        }
                    }
                },
                "mail": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        },
                        "subject": {
                            "type": "string"
                        },
                        "name": {
                            "type": "string"
                        },
                        "from": {
                            "type": "string"
                        },
                        "success": {
                            "type": "string"
                        },
                        "error": {
                            "type": "string"
                        },
                        "to": {
                            "type": "array",
                            "items": {
                                "type": "string"
                            }
                        }
                    }
                },
                "favicon": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        }
                    }
                },
                "cache": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        },
                        "stats": {
                            "type": "boolean",
                            "default": true
                        },
                        "expire": {
                            "type": "number",
                            "default": 86400,
                            "pattern": "^[0-9]{1,8}$"

                        }
                    }
                },
                "inject": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        },
                        "header": {
                            "type": "boolean",
                            "default": true
                        },
                        "footer": {
                            "type": "boolean",
                            "default": true
                        }
                    }
                },
                "metrics": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        },
                        "ya": {
                            "type": [ "number", "string" ],
                            "pattern": "^[0-9]{8}$"
                        },
                        "ga": {
                            "type": "string"
                        }
                    }
                },
                "compress": {
                    "type": "object",
                    "properties": {
                        "enabled": {
                            "type": "boolean",
                            "default": true
                        }
                    }
                },

            },
            "required": [ "type", "site", "project" ]
        }
    },
    templates: [
        {
            text: 'Новый сайт',
            title: 'Добавить новый сайт',
            className: 'jsoneditor-type-object',
            field: '',
            value: GLOBAL_CONFIG.hosts[0]
        },
        {
            text: 'Почта',
            title: 'Добавить почту',
            className: 'jsoneditor-type-object',
            field: 'mail',
            value: GLOBAL_CONFIG.mail
        },
        {
            text: 'Название сайта',
            title: 'Название сайта',
            className: 'jsoneditor-type-string',
            field: 'site',
            value: 'https://example.com'
        },
        {
            text: 'Инжектор HTML',
            title: 'Добавить HTML',
            className: 'jsoneditor-type-object',
            field: 'inject',
            value: GLOBAL_CONFIG.inject
        },
        {
            text: 'Метрика',
            title: 'Добавить метрику (Yandex, Google)',
            className: 'jsoneditor-type-object',
            field: 'metrics',
            value: GLOBAL_CONFIG.metrics
        },
        {
            text: 'Фавикон',
            title: 'Добавить иконку сайта',
            className: 'jsoneditor-type-object',
            field: 'favicon',
            value: GLOBAL_CONFIG.favicon
        },
        {
            text: 'Кэширование',
            title: 'Добавить кэширование',
            className: 'jsoneditor-type-object',
            field: 'cache',
            value: GLOBAL_CONFIG.cache
        },
        {
            text: 'Сжатие страниц',
            title: 'Добавить сжатие',
            className: 'jsoneditor-type-object',
            field: 'compress',
            value: GLOBAL_CONFIG.compress
        },
        {
            text: 'Проксирование',
            title: 'Проксирование tor',
            className: 'jsoneditor-type-object',
            field: 'privoxy',
            value: GLOBAL_CONFIG.privoxy
        },
        {
            text: 'Сторейдж',
            title: 'Изменить тип сторейдж',
            className: 'jsoneditor-type-object',
            field: 'storage',
            value: {
                "type": "disk"
            }
        }
    ],
    onChange: function () {
        if (!buttonSaveTrigger) {
            buttonSaveTrigger = true;
            $('#revision-save').removeAttr('disabled');
            $('#revision-restore').removeAttr('disabled');
        }
    },
    onClassName: function({ path, field, value }) {
        return { path, field, value };
    },
    onCreateMenu: function (items, node) {
        const path = node.path;

        // console.log('items:', items, 'node:', node)

        items.forEach(function (item, index, items) {
            if (items[index].text == 'Duplicate')
                items[index].text = 'Дублировать';

            if (items[index].text == 'Remove')
                items[index].text = 'Удалить';

            if (items[index].text == 'Insert')
                items[index].text = 'Добавить';

            // if (items[index].text == 'Append')
                // items[index].text = 'Добавить';

                // console.log(items[index]);

                
            if ("submenu" in item) {

                items[index].submenu = items[index].submenu.filter(function (item) {
                    return item.type !== 'separator';
                });
                
                switch (node.path.length) {
                    case 0:
                        break;
                        
                    case 1:
                        items[index].submenu = items[index].submenu.filter(function (item) {
                                var excludes = ['Новый сайт'];
                            return excludes.includes(item.text);
                        });
                        break;
                        
                    case 2:
                        items[index].submenu = items[index].submenu.filter(function (item) {
                            var excludes = ['Auto', 'Array', 'Object', 'String', 'Новый сайт', 'Название сайта'];
                            return !excludes.includes(item.text);
                        });
                        break;

                    case 3:
                        items[index].submenu = items[index].submenu.filter(function (item) {
                            var excludes = [
                                'Auto', 'Array', 'Object', 'String', 'Новый сайт',
                                'Почта', 'Инжектор HTML', 'Метрика', 'Фавикон',
                                'Кэширование', 'Сжатие страниц', 'Сторейдж', 'Проксирование'
                            ];
                            return !excludes.includes(item.text);
                        });
                        
                        // console.log(node.path[1]);
                        if (node.path[1] == 'site')
                        {
                            items[index].submenu = items[index].submenu.filter(function (item) {
                                var excludes = [
                                    'Название сайта'
                                ];
                                return excludes.includes(item.text);
                            });
                        } else {
                            items[index].submenu = items[index].submenu.filter(function (item) {
                                var excludes = [
                                    'Название сайта'
                                ];
                                return !excludes.includes(item.text);
                            });
                            
                        }

                        break;
                        
                    case 4:
                        items[index].submenu = items[index].submenu.filter(function (item) {
                            var excludes = [
                                'Auto', 'Array', 'Object', 'String', 'Новый сайт',
                                'Почта', 'Инжектор HTML', 'Метрика', 'Фавикон', 'Кэширование',
                                'Сжатие страниц', 'Название сайта', 'Проксирование'
                            ];
                            return !excludes.includes(item.text);
                        });

                        break;
                        
                    default:
                        break;
                }
                
            }
            
        });

        /* items = items.filter(function (item) {
            return item.type !== 'separator'
        }) */

        items = items.filter(function (item) {
            var excludes = ['Type', 'Extract', 'Append'];
            return !excludes.includes(item.text);
        });

        return items;
    }
};

const editor = new JSONEditor(container, options);

var setUserConfig = function (success, beforesend, post) {
    beforesend();
    var xhr = ("onload" in new XMLHttpRequest()) ? new XMLHttpRequest() : new XDomainRequest();

    xhr.open('POST', '/?editor', true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onload = success;
    return xhr.send(JSON.stringify(post));
};

var setUserConfigRevisions = function (revisions) {
    var elems = [];
    /*                 for (let index = 0; index < revisions.length; index++) {
                        const rev = revisions[index];
                        // console.log(rev);
                        elems.push('<li>' + rev + '</li>');
                    } */

    revision.all = revisions;
    revision.prev = revisions[(revisions.length - 1)];
    $('#revision-counts').html(revisions.length);

    /*                 $('<ul/>', {
                        id: 'revision-menu',
                        html: elems.join(' ')
                    }).appendTo('#revision-status'); */
};


setUserConfig((d) => {
    var data = JSON.parse(d.target.responseText);
    setUserConfigRevisions(data.revisions);
    editor.set(data.hosts);
    editor.expandAll();
}, () => { }, {
    config: true
});

$('#revision-save').click(() => {
    setUserConfig((d) => {
        var data = JSON.parse(d.target.responseText);

        if (data.status) {
            $('#revision-save').attr('disabled', '');
            // $('#revision-restore').attr('disabled', '');

            buttonSaveTrigger = false;
            var revCounts = (parseInt($('#revision-counts').text()) + 1);
            $('#revision-counts').text(revCounts);
        }

    }, () => { }, {
        save: true,
        data: editor.getText()
    });
});

$('#revision-restore').click(() => {
    // console.log(revision.prev);
    setUserConfig((d) => {
        var data = JSON.parse(d.target.responseText);

        console.log('restored revision');
        // console.log(data);

        setUserConfig((d) => {
            var data = JSON.parse(d.target.responseText);
            setUserConfigRevisions(data.revisions);
            editor.set(data.hosts);
            editor.expandAll();
        }, () => { }, {
            config: true
        });

    }, () => { }, {
        config: true,
        revision: revision.prev
    });
});

$('#version').text(
    'ver: ' + GLOBAL_CONFIG.version + ', ' + GLOBAL_CONFIG.version_date
);
