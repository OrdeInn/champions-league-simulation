import { expect, test } from '@playwright/test'

test('reset functionality flow', async ({ page }) => {
  await page.goto('/')
  await page.getByTestId('generate-fixture-button').click()
  await page.getByTestId('start-simulation-button').click()

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
  await expect(page.getByRole('heading', { name: 'Week 0 of 6' })).toBeVisible()

  await expect(page.locator('[data-testid^="unplayed-match-"]')).toHaveCount(2)
  await expect(page.getByTestId('prediction-placeholder')).toBeVisible()
  await expect(page.getByTestId('play-week-button')).toBeEnabled()

  const standings = await page.$$eval('[data-testid="standing-row"]', rows => {
    return rows.map(row => Array.from(row.querySelectorAll('td')).map(cell => cell.textContent?.trim() ?? ''))
  })

  expect(standings).toHaveLength(4)
  standings.forEach(row => {
    expect(row[2]).toBe('0')
    expect(row[3]).toBe('0')
    expect(row[4]).toBe('0')
    expect(row[5]).toBe('0')
    expect(row[6]).toBe('0')
    expect(row[7]).toBe('0')
    expect(row[8]).toBe('0')
    expect(row[9]).toBe('0')
  })
})
