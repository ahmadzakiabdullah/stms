import { readdir, stat } from 'node:fs/promises';
import { join } from 'node:path';

const assetDirectory = join(process.cwd(), 'public', 'build', 'assets');
const budgets = {
    '.js': Number(process.env.BUNDLE_JS_MAX_BYTES ?? 400_000),
    '.css': Number(process.env.BUNDLE_CSS_MAX_BYTES ?? 100_000),
};

const files = await readdir(assetDirectory);
const failures = [];

for (const file of files) {
    const extension = Object.keys(budgets).find((candidate) => file.endsWith(candidate));
    if (!extension) continue;

    const bytes = (await stat(join(assetDirectory, file))).size;
    if (bytes > budgets[extension]) {
        failures.push(`${file}: ${bytes} bytes exceeds ${budgets[extension]} bytes`);
    }
}

if (failures.length > 0) {
    console.error(`Bundle budget failed:\n${failures.join('\n')}`);
    process.exit(1);
}

console.log(`Bundle budget passed (JS <= ${budgets['.js']} bytes, CSS <= ${budgets['.css']} bytes).`);
