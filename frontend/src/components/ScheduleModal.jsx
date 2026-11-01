import React, { useEffect } from 'react';
import { X } from 'lucide-react';

function formatTime(timeStr) {
  if (!timeStr) return '';
  const [hours, minutes] = timeStr.split(':');
  const h = parseInt(hours, 10);
  const suffix = h >= 12 ? 'PM' : 'AM';
  const display = h % 12 || 12;
  return `${display}:${minutes} ${suffix}`;
}

export default function ScheduleModal({ restaurant, onClose }) {
  useEffect(() => {
    if (!restaurant) return undefined;
    const onKeyDown = (event) => {
      if (event.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [restaurant, onClose]);

  if (!restaurant) return null;

  const weekly = Array.isArray(restaurant.weekly_hours) ? restaurant.weekly_hours : [];

  const openDays = weekly.filter((day) => !day.is_closed).length;
  const shiftCount =
    restaurant.shifts_per_week ?? weekly.reduce((total, day) => total + day.shifts.length, 0);

  return (
    <div className="sheet-overlay" onClick={onClose} role="presentation">
      <div
        className="sheet"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="schedule-sheet-title"
      >
        <div className="sheet-head">
          <div>
            <p className="eyebrow">Weekly Schedule</p>
            <h2 className="sheet-title" id="schedule-sheet-title">
              {restaurant.name}
            </h2>
            <p className="sheet-sub">
              {restaurant.cuisine_type || 'Dining'} · {restaurant.address || 'Indonesia'}
            </p>
          </div>
          <button type="button" className="close-btn" onClick={onClose} aria-label="Close panel">
            <X size={18} strokeWidth={1.6} />
          </button>
        </div>

        <div className="sheet-body">
          <div className="schedule-facts">
            <div className="schedule-fact">
              <span className="schedule-fact-label">Open days</span>
              <strong>
                {openDays}
                <span className="schedule-fact-suffix">/7</span>
              </strong>
            </div>
            <div className="schedule-fact">
              <span className="schedule-fact-label">Weekly shifts</span>
              <strong>{shiftCount}</strong>
            </div>
            <div className="schedule-fact schedule-fact-wide">
              <span className="schedule-fact-label">Weekly summary</span>
              <p>{restaurant.formatted_schedule || 'Hours not published'}</p>
            </div>
          </div>

          <div className="shifts-table-wrap">
            <table className="shifts-table">
              <thead>
                <tr>
                  <th scope="col">Day</th>
                  <th scope="col">Service</th>
                  <th scope="col">Hours</th>
                  <th scope="col">Schedule Note</th>
                </tr>
              </thead>
              <tbody>
                {weekly.map((day) => {
                  if (day.is_closed) {
                    return (
                      <tr key={day.day_of_week} className="is-closed">
                        <td className="shift-day">{day.day_name}</td>
                        <td colSpan={2}>
                          <span className="shift-state">Closed all day</span>
                        </td>
                        <td>
                          <span className="shift-flag">No service</span>
                        </td>
                      </tr>
                    );
                  }

                  return day.shifts.map((shift, index) => (
                    <tr key={`${day.day_of_week}-${shift.open_time}-${index}`}>
                      {index === 0 && (
                        <td className="shift-day" rowSpan={day.shifts.length}>
                          {day.day_name}
                        </td>
                      )}
                      <td>
                        <span className="shift-service">{shift.service_label}</span>
                      </td>
                      <td>
                        <div className="shift-time">
                          {formatTime(shift.open_time)} – {formatTime(shift.close_time)}
                        </div>
                      </td>
                      <td>
                        {shift.spans_next_day ? (
                          <span className="shift-flag" title="Ends after midnight on the following day">
                            +1 day
                          </span>
                        ) : (
                          <span className="shift-same">Same day</span>
                        )}
                      </td>
                    </tr>
                  ));
                })}
              </tbody>
            </table>
          </div>
        </div>

        <div className="sheet-foot">
          <button type="button" className="btn btn-quiet" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
}
