import React, { useEffect, useState } from 'react';
import { X, Save, AlertCircle, Plus, Trash2 } from 'lucide-react';

const DAYS = [
  { id: 1, name: 'Monday', short: 'Mon' },
  { id: 2, name: 'Tuesday', short: 'Tue' },
  { id: 3, name: 'Wednesday', short: 'Wed' },
  { id: 4, name: 'Thursday', short: 'Thu' },
  { id: 5, name: 'Friday', short: 'Fri' },
  { id: 6, name: 'Saturday', short: 'Sat' },
  { id: 7, name: 'Sunday', short: 'Sun' },
];

const DEFAULT_SHIFT = { open_time: '11:30', close_time: '22:00' };

function emptyWeek(open = true) {
  return DAYS.map((day) => ({
    day_of_week: day.id,
    name: day.name,
    short: day.short,
    is_closed: !open,
    shifts: open ? [{ ...DEFAULT_SHIFT }] : [],
  }));
}

function weekFromRestaurant(restaurant) {
  const source = Array.isArray(restaurant?.weekly_hours) ? restaurant.weekly_hours : [];

  return DAYS.map((day) => {
    const entry = source.find((item) => item.day_of_week === day.id);
    const shifts = (entry?.shifts || []).map((shift) => ({
      open_time: (shift.open_time || '').slice(0, 5),
      close_time: (shift.close_time || '').slice(0, 5),
    }));

    return {
      day_of_week: day.id,
      name: day.name,
      short: day.short,
      is_closed: shifts.length === 0,
      shifts: shifts.length > 0 ? shifts : [{ ...DEFAULT_SHIFT }],
    };
  });
}

function spansNextDay(shift) {
  if (!shift.open_time || !shift.close_time) return false;
  return shift.close_time < shift.open_time;
}

