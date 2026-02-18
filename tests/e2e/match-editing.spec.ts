import { expect, test } from '@playwright/test'

test('match result editing flow', async ({ page }) => {
  await page.goto('/')
  await page.getByTestId('generate-fixture-button').click()
  await page.getByTestId('start-simulation-button').click()

  await page.getByTestId('play-week-button').click()
  await page.waitForLoadState('networkidle')
  await expect(page.getByRole('heading', { name: 'Week 1 of 6' })).toBeVisible()

  const tableBeforeEdit = await page.locator('[data-testid="league-table"]').textContent()

  const playedMatch = page.locator('[data-testid^="played-match-"]').first()
  const playedMatchTestId = await playedMatch.getAttribute('data-testid')
  expect(playedMatchTestId).toBeTruthy()
  const matchId = playedMatchTestId?.replace('played-match-', '')
  expect(matchId).toBeTruthy()
  await page.getByTestId(`edit-match-${matchId}`).click()

  await expect(page.getByTestId('match-result-editor')).toBeVisible()
  await page.getByTestId('home-score-input').fill('9')
  await page.getByTestId('away-score-input').fill('0')
  await page.getByTestId('save-edit-button').click()
  await page.waitForLoadState('networkidle')

  await expect(page.getByTestId('match-result-editor')).toBeHidden()
  await expect(page.getByTestId(playedMatchTestId as string)).toContainText(/9\s*-\s*0/)

  const tableAfterEdit = await page.locator('[data-testid="league-table"]').textContent()
  expect(tableAfterEdit).not.toBe(tableBeforeEdit)
})
