import fs from 'fs';
import moment from 'moment';
import date from 'date-and-time';
import minimist from 'minimist';
import { EventEmitter } from 'node:events';
import { createClient } from 'redis';

import Sitemapper from 'sitemapper';
import randomUserAgent from 'random-user-agent';
const SitemapInstance = new Sitemapper();
// import { Resolver } from 'node:dns';
// const DNSResolver = new Resolver();
// DNSResolver.setServers(['4.4.4.4']);
import { launch } from 'puppeteer-core';
import prettyMilliseconds from 'pretty-ms';
import { performance } from "node:perf_hooks";

class Emitter extends EventEmitter {}
const Event = new Emitter();
const basedir = '/home/wilda/www';


/**
 * This class describes a Chromium Instance server.
 *
 * @class      ChromiumInstance (name)
 */
class ChromiumInstance {
	// maxRunningInstances = 4;
	dateFormat = 'YYYY-MM-DD HH:mm:ss';

	browser = null;

	running = {
		counter: 0
	};

	summary = {
		success: 0,
		broken: 0,
		error: 0
	};

	domain = null;

	service = 'Chromium Server';
	redis = {
		prefix: 'chromium',
		links: 'summary:links'
	};

	/**
	 * { function_description }
	 *
	 * @return     {Promise}  { description_of_the_return_value }
	 */
	async browserRun() {

		const browserArgs = this.config.chromium.proxy.enabled
			? Object.assign(this.config.chromium.args, [
			`--proxy-server=http://${this.config.chromium.proxy.host}:${this.config.chromium.proxy.port}`
		]) : this.config.chromium.args;

		// console.log(browserArgs);

		this.browser = await launch({
			executablePath: this.config.chromium.executablePath,
			headless: true,
			args: browserArgs
		});

		this.incognito = await this.browser.createIncognitoBrowserContext();

	};

	/**
	 * { function_description }
	 */
	async browserClose() {
		await this.browser.close();
	};


	/**
	 * { function_description }
	 *
	 * @return     {Promise}  { description_of_the_return_value }
	 */
	async redisConnect() {
		const connection = createClient({
			socket: {
				host: this.config.global.storage.redis.host
			},
			enable_offline_queue: false,
			retry_strategy: (options) => {
				return Math.min(options.attempt * 100, this.config.global.storage.redis.retryTime);
			},
			connect_timeout: Number.MAX_SAFE_INTEGER
		});

		await connection.connect();

		return connection;
	}


	/**
	 * { function_description }
	 *
	 * @param      {<type>}  size    The size
	 * @return     {<type>}  { description_of_the_return_value }
	 */
	genHash(size) {
		return Math.random().toString(36).substr(2, size);
	}

	/**
	 * { function_description }
	 *
	 * @param      {<type>}  s       { parameter_description }
	 * @return     {<type>}  { description_of_the_return_value }
	 */
	decode(s) {
		try {
			// s = s.trim();
			return JSON.parse(s);
		} catch (e) {
			return console.error(e);
		}
	}


	/**
	 * { function_description }
	 *
	 * @param      {<type>}  url     The url
	 * @return     {<type>}  { description_of_the_return_value }
	 */
	parseUrlDomain(url) {
		const obj = new URL(url);
		return obj.hostname;
	}


	/**
	 * Gets the latest date.
	 *
	 * @return     {<type>}  The latest date.
	 */
	getLatestDate() {
		const dt = new Date();
		return date.format(dt, 'YYYYMMDDHHmmss');
	}

	/**
	 * { function_description }
	 *
	 * @return     {Promise}  { description_of_the_return_value }
	 */
	async subscribe() {
		const connection = createClient({
			socket: {
				host: this.config.global.storage.redis.host
			},
			enable_offline_queue: false,
			retry_strategy: (options) => {
				return Math.min(options.attempt * 100, this.config.global.storage.redis.retryTime);
			},
			connect_timeout: Number.MAX_SAFE_INTEGER
		});
		const subscriber = connection.duplicate();

		await connection.connect();

		// console.log(`Started chromium subscriber. Version: ${this.config.chromium.version}`);

		subscriber.on('error', err => console.error(err));
		await subscriber.connect();
		await subscriber.subscribe(this.config.chromium.topic, payload => {
			const dec = this.decode(payload);

			try {

				if (dec.url === undefined || dec.event === undefined)
					throw new TypeError('error response params');

				Event.emit(dec.event, dec);

	        } catch (err) {
	        	console.error(err);
	        }

		});
	}


