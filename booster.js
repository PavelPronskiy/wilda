

const fs = require('fs');
const path = require('path');
var request = require('request-promise');

const puppeteer = require('puppeteer-core');
const { PuppeteerScreenRecorder } = require('puppeteer-screen-recorder');
const basedir = '/home/operate/www';
const global_config = JSON.parse(fs.readFileSync('app/config/global.json'));
const hosts_config = JSON.parse(fs.readFileSync('app/config/hosts.json'));
const booster_config = JSON.parse(fs.readFileSync('app/config/booster.json'));
const config = Object.assign({}, global_config, booster_config);

var browser;
var sqlInstance;
var testForms = {};
var screenshotFileSuffix = '.png';


process.title = 'booster (node ' + process.version + ')';

var Phantomas = {
	service: 'phantomas',
	connectTimeout: 4000,
	reconnectPeriod: 1000,
	browserRun: async function() {
		browser = await puppeteer.launch({
			executablePath: config.phantomas.executablePath,
			headless: true,
			args: config.phantomas.args
		});
	},
	browserClose: async function() {
		await browser.close();
	},
	screenshot: async function(p, s) {
		var f = s + '_orig' + screenshotFileSuffix;
		var t = s + '_thumb' + screenshotFileSuffix;
		await p.screenshot({
			path: config.phantomas.screenshot.dir + '/' + f,
			fullPage: false
		});

		await gm(config.phantomas.screenshot.dir + '/' + f)
		.resize(
			config.phantomas.dimensions.thumb.width,
			config.phantomas.dimensions.thumb.height
		)
		.noProfile()
		.write(config.phantomas.screenshot.dir + '/' + t, function (err) {
			if (err) {
				console.log(err);
			}
		});

		return { orig: f, thumb: t };
	},
	getSites: async () => {

		for (const host of hosts_config) {
			for (const site of host.site) {
				console.log(site);
				const robotsData = await request(site + '/robots.txt');

				// console.log(robotsData);
				console.log(robotsData);
			}
		}
	},
	requestUrl: async (url) => {
		return await request(url);
	},
	getPage: async (url, dimension) => {
		var results = {};
		results.status = false;
		results.message = '';
		results.poster = 'poster_' + clientId + '.webp';

		// Открываем браузер
		await Phantomas.browserRun();

		// Открываем вкладку в браузере
		var page = await browser.newPage();

		// Устанавливаем размер вкладки
		await page.setViewport(dimension);

		// Инициализируем запись
		const recorder = new PuppeteerScreenRecorder(page);

		// Переходим по ссылке
		await page.goto(url, { waitUntil: 'domcontentloaded' });

		await page.waitForTimeout(1000);

		// Создаём постер
		await page.screenshot({
			path: config.phantomas.screenshot.dir + '/' + results.poster,
			fullPage: false
		});

		console.log(results);

	}
};

Phantomas.getSites();

