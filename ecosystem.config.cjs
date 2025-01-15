module.exports = {
	apps: [
    {
        "name": "chromium",
        "description": "Chromium subscriber",
        "namespace": "prod",
        "script": "chromium.js",
        "args": "-m subscribe",
        "cwd": "/home/wilda/www",
        "autorestart": true,
        "max_restarts": 9999,
        "watch": [
            "chromium.js",
            ".chromium.json",
            "app/config/global.json",
            "app/config/hosts.json",
            "app/config/chromium.json"
        ],
        "ignore_watch": [
            ".git",
            "node_modules"
        ],
        "restart_delay": 5000
    }
	]
};

// ,
//     {
//         "name": "cron",
//         "description": "cron",
//         "namespace": "prod",
//         "script": "cron.js",
//         "args": "",
//         "cwd": "/home/wilda/www",
//         "autorestart": true,
//         "max_restarts": 9999,
//         "watch": [
//             "cron.js",
//             ".chromium.json"
//         ],
//         "ignore_watch": [
//             ".git",
//             "node_modules"
//         ],
//         "restart_delay": 5000
//     }