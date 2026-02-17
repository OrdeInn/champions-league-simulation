import { expect, test } from '@playwright/test'

test('complete simulation journey', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByTestId('teams-page')).toBeVisible()
  await expect(page.getByTestId('team-card')).toHaveCount(4)

  await page.getByTestId('generate-fixture-button').click()
  await expect(page).toHaveURL(/\/fixtures$/)
  await expect(page.getByTestId('fixtures-page')).toBeVisible()
  await expect(page.getByTestId('week-card')).toHaveCount(6)

  await page.getByTestId('start-simulation-button').click()
  await expect(page).toHaveURL(/\/simulation$/)
  await expect(page.getByTestId('simulation-page')).toBeVisible()
  await expect(page.getByTestId('league-table')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Week 0 of 6' })).toBeVisible()

  for (let week = 1; week <= 4; week++) {
    await page.getByTestId('play-week-button').click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByRole('heading', { name: `Week ${week} of 6` })).toBeVisible()
  }

  await expect(page.getByTestId('prediction-panel')).toBeVisible()
  await expect(page.getByTestId('probability-bar')).toHaveCount(4)
  const probabilities = await page.locator('[data-testid="probability-bar"] .font-mono-broadcast').allTextContents()
  const probabilitySum = probabilities
    .map(value => Number.parseFloat(value.replace('%', '')))
    .reduce((sum, value) => sum + value, 0)
  expect(probabilitySum).toBeGreaterThan(99.5)
  expect(probabilitySum).toBeLessThan(100.5)

  await page.getByTestId('play-all-button').click()
  await page.waitForLoadState('networkidle')
  await expect(page.getByRole('heading', { name: 'Week 6 of 6' })).toBeVisible()

  await expect(page.getByTestId('play-week-button')).toBeDisabled()
  await expect(page.getByTestId('play-all-button')).toBeDisabled()

  const standings = await page.$$eval('[data-testid="standing-row"]', rows => {
    return rows.map(row => Array.from(row.querySelectorAll('td')).map(cell => cell.textContent?.trim() ?? ''))
  })

  expect(standings).toHaveLength(4)
  standings.forEach(row => {
    expect(row).toHaveLength(10)
    expect(row[2]).toBe('6')
  })

  const finalProbabilities = await page.locator('[data-testid="probability-bar"] .font-mono-broadcast').allTextContents()
  const highestProbability = Math.max(...finalProbabilities.map(value => Number.parseFloat(value.replace('%', ''))))
  expect(highestProbability).toBeGreaterThanOrEqual(99.9)
})
