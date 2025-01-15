let buttonSaveTrigger = false;
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
                    "enum": ["Tilda", "Wix", "Plain", "Taplink"],
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
                        "smtp": {
                            "type": "object",
                            "properties": {
                                "auth": {
                                    "type": "boolean",
                                    "default": true
                                },
                                "username": {
                                    "type": "string",
                                    "default": "test@mail.ru"
                                },
                                "host": {
                                    "type": "string",
                                    "default": "mailhost.ru"
                                },
                                "port": {
                                    "type": "number",
                                    "default": 25
                                },
                                "password": {
                                    "type": "string",
                                    "default": "userpass"
                                }
                            }
                        },
                        "send_type": {
                            "enum": ["sendmail", "smtp"],
                            "type": "string",
                            "default": "sendmail"
                        },
                        "enabled": {
                            "type": "boolean",
                            "default": false
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
                        "browser": {
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
    onChange: () => {
        if (!buttonSaveTrigger) {
            buttonSaveTrigger = true;
            $('#revision-save').removeAttr('disabled');
            $('#revision-restore').removeAttr('disabled');
        }
    },
    onClassName: ({ path, field, value }) => {
        return { path, field, value };
    },
    onCreateMenu: (items, node) => {
        const path = node.path;

        // console.log('items:', items, 'node:', node)

        items.forEach((item, index, items) => {
            if (items[index].text === 'Duplicate')
                items[index].text = 'Дублировать';

            if (items[index].text === 'Remove')
                items[index].text = 'Удалить';

            if (items[index].text === 'Insert')
                items[index].text = 'Добавить';

            // if (items[index].text == 'Append')
                // items[index].text = 'Добавить';

                // console.log(items[index]);

                
            if ("submenu" in item) {

                items[index].submenu = items[index].submenu.filter((item) => {
                    return item.type !== 'separator';
                });
                
                switch (node.path.length) {
                    case 0:
                        break;
                        
                    case 1:
                        items[index].submenu = items[index].submenu.filter((item) => {
                            return ['Новый сайт'].includes(item.text);
                        });
                        break;
                        
                    case 2:
                        items[index].submenu = items[index].submenu.filter((item) => {
                            return !['Auto', 'Array', 'Object', 'String', 'Новый сайт', 'Название сайта'].includes(item.text);
                        });
                        break;

                    case 3:
                        items[index].submenu = items[index].submenu.filter((item) => {
                            return ![
                                'Auto', 'Array', 'Object', 'String', 'Новый сайт',
                                'Почта', 'Инжектор HTML', 'Метрика', 'Фавикон',
                                'Кэширование', 'Сжатие страниц', 'Сторейдж', 'Проксирование'
                            ].includes(item.text);
                        });
                        
                        // console.log(node.path[1]);
                        if (node.path[1] === 'site')
                        {
                            items[index].submenu = items[index].submenu.filter((item) => {
                                return [
                                    'Название сайта'
                                ].includes(item.text);
                            });
                        } else {
                            items[index].submenu = items[index].submenu.filter((item) => {
                                return ![
                                    'Название сайта'
                                ].includes(item.text);
                            });
                        }

                        break;
                        
                    case 4:
                        items[index].submenu = items[index].submenu.filter((item) => {
                            return ![
                                'Auto', 'Array', 'Object', 'String', 'Новый сайт',
                                'Почта', 'Инжектор HTML', 'Метрика', 'Фавикон', 'Кэширование',
                                'Сжатие страниц', 'Название сайта', 'Проксирование'
                            ].includes(item.text);
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

        items = items.filter((item) => {
            return !['Type', 'Extract', 'Append'].includes(item.text);
        });

        return items;
    }
};

const editor = new JSONEditor(container, options);

const postData = (success, beforesend, post) => {
    beforesend();
    const xhr = ("onload" in new XMLHttpRequest()) ? new XMLHttpRequest() : new XDomainRequest();

    xhr.open('POST', '/?editor', true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onload = success;
    return xhr.send(JSON.stringify(post));
};

const postDataRevisions = (revisions) => {
    // let elems = [];
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


postData((d) => {
    const data = JSON.parse(d.target.responseText);
    postDataRevisions(data.revisions);
    editor.set(data.hosts);
    editor.expandAll();
}, () => { }, {
    config: true
});

const postNotification = (d) => {
    if (d.status)
    {
        new bs5.Toast({
            body: d.message,
            className: 'border-0 bg-primary',
        }).show()
    }
    else
    {
        new bs5.Toast({
            body: d.message,
            className: 'border-0 bg-danger',
        }).show()
    }
};

$('#revision-save').click(() => {
    postData((d) => {
        const data = JSON.parse(d.target.responseText);

        postNotification(data);

        if (data.status) {
            $('#revision-save').attr('disabled', '');
            // $('#revision-restore').attr('disabled', '');

            buttonSaveTrigger = false;
            const revCounts = (Number.parseInt($('#revision-counts').text(), 16) + 1);
            $('#revision-counts').text(revCounts);
        }

    }, () => { }, {
        save: true,
        data: editor.getText()
    });
});

$('#revision-restore').click(() => {
    // console.log(revision.prev);
    postData((d) => {
        const data = JSON.parse(d.target.responseText);

        console.log('restored revision');
        // console.log(data);
        postNotification(data);

        postData((d) => {
            const data = JSON.parse(d.target.responseText);
            postDataRevisions(data.revisions);
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
    `ver: ${GLOBAL_CONFIG.version}, ${GLOBAL_CONFIG.version_date}`
);

for (let i = HOSTS_CONFIG.length - 1; i >= 0; i--) {
    for (let s = HOSTS_CONFIG[i].site.length - 1; s >= 0; s--) {
        // console.log(HOSTS_CONFIG[i].site[s]);
        $(`<option value="${HOSTS_CONFIG[i].site[s]}">${HOSTS_CONFIG[i].site[s]}</option>`).appendTo('#cache-revalidate-site');
    }
}

function numberRange(start, end) {
  return new Array(end - start).fill().map((d, i) => i + start);
}

const numberRangeHours = numberRange(3, 13);
for (let i = 0; i < CHROMIUM_CONFIG.cron.schedule.length; i++) {
    // CHROMIUM_CONFIG.cron.schedule[i]
    if (CHROMIUM_CONFIG.cron.schedule[i].event === 'autocache') {
        // console.log(CHROMIUM_CONFIG.cron.schedule[i].time);
        const cronJobAutocacheSplit = CHROMIUM_CONFIG.cron.schedule[i].time.split(' ');
        let cronJobAutocacheHour = Number(cronJobAutocacheSplit[2].split('/')[1]);
        
        if (!cronJobAutocacheHour) {
            cronJobAutocacheHour = 0;
        }

        for (let i = 0; i < numberRangeHours.length; i++) {
            if (cronJobAutocacheHour === numberRangeHours[i]) {
                $(`<option selected value="${numberRangeHours[i]}">${numberRangeHours[i]}</option>`).appendTo('#cache-revalidate-hours');
            } else {
                $(`<option value="${numberRangeHours[i]}">${numberRangeHours[i]}</option>`).appendTo('#cache-revalidate-hours');
            }
        }
    }
}

$(document).ready(() => {

    const checkboxCronCacheEnabler = $('#cron-cache-enabled');

    if (CHROMIUM_CONFIG.cron.enabled) {
        checkboxCronCacheEnabler.prop('checked', true);
    }

    checkboxCronCacheEnabler.on('change', () => {

        if (checkboxCronCacheEnabler.is(":checked"))
        {
            postData((d) => {
                const data = JSON.parse(d.target.responseText);
                postNotification(data);

            }, () => {}, {
                'cron-cache-enabler': true,
                data: 'enabled'
            });
        }
        else
        {
            postData((d) => {
                const data = JSON.parse(d.target.responseText);
                postNotification(data);
            }, () => {}, {
                'cron-cache-enabler': true,
                data: 'disabled'
            });
        }
    });

    $('#cache-revalidate-run').click((e) => {
        postData((d) => {
            const data = JSON.parse(d.target.responseText);
            postNotification(data);
        }, () => {}, {
            'revalidate-cache': true,
            data: $('select#cache-revalidate-site').val()
        });

        e.preventDefault();
    })

    $('#cache-settings-save').click((e) => {
        postData((d) => {
            const data = JSON.parse(d.target.responseText);
            postNotification(data);
        }, () => {}, {
            'cache-settings-save': true,
            data: {
                'cache-revalidate-hours': $('select#cache-revalidate-hours').val()
            }

        });

        e.preventDefault();
    })

    // console.log(CHROMIUM_STATS.global);

    const lastrun_global = CHROMIUM_STATS.global.lastrun === 0 ? '-' : moment(CHROMIUM_STATS.global.lastrun).fromNow();

    $('#cache-global-lastrun-date').text(lastrun_global);
    $('#cache-global-links-success').text(CHROMIUM_STATS.global.links.success);
    $('#cache-global-links-broken').text(CHROMIUM_STATS.global.links.broken);
    $('#cache-global-links-error').text(CHROMIUM_STATS.global.links.error);

    // console.log(cronJobAutocache[2].split('/')[1]);

});




