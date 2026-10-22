import { spawn } from 'node:child_process'
import { randomUUID } from 'node:crypto'
import { rm } from 'node:fs/promises'
import { join } from 'node:path'
import { tmpdir } from 'node:os'

const databasePath = join(tmpdir(), `new-portfolio-navigation-${randomUUID()}.sqlite`)
const runner = spawn(process.execPath, ['node_modules/@playwright/test/cli.js', 'test', 'tests/e2e/mobile-navigation.spec.ts'], {
  stdio: 'inherit',
  env: { ...process.env, PLAYWRIGHT_TEST_DB_DATABASE: databasePath },
})

let requestedSignal = null
for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => {
    requestedSignal = signal
    runner.kill(signal)
  })
}

let exitCode = 1
try {
  exitCode = await new Promise((resolve, reject) => {
    runner.once('error', reject)
    runner.once('exit', (code, signal) => resolve(code ?? (signal === 'SIGINT' ? 130 : 143)))
  })
} finally {
  await rm(databasePath, { force: true })
}

process.exitCode = requestedSignal === 'SIGINT' ? 130 : requestedSignal === 'SIGTERM' ? 143 : exitCode
