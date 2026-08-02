function partsAt(instant: number, timeZone: string): Record<string, number> {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(new Date(instant))

  return Object.fromEntries(
    parts
      .filter((part) => part.type !== 'literal')
      .map((part) => [part.type, Number(part.value)]),
  )
}

export function localDateTimeToUtcIso(value: string, timeZone: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/.exec(value)

  if (!match) {
    throw new Error('Invalid local date and time.')
  }

  const desired = {
    year: Number(match[1]),
    month: Number(match[2]),
    day: Number(match[3]),
    hour: Number(match[4]),
    minute: Number(match[5]),
    second: Number(match[6] ?? 0),
  }
  const wallClockAsUtc = Date.UTC(
    desired.year,
    desired.month - 1,
    desired.day,
    desired.hour,
    desired.minute,
    desired.second,
  )
  let instant = wallClockAsUtc

  for (let attempt = 0; attempt < 3; attempt += 1) {
    const actual = partsAt(instant, timeZone)
    const offset = Date.UTC(
      actual.year,
      actual.month - 1,
      actual.day,
      actual.hour,
      actual.minute,
      actual.second,
    ) - instant
    instant = wallClockAsUtc - offset
  }

  const resolved = partsAt(instant, timeZone)
  const isSameWallClock = Object.entries(desired).every(
    ([part, expected]) => resolved[part] === expected,
  )

  if (!isSameWallClock) {
    throw new Error('The local date and time does not exist in the selected timezone.')
  }

  return new Date(instant).toISOString()
}
