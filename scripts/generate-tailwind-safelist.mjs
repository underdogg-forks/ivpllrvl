import glob from 'glob';
import fs from 'node:fs';

const phpFiles = glob.sync('modules/**/resources/views/**/*.php');
const classes = new Set();
const utilityPattern = /^[a-zA-Z][a-zA-Z0-9:_\-\/\[\].%]+$/;

for (const file of phpFiles) {
    const contents = fs.readFileSync(file, 'utf8');

    for (const match of contents.matchAll(/class\s*=\s*["']([^"']+)["']/g)) {
        for (const token of match[1].split(/\s+/)) {
            const candidate = token.trim();
            if (!candidate || !utilityPattern.test(candidate)) {
                continue;
            }

            classes.add(candidate);
        }
    }
}

const safelist = [...classes].sort();
fs.writeFileSync('tailwind.safelist.json', JSON.stringify(safelist, null, 2) + '\n');
console.log(`Generated ${safelist.length} class names in tailwind.safelist.json`);
