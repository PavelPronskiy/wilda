const fs = require('fs');
const vm = require("vm");
const crypto = require('crypto');
const path = require('path');
const cronInstance = require('node-cron');
const sharp = require('sharp');
const PerformanceTimer = require('execution-time')();
const { program } = require('commander');
const request = require('request-promise');
const errors = require('request-promise/errors');
const Sitemapper = require('sitemapper');
const randomUserAgent = require('random-user-agent');
const SitemapInstance = new Sitemapper();
const puppeteer = require('puppeteer-core');
const bluebird = require("bluebird");
// const pool = workerpool.pool();

const GLOBAL_CONFIG = JSON.parse(fs.readFileSync('app/config/global.json'));
const HOSTS_CONFIG = JSON.parse(fs.readFileSync('app/config/hosts.json'));
const REPORTER_CONFIG = JSON.parse(fs.readFileSync('app/config/reports.json'));

// var BrowserInstance;

const REPORT_DATE_PATH = new Date().toISOString().slice(0, 19).replace('T', '-').replace(/:/g, '');
const REPORT_DATE_STRING = new Date().toISOString().slice(0, 19).replace('T', ' ');

program
    .option('--report')
    .option('--cron')
    .option('--boost');

program.parse();

const CommanderOptions = program.opts();
if (!CommanderOptions.report && !CommanderOptions.boost && !CommanderOptions.cron) {
    return console.log('No args defined');
}

