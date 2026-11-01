import React from 'react';
import { Star, Edit3, Trash2 } from 'lucide-react';
import {
  WEEK_LETTERS,
  dayHoursLabel,
  nextOpenDay,
  resolveTodayIso,
  formatClock,
} from '../services/hours.js';

export default function RestaurantCard({
  restaurant,
  serverDayName = null,
  onViewSchedule,
  onEdit,
  onDelete,
  isAdmin = false,
  index = 0,
  order = 0,
}) {
  const schedule = restaurant.formatted_schedule || 'Hours not published';
  const displayIndex = String(index).padStart(2, '0');

  const weekly = Array.isArray(restaurant.weekly_hours) ? restaurant.weekly_hours : [];
  const todayIso = resolveTodayIso(serverDayName);
  const todayEntry = weekly.find((day) => day.day_of_week === todayIso);
  const todayHours = dayHoursLabel(todayEntry);
  const upcoming = todayEntry && !todayEntry.is_closed ? null : nextOpenDay(weekly, todayIso);

  return (
    <article
      className="resto-row"
      style={{ '--i': Math.min(order, 12) }}
      aria-label={`${restaurant.name}, ${restaurant.is_open_now ? 'open now' : 'closed'}`}
      onClick={() => onViewSchedule(restaurant)}
      role="button"
      tabIndex={0}
      title="Click to view weekly schedule"
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onViewSchedule(restaurant);
        }
      }}
    >
      <span className="row-index mono">{displayIndex}</span>

      <div className="row-main">
        <h3 className="row-name" title={restaurant.name}>
          {restaurant.name}
        </h3>
        <span className="row-cuisine">{restaurant.cuisine_type || 'Dining'}</span>
        {restaurant.address && <p className="row-address">{restaurant.address}</p>}
      </div>

      <div className="row-hours">
        <span className="row-label">Hours</span>
        {weekly.length > 0 ? (
          <>
            <p
              className={`row-hours-today${todayHours ? '' : ' is-closed'}`}
              title={schedule}
            >
              {todayHours ?? `Closed today${upcoming ? ` · Opens ${upcoming.name} ${formatClock(upcoming.at)}` : ''}`}
            </p>
            <div
              className="row-week"
              title={schedule}
              aria-label={`Weekly hours: ${weekly
                .map((day) => `${day.day_name} ${dayHoursLabel(day) ?? 'closed'}`)
                .join(', ')}`}
            >
              {weekly.map((day) => (
                <span
                  key={day.day_of_week}
                  className={[
                    'week-day',
                    day.is_closed ? 'off' : '',
                    day.day_of_week === todayIso ? 'today' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                >
                  {WEEK_LETTERS[day.day_of_week - 1]}
                </span>
              ))}
            </div>
          </>
        ) : (
          <p className="row-hours-text" title={schedule}>
            {schedule}
          </p>
        )}
      </div>

      <div className="row-rating">
        <Star size={12} strokeWidth={1.4} fill="#b1502a" color="#b1502a" aria-hidden="true" />
        <span>{Number(restaurant.rating ?? 4.5).toFixed(1)}</span>
      </div>

      <div className={`row-status ${restaurant.is_open_now ? 'open' : ''}`}>
        <span className={`dot ${restaurant.is_open_now ? 'open' : ''}`} aria-hidden="true" />
        {restaurant.is_open_now ? 'Open now' : 'Closed now'}
      </div>

      <div className="row-actions">
        <button
          type="button"
          className="link-btn"
          onClick={(e) => {
            e.stopPropagation();
            onViewSchedule(restaurant);
          }}
        >
          Weekly hours
        </button>

        {isAdmin && onEdit && (
          <button
            type="button"
            className="btn-icon"
            onClick={(e) => {
              e.stopPropagation();
              onEdit(restaurant);
            }}
            title={`Edit ${restaurant.name}`}
            aria-label={`Edit ${restaurant.name}`}
          >
            <Edit3 size={15} strokeWidth={1.6} />
          </button>
        )}

        {onDelete && (
          <button
            type="button"
            className="btn-icon danger"
            onClick={(e) => {
              e.stopPropagation();
              onDelete(restaurant);
            }}
            title={`Delete ${restaurant.name}`}
            aria-label={`Delete ${restaurant.name}`}
          >
            <Trash2 size={15} strokeWidth={1.6} />
          </button>
        )}
      </div>
    </article>
  );
}
