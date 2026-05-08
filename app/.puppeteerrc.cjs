module.exports = {
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH ?? '/usr/bin/chromium-browser',
    cacheDirectory: '/tmp/puppeteer-cache',
};
