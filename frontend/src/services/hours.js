/**
 * Presentation helpers for per-restaurant opening hours.
 *
 * `weekly_hours` from the API is the source of truth (1 = Monday ... 7 =
 * Sunday). "Today" is anchored to the server's day whenever available so the
 * Hours column always agrees with the Open now / Closed now badge, which is
 * computed on the server in its configured timezone.
 */

export const WEEK_LETTERS = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];

const SERVER_DAY_NAMES = {
  Monday: 1,
  Tuesday: 2,
  Wednesday: 3,
  Thursday: 4,
  Friday: 5,
  Saturday: 6,
  Sunday: 7,
};

/** 24-hour "19:00" -> "7:00 PM" */
export function formatClock(timeStr) {
  if (!timeStr) return '';
  const [hours, minutes] = timeStr.split(':');
  const h = parseInt(hours, 10);
  const suffix = h >= 12 ? 'PM' : 'AM';
  const display = h % 12 || 12;
  return `${display}:${minutes} ${suffix}`;
}

/** Browser-local day as ISO weekday (1 Monday - 7 Sunday). */
export function browserDayIso() {
  const day = new Date().getDay(); // 0 Sunday - 6 Saturday
  return day === 0 ? 7 : day;
}

/** Resolve "today" from the server-reported day name, with a local fallback. */
export function resolveTodayIso(currentDayName) {
  const mapped = SERVER_DAY_NAMES[currentDayName];
  return mapped ?? browserDayIso();
}

/** "11:00 AM – 2:00 PM & 5:00 PM – 11:00 PM" for one weekly_hours day, or null when closed. */
export function dayHoursLabel(day) {
  if (!day || day.is_closed || !Array.isArray(day.shifts) || day.shifts.length === 0) {
    return null;
  }

  return day.shifts
    .map((shift) => `${formatClock(shift.open_time)} – ${formatClock(shift.close_time)}`)
    .join(' & ');
}

/** Next day (from `todayIso`, wrapping the week) where the venue opens, or null. */
export function nextOpenDay(weekly, todayIso) {
  if (!Array.isArray(weekly)) return null;

  for (let step = 1; step <= 7; step += 1) {
    const target = ((todayIso - 1 + step) % 7) + 1;
    const day = weekly.find((entry) => entry.day_of_week === target);
    const label = dayHoursLabel(day);

    if (label) {
      return { name: day.day_name_short, at: day.shifts[0].open_time, label };
    }
  }

  return null;
}