    /**
     * { function_description }
     *
     * @param      {<type>}  link      The link
     * @param      {Array}   [res=[]]  The resource
     * @return     {Array}   { description_of_the_return_value }
     */
    prepareDimensionsLinks(links, res = []) {
        for (const link of links) {
	        for (const dim of Object.keys(this.config.chromium.dimensions)) {
	            res.push({ url: link, dimension: dim });
	        }
        }

        return res;
    }


	/**
	 * { function_description }
	 *
	 * @return     {Promise}  { description_of_the_return_value }
	 */
	async waitForAvailable(time = 5000)
	{
		const promise = (time) => {
			return new Promise(resolve => setTimeout(resolve, time));
		}

		while (this.running.counter > this.config.chromium.maxRunningInstances) {
			await promise(time);
		}
	}

	async getSitemapUrls(link) {
		
		let error = false;
		let ret = [];

        const links = await SitemapInstance.fetch({
        	url: `${link}/sitemap.xml`,
        	timeout: this.config.chromium.timeout
        }).then((sd) => {
            return sd.sites;
        }).catch((err) => {
        	console.error(err);
        	error = true;
        	return [];
        });

        if (links.length > 0) {
            ret = links;
        } else {
        	ret = [link];
        }

        if (error) {
        	ret = [];
        }

        return ret;
	}

    /**
     * Gets the site urls.
     *
     * @param      {<type>}   site    The site
     * @return     {Promise}  The site urls.
     */
    /*async getSiteUrls(link) {
    	let ret = [];
        try {

        	return await this.getSitemapUrls(link);

        } catch (err) {
        	console.log(err);
        	console.log(`Error fetch ${link}/sitemap.xml`);
            return [link];
        }
    };*/


    /**
     * Gets the dimension pages.
     *
     * @param      {<type>}   links   The links
     * @return     {Promise}  The dimension pages.
     */
    async gotoPage(link) {


        try {

	    	performance.mark('open_page');

	    	// performance.mark('new_page');
			// Открываем вкладку
			const page = await this.incognito.newPage();

	    	// performance.mark('new_page_opened');

			// Устанавливаем user agent
			await page.setUserAgent(randomUserAgent(link.dimension));

			// Ждём открытия вкладки (баг)
			// await page.waitForTimeout(this.config.chromium.wait);

			// Устанавливаем разрешение дисплея 
	    	await page.setViewport(this.config.chromium.dimensions[link.dimension]);

	    	page.setDefaultTimeout(this.config.chromium.timeout);

	    	// Переходим по ссылке
	        const pageResponse = await page.goto(link.url, {
	        	waitUntil: 'domcontentloaded'
	        });

	        const pageResponseStatusCode = pageResponse.status();
	        // console.log(pageResponse);

			// Ждём загрузки страницы (баг)
	        // await page.waitForTimeout(this.config.chromium.wait);

	        // Закрываем вкладку
			await page.close();

			if (pageResponseStatusCode === 200)
				this.summary.success++;
			else
				this.summary.broken++;

	    	performance.mark('close_page');

	    	const measureTimePageLoad = performance.measure('page', 'open_page', 'close_page');
	    	// const measureTimeNewPageOpen = performance.measure('newPage', 'new_page', 'new_page_opened');
	    	const measurePageLoadDuration = prettyMilliseconds(measureTimePageLoad.duration, {compact: true});
        	// console.log(measureTimeNewPageOpen.duration);
			console.log(`Autocache url: ${link.url}, dimension: ${link.dimension}, status: ${pageResponseStatusCode}, runtime: ${measurePageLoadDuration}`);

        } catch (err) {
			this.summary.error++;
			console.log(`Error url: ${link.url}, dimension: ${link.dimension}`);
        	console.error(err.message);
        } finally {
	    	performance.mark('close_page');
        }

    }

    /**
     * Gets the dimension pages.
     *
     * @param      {<type>}   links   The links
     * @return     {Promise}  The dimension pages.
     */
    async gotoPages(links) {
        try {

	        for (const link of links) {
				await this.gotoPage(link);
	        }

        } catch (err) {
        	console.error(err);
        }
    };


