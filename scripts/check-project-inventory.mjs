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
const state = readFileSync('CURRENT_STATE.md', 'utf8');
const expectedPhrases = [
    `${inventory.routes} application routes`,
    `${inventory.migrations} migration files`,
    `${inventory.controllers} controller files`,
    `${inventory.testFiles} PHP test files`,
];
const missing = expectedPhrases.filter((phrase) => !state.includes(phrase));

console.log(JSON.stringify(inventory));
if (missing.length) {
    console.error(`CURRENT_STATE.md inventory is stale:\n${missing.join('\n')}`);
    process.exit(1);
}
