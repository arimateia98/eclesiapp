import { describe, expect, it } from 'vitest'
import { localDateTimeToUtcIso } from './dateTime'

describe('localDateTimeToUtcIso', () => {
  it('interpreta o horário no timezone da organização', () => {
    expect(localDateTimeToUtcIso('2026-08-15T19:00', 'America/Fortaleza'))
      .toBe('2026-08-15T22:00:00.000Z')
  })

  it('respeita o deslocamento vigente no dia informado', () => {
    expect(localDateTimeToUtcIso('2026-01-15T19:00', 'America/New_York'))
      .toBe('2026-01-16T00:00:00.000Z')
    expect(localDateTimeToUtcIso('2026-07-15T19:00', 'America/New_York'))
      .toBe('2026-07-15T23:00:00.000Z')
  })

  it('rejeita um horário local inexistente na transição sazonal', () => {
    expect(() => localDateTimeToUtcIso('2026-03-08T02:30', 'America/New_York'))
      .toThrow('does not exist')
  })
})