export default function RestaurantModal({ isOpen, onClose, onSave, initialData = null }) {
  const [formData, setFormData] = useState({
    name: '',
    cuisine_type: '',
    address: '',
    rating: 4.5,
    description: '',
  });
  const [week, setWeek] = useState(() => emptyWeek(true));
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (initialData) {
      setFormData({
        name: initialData.name || '',
        cuisine_type: initialData.cuisine_type || '',
        address: initialData.address || '',
        rating: initialData.rating ?? 4.5,
        description: initialData.description || '',
      });
      setWeek(weekFromRestaurant(initialData));
    } else {
      setFormData({
        name: '',
        cuisine_type: '',
        address: '',
        rating: 4.5,
        description: '',
      });
      setWeek(emptyWeek(true));
    }
    setError(null);
  }, [initialData, isOpen]);

  useEffect(() => {
    if (!isOpen) return undefined;
    const onKeyDown = (event) => {
      if (event.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const updateDay = (dayOfWeek, updater) => {
    setWeek((prev) =>
      prev.map((day) => (day.day_of_week === dayOfWeek ? updater(day) : day))
    );
  };

  const toggleDay = (dayOfWeek) => {
    updateDay(dayOfWeek, (day) => ({
      ...day,
      is_closed: !day.is_closed,
      shifts: day.is_closed || day.shifts.length === 0 ? [{ ...DEFAULT_SHIFT }] : day.shifts,
    }));
  };

  const updateShift = (dayOfWeek, index, field, value) => {
    updateDay(dayOfWeek, (day) => ({
      ...day,
      shifts: day.shifts.map((shift, shiftIndex) =>
        shiftIndex === index ? { ...shift, [field]: value } : shift
      ),
    }));
  };

  const addShift = (dayOfWeek) => {
    updateDay(dayOfWeek, (day) => {
      const last = day.shifts[day.shifts.length - 1] || DEFAULT_SHIFT;
      const open = last.close_time || DEFAULT_SHIFT.close_time;
      const close = ['22:00', '23:00', '23:30', '23:59'].find((time) => time > open) || '23:59';

      return {
        ...day,
        shifts: [
          ...day.shifts,
          {
            open_time: open === close ? '23:00' : open,
            close_time: close,
          },
        ],
      };
    });
  };

  const removeShift = (dayOfWeek, index) => {
    updateDay(dayOfWeek, (day) => {
      const shifts = day.shifts.filter((_, shiftIndex) => shiftIndex !== index);

      return {
        ...day,
        is_closed: shifts.length === 0,
        shifts,
      };
    });
  };

  const applyMondayToAll = () => {
    const monday = week.find((day) => day.day_of_week === 1);
    if (!monday) return;

    setWeek((prev) =>
      prev.map((day) => ({
        ...day,
        is_closed: monday.is_closed,
        shifts: monday.shifts.map((shift) => ({ ...shift })),
      }))
    );
  };

  const buildSchedules = () => {
    const schedules = [];

    for (const day of week) {
      if (day.is_closed) continue;

      if (day.shifts.length === 0) {
        throw new Error(`${day.name} is open but has no service window.`);
      }

      for (const shift of day.shifts) {
        if (!shift.open_time || !shift.close_time) {
          throw new Error(`${day.name} has an incomplete service window.`);
        }

        if (shift.open_time === shift.close_time) {
          throw new Error(`${day.name}: a service must end at a different time than it starts.`);
        }

        schedules.push({
          day_of_week: day.day_of_week,
          open_time: shift.open_time,
          close_time: shift.close_time,
        });
      }
    }

    return schedules;
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!formData.name.trim()) {
      setError('A restaurant name is required.');
      return;
    }

    let schedules;

    try {
      schedules = buildSchedules();
    } catch (err) {
      setError(err.message);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await onSave({ ...formData, schedules }, initialData?.id);
      onClose();
    } catch (err) {
      const message =
        err.response?.data?.message ||
        Object.values(err.response?.data?.errors || {})?.[0]?.[0] ||
        err.message ||
        'The record could not be saved.';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const openDays = week.filter((day) => !day.is_closed).length;
  const services = week.reduce((total, day) => total + (day.is_closed ? 0 : day.shifts.length), 0);

  return (
    <div className="sheet-overlay" onClick={onClose} role="presentation">
      <div
        className="sheet"
        onClick={(event) => event.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="restaurant-sheet-title"
      >
        <div className="sheet-head">
          <div>
            <p className="eyebrow">{initialData ? 'Edit record' : 'New record'}</p>
            <h2 className="sheet-title" id="restaurant-sheet-title">
              {initialData ? initialData.name : 'Add a restaurant'}
            </h2>
            <p className="sheet-sub">Weekly shifts · stored as structured rows</p>
          </div>
          <button type="button" className="close-btn" onClick={onClose} aria-label="Close panel">
            <X size={18} strokeWidth={1.6} />
          </button>
        </div>

        <form
          id="restaurant-form"
          className="sheet-body"
          onSubmit={handleSubmit}
          style={{ gap: '1.15rem' }}
        >
          {error && (
            <div className="form-error" role="alert">
              <AlertCircle size={16} strokeWidth={1.6} style={{ flex: 'none', marginTop: 1 }} />
              <span>{error}</span>
            </div>
          )}

          <div className="form-group">
            <label className="form-label" htmlFor="modal-name">
              Restaurant name
            </label>
            <input
              id="modal-name"
              type="text"
              className="form-input"
              placeholder="e.g. Kushi Tsuru"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
          </div>

          <div className="form-group">
            <div className="hours-head">
              <span className="form-label">Operating hours</span>
              <span className="hours-meta">
                {openDays}/7 days · {services} services
              </span>
            </div>

            <div className="hours-editor">
              {week.map((day) => (
                <div className={`hours-day${day.is_closed ? ' is-closed' : ''}`} key={day.day_of_week}>
                  <div className="hours-day-head">
                    <span className="hours-day-name">{day.name}</span>
                    <div className="hours-toggle" role="group" aria-label={`${day.name} status`}>
                      <button
                        type="button"
                        className={!day.is_closed ? 'is-active' : ''}
                        onClick={() => day.is_closed && toggleDay(day.day_of_week)}
                      >
                        Open
                      </button>
                      <button
                        type="button"
                        className={day.is_closed ? 'is-active' : ''}
                        onClick={() => !day.is_closed && toggleDay(day.day_of_week)}
                      >
                        Closed
                      </button>
                    </div>
                  </div>

                  {!day.is_closed && (
                    <div className="hours-services">
                      {day.shifts.map((shift, index) => (
                        <div className="hours-service" key={`${day.day_of_week}-${index}`}>
                          <input
                            type="time"
                            className="form-input hours-time"
                            value={shift.open_time}
                            onChange={(e) =>
                              updateShift(day.day_of_week, index, 'open_time', e.target.value)
                            }
                            aria-label={`${day.name} service ${index + 1} start`}
                          />
                          <span className="hours-dash">to</span>
                          <input
                            type="time"
                            className="form-input hours-time"
                            value={shift.close_time}
                            onChange={(e) =>
                              updateShift(day.day_of_week, index, 'close_time', e.target.value)
                            }
                            aria-label={`${day.name} service ${index + 1} end`}
                          />
                          {spansNextDay(shift) && <span className="hours-spans">+1 day</span>}
                          <button
                            type="button"
                            className="hours-remove"
                            onClick={() => removeShift(day.day_of_week, index)}
                            aria-label={`Remove ${day.name} service ${index + 1}`}
                          >
                            <Trash2 size={14} strokeWidth={1.6} />
                          </button>
                        </div>
                      ))}

                      <button
                        type="button"
                        className="link-btn hours-add"
                        onClick={() => addShift(day.day_of_week)}
                      >
                        <Plus size={13} strokeWidth={1.8} />
                        Add service
                      </button>
                    </div>
                  )}
                </div>
              ))}
            </div>

            <div className="hours-actions">
              <button type="button" className="chip" onClick={applyMondayToAll}>
                Copy Monday to all days
              </button>
              <button type="button" className="chip" onClick={() => setWeek(emptyWeek(false))}>
                Clear week
              </button>
            </div>

            <p className="form-hint">
              Each row is a real service window stored in the database. Add a second service for
              lunch and dinner; leave the day closed when there is no service. A window that ends
              earlier than it starts is treated as after midnight.
            </p>
          </div>

          <div className="form-grid">
            <div className="form-group">
              <label className="form-label" htmlFor="modal-cuisine">
                Cuisine
              </label>
              <input
                id="modal-cuisine"
                type="text"
                className="form-input"
                placeholder="Japanese, Italian, Cafe"
                value={formData.cuisine_type}
                onChange={(e) => setFormData({ ...formData, cuisine_type: e.target.value })}
              />
            </div>

            <div className="form-group">
              <label className="form-label" htmlFor="modal-rating">
                Rating (1–5)
              </label>
              <input
                id="modal-rating"
                type="number"
                step="0.1"
                min="1"
                max="5"
                className="form-input"
                value={formData.rating}
                onChange={(e) => {
                  const v = parseFloat(e.target.value);
                  setFormData({ ...formData, rating: isNaN(v) ? 4.5 : v });
                }}
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="modal-address">
              Address
            </label>
            <input
              id="modal-address"
              type="text"
              className="form-input"
              placeholder="123 Market St, San Francisco, CA"
              value={formData.address}
              onChange={(e) => setFormData({ ...formData, address: e.target.value })}
            />
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="modal-desc">
              Description
            </label>
            <textarea
              id="modal-desc"
              className="form-input"
              rows={3}
              placeholder="Optional note for the admin record"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            />
          </div>
        </form>

        <div className="sheet-foot">
          <button type="button" className="btn btn-quiet" onClick={onClose} disabled={loading}>
            Cancel
          </button>
          <button type="submit" form="restaurant-form" className="btn btn-primary" disabled={loading}>
            <Save size={15} strokeWidth={1.6} />
            {loading ? 'Saving…' : initialData ? 'Save changes' : 'Create record'}
          </button>
        </div>
      </div>
    </div>
  );
}