const ReporterInstance = {
    counter: {
        success: 0,
        errors: 0
    },
    service: 'ReporterInstance',
    connectTimeout: 4000,
    reconnectPeriod: 1000,

    /**
     * { function_description }
     *
     * @param      {string}  site    The site
     * @return     {<type>}  { description_of_the_return_value }
     */
    site: (site) => {
        this.targetSite = site ? site
            .replace('https://', '')
            .replace('http://', '')
            .replace('/', '') : this.targetSite;

        return this.targetSite;
    },

    /**
     * Sets the path url.
     *
     * @param      {string}  url     The new value
     * @return     {string}  { description_of_the_return_value }
     */
    setPathUrl: (url) => {
/*        let t = url
            .replace('https://', '')
            .replace('http://', '')
            .replace(/\/$/, '')
            .split('/')
            .slice(1, 20)
            .join('-');*/

        return crypto
        	.createHash('md5')
        	.update(url)
        	.digest("hex");
    },


    /**
     * { function_description }
     *
     * @param      {<type>}  link    The link
     * @param      {<type>}  dim     The dim
     * @param      {<type>}  report  The report
     * @return     {<type>}  { description_of_the_return_value }
     */
    processPage: async (link, dim, report) => {

        var data = report ?
            await ReporterInstance.getScreenshotPage(link, dim) :
            await ReporterInstance.boostPage(link, dim);

        return data;

    },


    /**
     * { function_description }
     *
     * @param      {<type>}  links     The links
     * @param      {Array}   [res=[]]  The resource
     * @return     {Array}   { description_of_the_return_value }
     */
    prepareDimensionsLinks: async (links, res = []) => {
        for (const link of links) {
            for (const dim of Object.keys(REPORTER_CONFIG.dimensions)) {
                res.push({ url: link, dimension: dim });
            }
        }

        return res;
    },


    /**
     * Gets the site urls.
     *
     * @param      {string}  site    The site
     * @return     {Array}   The site urls.
     */
    getSiteUrls: async (site) => {
        try {
            const robotsData = await request(site + '/robots.txt');
            let sitemapData = [];
            // console.log(robotsData);
            for (const robotsDataLine of robotsData.split("\n")) {
                if (robotsDataLine.match(/^Sitemap/)) {
                    let sitemapUrl = robotsDataLine.replace('Sitemap: ', '');
                    sitemapData = await SitemapInstance.fetch(sitemapUrl).then((sd) => {
                        return sd.sites;
                    });

                    return sitemapData;
                }
            }

        } catch (err) {
            ReporterInstance.counter.errors++;
            return [site];
        }


        return [site];
    },


    /**
     * { function_description }
     *
     * @param      {<type>}    urls        The urls
     * @param      {Function}  screenshot  The screenshot
     * @return     {<type>}    { description_of_the_return_value }
     */
    parseBluebirdLinks: async (urls, screenshot) => {

        urls = ReporterInstance.prepareDimensionsLinks(urls);

        const withBrowser = async (fn) => {

            const browser = await puppeteer.launch({
                executablePath: REPORTER_CONFIG.executablePath,
                headless: true,
                args: REPORTER_CONFIG.args
            });

            try {
                return await fn(browser);
            } finally {
                await browser.close();
            }
        };

        const withPage = (browser) => async (fn) => {
            const page = await browser.newPage();

            if (!screenshot) {
                // Turns request interceptor on.
                await page.setRequestInterception(true);

                // Ignore all the asset requests, just get the document.
                page.on('request', request => {
                    if (request.resourceType() === 'document') {
                        request.continue();
                    } else {
                        request.abort();
                    }
                });
            }

            try {
                return await fn(page);
            } finally {
                await page.close();
            }
        };

        const results = await withBrowser(async (browser) => {
            return bluebird.map(urls, async (obj) => {
                return withPage(browser)(async (page) => {

                    PerformanceTimer.start(obj.url);

                    let outputReportDir = REPORTER_CONFIG.screenshot.dir + '/' +
                        REPORT_DATE_PATH + '/' +
                        ReporterInstance.site();

                    let outputFullScreenshotFile = outputReportDir + '/' +
                    	ReporterInstance.setPathUrl(obj.url) + '-' +
                    	obj.dimension + '_full.webp';

                    let outputThumbnailScreenshotFile = outputReportDir + '/' +
                    	ReporterInstance.setPathUrl(obj.url) + '-' +
                    	obj.dimension + '_thumb.webp';

                    let res = {
                        url: obj.url,
                        site: ReporterInstance
                            .site()
                            .replace('http://', '')
                            .replace('https://', '')
                            .replace('/', ''),
                        dimension: {
                            type: obj.dimension,
                            resolution: REPORTER_CONFIG.dimensions[obj.dimension]
                        }
                    };

                    // Устанавливаем user agent
                    await page.setUserAgent(randomUserAgent(obj.dimension));

                    // Устанавливаем размер вкладки
                    await page.setViewport(REPORTER_CONFIG.dimensions[obj.dimension]);

                    try {

                        // Переходим по ссылке
                        await page.goto(obj.url, { waitUntil: 'domcontentloaded' });
                        /*await page.waitForNavigation({
							waitUntil: 'networkidle0',
						});*/

                        if (screenshot) {

                            await page.waitForTimeout(REPORTER_CONFIG.wait);

                            if (!fs.existsSync(outputReportDir)) {
                                fs.mkdirSync(outputReportDir, { recursive: true });
                            }

                            // Создаём скриншот
                            await page.screenshot({
                                path: outputFullScreenshotFile,
                                fullPage: true
                            });

                            // Создаём превью
                            await sharp(outputFullScreenshotFile)
                                .resize(150, 150)
                                .toFile(outputThumbnailScreenshotFile);

                            res.screenshots = {
                                thumb: outputThumbnailScreenshotFile,
                                full: outputFullScreenshotFile
                            };

                            res.title = await page.title();

                        }

                        ReporterInstance.counter.success++;

                    } catch (err) {
                        ReporterInstance.counter.errors++;
                        res.error = err.message;
                    }

                    let runtime = PerformanceTimer.stop(obj.url);
                    res.runtime = runtime.preciseWords;

                    return res;
                });
            }, {
                concurrency: REPORTER_CONFIG.threads
            });
        });

        return results;
    },


    /**
     * { function_description }
     *
     * @param      {string}  method  The method
     */
    run: async () => {
        let results = {},
            args;
        results.stats = {};
        results.data = [];

        if (CommanderOptions.boost) {
            args = 'boost';
        }

        if (CommanderOptions.report) {
            args = 'report';
        }

        process.title = 'reporter cron (node ' + process.version + ')';

        if (CommanderOptions.cron) {
            return ReporterInstance.cron();
        }

        // Остальное для boost, report
        PerformanceTimer.start(args);

        for (const host of HOSTS_CONFIG) {
            let siteResults = [];

            console.log('Run: ' + args + ', sites: ' + host.site.join(', '));
            for (const site of host.site) {
                ReporterInstance.site(site);
                let res = await ReporterInstance.getSiteUrls(site);
                if (res.length > 0) {
                    siteResults.push(
                        await ReporterInstance.parseBluebirdLinks(
                            res,
                            args == 'report' ? true : false
                        )
                    );
                }
            }

            results.data.push(siteResults);
        }

        let runtime = PerformanceTimer.stop(args);

        if (args === 'report') {

            var outputReportDir = REPORTER_CONFIG.screenshot.dir + '/' + REPORT_DATE_PATH;

            if (!fs.existsSync(outputReportDir)) {
                fs.mkdirSync(outputReportDir, { recursive: true });
            }

            let outputIndexJSON = REPORTER_CONFIG.screenshot.dir + '/' + REPORT_DATE_PATH + '/' + 'index.json';

            results.stats = {
                date: REPORT_DATE_STRING,
                total: results.data.length,
                runtime: runtime.preciseWords,
                counters: ReporterInstance.counter
            };

            fs.writeFileSync(outputIndexJSON, JSON.stringify(results));
        }

        console.log(args + ' complete, sites: ' + results.data.length + ', runtime: ' + runtime.preciseWords);
    },

    /**
     * { function_description }
     *
     * @return     {<type>}  { description_of_the_return_value }
     */
    cron: () => {

        if (!REPORTER_CONFIG.cron.enabled) {
            return console.log('Cron not enabled');
        }

        console.log('Started service: cron');

        REPORTER_CONFIG.cron.jobs.forEach((cronJob, index) => {
            if (cronInstance.validate(cronJob.time)) {
                new cronInstance.schedule(cronJob.time, async () => {
                    // var cmd = JSON.parse(cronJob.command);
                    PerformanceTimer.start(cronJob.command);

                    if (CommanderOptions.cron) {
                        delete CommanderOptions.cron;
                    }

                    if (CommanderOptions.boost) {
                        delete CommanderOptions.boost;
                    }

                    if (CommanderOptions.report) {
                        delete CommanderOptions.report;
                    }

                    CommanderOptions[cronJob.command] = true;

                    await ReporterInstance.run();
                    // console.log(cronJob.command);
                    let runtime = PerformanceTimer.stop(cronJob.command);

                    console.log('Cron job complete: ' + cronJob.command + ', runtime: ' + runtime.preciseWords);
                }, {
                    scheduled: true,
                    timezone: REPORTER_CONFIG.timezone
                });
            } else {
                console.log('Invalid time format: ' + cronJob.time);
            }
        });
    }

};

ReporterInstance.run();