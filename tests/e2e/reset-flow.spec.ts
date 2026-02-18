import { expect, test } from '@playwright/test'

test('reset functionality flow', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByTestId('teams-page')).toBeVisible()
  await page.getByTestId('generate-fixture-button').click()
  await expect(page).toHaveURL(/\/fixtures$/)
  await page.getByTestId('start-simulation-button').click()
  await expect(page).toHaveURL(/\/simulation$/)

  await page.getByTestId('play-week-button').click()
  await page.waitForLoadState('networkidle')
  await page.getByTestId('play-week-button').click()
  await page.waitForLoadState('networkidle')
  await page.getByTestId('play-week-button').click()
  await page.waitForLoadState('networkidle')
  await expect(page.getByRole('heading', { name: 'Week 3 of 6' })).toBeVisible()

  page.once('dialog', dialog => dialog.accept())
  await page.getByTestId('reset-button').click()
  await page.waitForLoadState('networkidle')
  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByTestId('teams-page')).toBeVisible()
  await expect(page.getByTestId('generate-fixture-button')).toBeVisible()
  await expect(page.getByTestId('team-card')).toHaveCount(4)

  await page.goto('/simulation')
  await page.waitForLoadState('networkidle')
  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByTestId('teams-page')).toBeVisible()
})