	/**
	 * Получение и обход ссылок
	 *
	 * @param      {<type>}   response  The response
	 * @return     {Promise}  { description_of_the_return_value }
	 */
	async crawler(link) {

		try {

			this.domain = link.replace('http://', '')
                .replace('https://', '')
                .replace('/', '');

			console.log(`Starting autocache site: ${link}`);


            const rp = `${this.config.global.name}`;
			// const lastRunDate = moment().format('YYYY-MM-DD HH:mm:ss');
			const lastRunDate = new Date().toISOString().replace('T', ' ').split('.')[0];

			await this.redis.client.set(
				`${rp}:${this.config.global.storage.keys.chromium.lastrun}`,
				lastRunDate
			);

			// Проверяем наличие ключа запущенного процесса chromium
			if (!await this.redis.client.get(`${rp}:${this.config.global.storage.keys.chromium.running}:${this.domain}`))
			{
				// Устанавливаем ключ запущенной задачи
				await this.redis.client.set(
					`${rp}:${this.config.global.storage.keys.chromium.running}:${this.domain}`,
					0,
					{
						'EX': 120
					},
					() => {}
				);

				// Формируем список ссылок из карты сайта
				const sitemapLinks = await this.getSitemapUrls(link);

				if (sitemapLinks.length > 0)
				{
					// Загружаем страницы
					console.log(`Autocache found domain: ${link}, links: ${sitemapLinks.length}`);
					const siteDimUrlsArray = this.prepareDimensionsLinks(sitemapLinks);
					await this.gotoPages(siteDimUrlsArray);
				}
				else
				{
					console.log(`Autocache empty ${link}`);
				}


				// Удаляем ключ запущенной задачи
				await this.redis.client.del(
					`${rp}:${this.config.global.storage.keys.chromium.running}:${this.domain}`
				);
			}
			else
			{
				console.log(`Autocache ${link} already running`);
			}

			// Записываем время последнего обхода сайта
			await this.redis.client.hSet(
				`${rp}:${this.config.global.storage.keys.chromium.links}:${this.domain}`,
				"lastrun",
				lastRunDate
			);
			
			// Записываем кол-во успшных страниц
			await this.redis.client.hSet(
				`${rp}:${this.config.global.storage.keys.chromium.links}:${this.domain}`,
				"success",
				this.summary.success
			);
			
			// Записываем кол-во неудачных страниц
			await this.redis.client.hSet(
				`${rp}:${this.config.global.storage.keys.chromium.links}:${this.domain}`,
				"broken",
				this.summary.broken
			);

			// Записываем кол-во ошибочных страниц
			await this.redis.client.hSet(
				`${rp}:${this.config.global.storage.keys.chromium.links}:${this.domain}`,
				"error",
				this.summary.error
			);

		} catch (err) {
			console.error(err);
		}
	}


	/**
	 * Constructs a new instance.
	 *
	 * @return     {<type>}  { description_of_the_return_value }
	 */
	constructor() {
		this.config = {
			global: JSON.parse(fs.readFileSync(`${basedir}/app/config/global.json`)),
			hosts: JSON.parse(fs.readFileSync(`${basedir}/app/config/hosts.json`)),
			chromium: JSON.parse(fs.readFileSync(`${basedir}/app/config/chromium.json`))
		}
	}
}



moment.locale('ru');

const CI = new ChromiumInstance();

Event.on('autocache', async(response) => {
	await CI.browserRun();

    for (const link of response.url)
    {
		await CI.crawler(link);
	}

	await CI.browser.close();

});

process.title = `server v${CI.config.global.version} (node ${process.version})`;

const argv = minimist(process.argv.slice(2), {
	alias: {
		help: [ 'h' ],
		verbose: [ 'v' ],
		method: [ 'm' ],
		site: [ 's' ]
	},
});

(async () => {
	
	if (argv.help)
	{
		console.log('-m subscribe');
		process.exit();
	}

	if (argv.site)
	{
		await CI.crawler(argv.site);
		process.exit();
	}

	CI.redis.client = await CI.redisConnect();
	// запуск
	switch (argv.method) {
		case 'subscribe': await CI.subscribe(); break;
		default: break;
	}

})();