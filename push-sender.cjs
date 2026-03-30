const webpush = require('web-push');
const fs = require('fs');
const path = require('path');

// Ожидаем путь к файлу с настройками в первом аргументе
const configPath = process.argv[2];
const logFile = path.resolve(__dirname, 'storage/logs/push_debug.log');

function log(msg) {
    try {
        const time = new Date().toISOString();
        if (fs.existsSync(path.dirname(logFile))) {
            fs.appendFileSync(logFile, `[${time}] ${msg}\n`);
        }
    } catch (e) {}
    console.log(msg);
}

if (!configPath) {
    log('CRITICAL: Config path missing');
    process.exit(1);
}

const run = async () => {
    try {
        const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
        
        webpush.setVapidDetails(
            'mailto:admin@example.com',
            config.vapid_public,
            config.vapid_private
        );

        const payload = config.payload;
        const subscriptions = Array.isArray(config.subscriptions) ? config.subscriptions : [config.subscription];
        
        log(`INFO: Starting batch push for ${subscriptions.length} subscribers`);

        const promises = subscriptions.map(sub => 
            webpush.sendNotification(sub, payload, { TTL: 3600 })
                .then(res => ({ success: true, endpoint: sub.endpoint, status: res.statusCode }))
                .catch(err => ({ success: false, endpoint: sub.endpoint, error: err.message }))
        );

        const results = await Promise.all(promises);
        
        const successCount = results.filter(r => r.success).length;
        const failCount = results.length - successCount;
        
        log(`DONE: Success: ${successCount}, Failed: ${failCount}`);
        
        if (failCount > 0) {
            results.filter(r => !r.success).forEach(r => log(`FAIL: ${r.endpoint} -> ${r.error}`));
        }

        process.exit(0);
    } catch (e) {
        log(`CRITICAL: Setup error: ${e.message}`);
        process.exit(1);
    }
};

run();
