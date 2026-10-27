import React from 'react';
import { X, Clock, Calendar, CheckCircle2, AlertCircle } from 'lucide-react';

const DAYS_OF_WEEK = [
  { id: 1, name: 'Monday', short: 'Mon' },
  { id: 2, name: 'Tuesday', short: 'Tue' },
  { id: 3, name: 'Wednesday', short: 'Wed' },
  { id: 4, name: 'Thursday', short: 'Thu' },
  { id: 5, name: 'Friday', short: 'Fri' },
  { id: 6, name: 'Saturday', short: 'Sat' },
  { id: 7, name: 'Sunday', short: 'Sun' },
];

export default function ScheduleModal({ restaurant, onClose }) {
  if (!restaurant) return null;

  // Format 24h time to 12h display
  const formatTime = (timeStr) => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const h = parseInt(hours, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const formattedH = h % 12 || 12;
    return `${formattedH}:${minutes} ${ampm}`;
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <div>
            <h2 style={{ fontSize: '1.25rem', color: '#f8fafc' }}>{restaurant.name}</h2>
            <p style={{ fontSize: '0.825rem', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '0.35rem', marginTop: '0.2rem' }}>
              <Clock size={14} style={{ color: '#818cf8' }} />
              Structured Operating Hours Breakdown
            </p>
          </div>
          <button className="close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {/* Original Raw Schedule */}
        <div style={{
          background: 'rgba(99, 102, 241, 0.1)',
          border: '1px solid rgba(99, 102, 241, 0.25)',
          padding: '0.85rem 1.15rem',
          borderRadius: '12px',
          marginBottom: '1.5rem',
          fontSize: '0.85rem',
        }}>
          <span style={{ color: '#a5b4fc', fontWeight: 600, display: 'block', marginBottom: '0.25rem' }}>
            Original Schedule String:
          </span>
          <code style={{ color: '#e0e7ff', fontFamily: 'monospace', fontSize: '0.9rem' }}>
            {restaurant.raw_schedule || restaurant.formatted_schedule}
          </code>
        </div>

        {/* Day-by-Day Table */}
        <h4 style={{ fontSize: '0.95rem', marginBottom: '0.75rem', color: '#cbd5e1' }}>
          Weekly Schedule Breakdown
        </h4>

        <table className="shifts-table">
          <thead>
            <tr>
              <th>Day</th>
              <th>Opening Shift</th>
              <th>Spans Next Day</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {DAYS_OF_WEEK.map((day) => {
              const dayShifts = restaurant.schedules?.filter((s) => s.day_of_week === day.id) || [];
              const isOpen = dayShifts.length > 0;

              return (
                <tr key={day.id} style={{ background: isOpen ? 'transparent' : 'rgba(255, 255, 255, 0.02)' }}>
                  <td style={{ fontWeight: 600 }}>{day.name}</td>
                  <td>
                    {isOpen ? (
                      dayShifts.map((s, idx) => (
                        <div key={idx} style={{ color: '#f1f5f9' }}>
                          {formatTime(s.open_time)} – {formatTime(s.close_time)}
                        </div>
                      ))
                    ) : (
                      <span style={{ color: '#ef4444', fontStyle: 'italic' }}>Closed</span>
                    )}
                  </td>
                  <td>
                    {isOpen && dayShifts.some((s) => s.spans_next_day) ? (
                      <span style={{
                        background: 'rgba(245, 158, 11, 0.15)',
                        color: '#fbbf24',
                        padding: '0.2rem 0.5rem',
                        borderRadius: '6px',
                        fontSize: '0.75rem',
                        fontWeight: 600,
                      }}>
                        Past Midnight
                      </span>
                    ) : (
                      <span style={{ color: '#64748b', fontSize: '0.8rem' }}>No</span>
                    )}
                  </td>
                  <td>
                    {isOpen ? (
                      <span style={{ color: '#10b981', display: 'flex', alignItems: 'center', gap: '0.25rem', fontSize: '0.8rem' }}>
                        <CheckCircle2 size={14} /> Open
                      </span>
                    ) : (
                      <span style={{ color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '0.25rem', fontSize: '0.8rem' }}>
                        <AlertCircle size={14} /> Off
                      </span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>

        <div style={{ marginTop: '1.5rem', display: 'flex', justifyContent: 'flex-end' }}>
          <button className="btn btn-secondary btn-sm" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
}
