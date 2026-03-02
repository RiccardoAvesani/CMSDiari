import { promises as fs } from 'node:fs'
import path from 'node:path'

const projectRoot = process.cwd()

const srcDir = path.join(projectRoot, 'node_modules', 'tinymce')
const destDir = path.join(projectRoot, 'public', 'vendor', 'tinymce')

async function pathExists(p) {
    try {
        await fs.access(p)
        return true
    } catch {
        return false
    }
}

async function copyDir(src, dest) {
    await fs.rm(dest, { recursive: true, force: true })
    await fs.mkdir(dest, { recursive: true })

    if (typeof fs.cp === 'function') {
        await fs.cp(src, dest, { recursive: true })
        return
    }

    const entries = await fs.readdir(src, { withFileTypes: true })

    for (const entry of entries) {
        const from = path.join(src, entry.name)
        const to = path.join(dest, entry.name)

        if (entry.isDirectory()) {
            await copyDir(from, to)
            continue
        }

        if (entry.isSymbolicLink()) {
            const linkTarget = await fs.readlink(from)
            await fs.symlink(linkTarget, to)
            continue
        }

        await fs.copyFile(from, to)
    }
}

if (!await pathExists(srcDir)) {
    console.error('[copy:tinymce] Directory non trovata:', srcDir)
    console.error('[copy:tinymce] Esegui prima `npm install`.')
    process.exit(1)
}

await fs.mkdir(path.dirname(destDir), { recursive: true })
await copyDir(srcDir, destDir)

console.log('[copy:tinymce] Copiato in:', destDir)
