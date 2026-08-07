import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const root = process.cwd();
const allowedFiles = new Set([
    'app/Console/Commands/CreateSuperAdmin.php',
    'app/Models/Concerns/BelongsToOrganization.php',
    'app/Services/ParticipantService.php',
    'app/Services/TournamentService.php',
]);
const needles = ['withoutOrganizationScope(', 'withoutGlobalScopes(', 'withoutGlobalScope('];
const violations = [];

function scan(directory) {
    for (const name of readdirSync(directory)) {
        const path = join(directory, name);
        const stat = statSync(path);
        if (stat.isDirectory()) {
            scan(path);
            continue;
        }
        if (!path.endsWith('.php')) continue;

        const file = relative(root, path).replaceAll('\\', '/');
        const source = readFileSync(path, 'utf8');
        if (needles.some((needle) => source.includes(needle)) && !allowedFiles.has(file)) {
            violations.push(file);
        }
    }
}

scan(join(root, 'app'));

if (violations.length) {
    console.error(`Unapproved tenant-scope bypass found:\n${violations.join('\n')}`);
    process.exit(1);
}

console.log('Tenant-scope bypass allowlist passed.');
