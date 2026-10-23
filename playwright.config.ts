import { defineConfig } from '@playwright/test'
import { randomUUID } from 'node:crypto'

const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8787'
const executablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
const testDatabase = process.env.PLAYWRIGHT_TEST_DB_DATABASE || `/tmp/new-portfolio-navigation-${randomUUID()}.sqlite`

export default defineConfig({
  testDir: './tests/e2e',
  testMatch: 'mobile-navigation.spec.ts',
  fullyParallel: false,
  reporter: 'list',
  use: {
    baseURL,
    browserName: 'chromium',
    launchOptions: executablePath ? { executablePath, args: ['--no-sandbox'] } : undefined,
    trace: 'retain-on-failure',
  },
  webServer: process.env.PLAYWRIGHT_BASE_URL ? undefined : {
    command: 'bash scripts/e2e-server.sh',
    url: baseURL,
    reuseExistingServer: false,
    timeout: 120_000,
    env: {
      APP_ENV: 'testing',
      APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
      DB_CONNECTION: 'sqlite',
      DB_DATABASE: testDatabase,
      DB_URL: '',
      SESSION_DRIVER: 'file',
      CACHE_STORE: 'file',
      QUEUE_CONNECTION: 'sync',
    },
  },
})
