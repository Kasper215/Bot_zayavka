const webpush = require('web-push');
const fs = require('fs');
const path = require('path');

// Ожидаем путь к файлу с настройками в первом аргументе
const configPath = process.argv[2];
const logFile = path.resolve(__dirname, 'storage/logs/push_debug.log');

function log(msg) {
    try {
        const time = new Date().toISOString();
        fs.appendFileSync(logFile, `[${time}] ${msg}\n`);
    } catch (e) {}
    console.log(msg);
}

if (!configPath) {
    log('CRITICAL: Config path missing');
    process.exit(1);
}

try {
    const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
    
    webpush.setVapidDetails(
        'mailto:admin@example.com',
        config.vapid_public,
        config.vapid_private
    );

    webpush.sendNotification(config.subscription, config.payload, { TTL: 60 })
        .then(result => {
            log(`SUCCESS: Push sent status: ${result.statusCode}`);
            process.exit(0);
        })
        .catch(error => {
            log(`ERROR: Push failed: ${error.message || error}`);
            process.exit(1);
        });
} catch (e) {
    log(`CRITICAL: Setup error: ${e.message}`);
    process.exit(1);
}
