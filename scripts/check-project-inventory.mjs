import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';

function countFiles(directory, extension) {
    let count = 0;
    for (const name of readdirSync(directory)) {
        const path = join(directory, name);
        const stat = statSync(path);
        count += stat.isDirectory() ? countFiles(path, extension) : Number(path.endsWith(extension));
    }
    return count;
}

const routeResult = spawnSync('php', ['artisan', 'route:list', '--json'], { encoding: 'utf8' });
if (routeResult.status !== 0) {
    console.error(routeResult.stderr || 'Unable to count Laravel routes.');
    process.exit(1);
}

const inventory = {
    routes: JSON.parse(routeResult.stdout).length,
    migrations: countFiles('database/migrations', '.php'),
    controllers: countFiles('app/Http/Controllers', '.php'),
    pages: countFiles('resources/js/Pages', '.tsx'),
    testFiles: countFiles('tests', '.php'),
};
const inventoryPhrases = [
    `${inventory.routes} application routes`,
    `${inventory.migrations} migration files`,
    `${inventory.controllers} controller files`,
    `${inventory.testFiles} PHP test files`,
];

const checks = [
    { file: 'CURRENT_STATE.md', phrases: inventoryPhrases },
    { file: 'README.md', phrases: inventoryPhrases },
    { file: 'docs/architecture/system-overview.md', phrases: [`${inventory.routes} application routes`] },
];

const failures = [];
for (const { file, phrases } of checks) {
    const content = readFileSync(file, 'utf8');
    const missing = phrases.filter((phrase) => !content.includes(phrase));
    if (missing.length) {
        failures.push(`${file} inventory is stale:\n${missing.join('\n')}`);
    }
}

console.log(JSON.stringify(inventory));
if (failures.length) {
    console.error(failures.join('\n\n'));
    process.exit(1);
}
